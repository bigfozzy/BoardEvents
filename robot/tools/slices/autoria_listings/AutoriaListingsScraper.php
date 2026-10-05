<?php

/**
 * Чтение auto.ria.com: страница выдачи и страница объявления.
 *
 * Про формат результата и про то, куда он кладётся, этот класс не знает.
 *
 * ВНИМАНИЕ, ЛОКАТОРЫ. Вёрстка доски меняется без предупреждения, и
 * когда локатор перестаёт находить - это первое, что нужно чинить.
 * Порядок проверки: сначала открыть страницу глазами и посмотреть,
 * что реально в разметке, и только потом менять константы здесь.
 * Ничего не хардкодим «наугад»: пустой результат честнее неверного.
 */
class AutoriaListingsScraper
{
    /**
     * Класс карточки объявления в выдаче.
     * Раньше в C# разбор шёл поиском подстроки 'class="address" href="'
     * по всему документу - порядок атрибутов в разметке не гарантирован.
     */
    private const CARD_CLASS = 'mainlink';

    /**
     * Часть адреса объявления. Отсекает рекламные и служебные ссылки,
     * которые попадают в выдачу вместе с карточками.
     */
    private const LISTING_PATH_PART = '/car/';

    /** Класс с телефоном на странице объявления */
    private const PHONE_CLASS = 'phone-wrap';

    /**
     * Собрать объявления со страницы выдачи.
     *
     * @param string $listUrl Адрес выдачи с уже применёнными фильтрами
     * @param int $limit Сколько максимум взять
     * @return AutoriaListingItem[] карточки без телефона и даты
     */
    public function harvestList(string $listUrl, int $limit = 100): array
    {
        WEB::$browser->navigate($listUrl);
        // список подрисовывается скриптом: без паузы выдача пустая
        WEB::$browser->wait_js();

        $found = DOM::$anchor->get_all_by_class(self::CARD_CLASS, false);
        $count = $found->count();

        if ($count === 0) {
            TOOLS::$log->warn(
                "Карточек с классом '" . self::CARD_CLASS . "' не найдено на $listUrl - "
                . "проверьте локатор: разметка доски изменилась",
                __METHOD__
            );
            return [];
        }

        $items = [];
        $take = min($limit, $count);

        for ($i = 0; $i < $take; $i++) {
            $anchor = $found->get($i);
            if (!$anchor->is_exist())
                continue;

            $href = trim((string)$anchor->get_href());
            if ($href === '')
                continue;

            $url = self::absoluteUrl($listUrl, $href);

            // не объявление
            if (!str_contains($url, self::LISTING_PATH_PART))
                continue;

            $items[self::key($url)] = new AutoriaListingItem(
                $url,
                trim((string)$anchor->get_inner_text())
            );
        }

        TOOLS::$log->info(
            "auto.ria: карточек в выдаче $count, взято " . count($items),
            __METHOD__
        );

        return array_values($items);
    }

    /**
     * Добрать телефон и дату объявления.
     *
     * Меняет объект на месте и возвращает его же - чтобы вызывающий
     * не собирал новый и не терял уже собранные поля.
     */
    public function fillDetails(AutoriaListingItem $item): AutoriaListingItem
    {
        WEB::$browser->navigate($item->url);
        WEB::$browser->wait_js();

        // телефон
        $phone = DOM::$span->get_by_class(self::PHONE_CLASS, false);

        if ($phone->is_exist()) {
            // в разметке телефон обёрнут пробелами и переносами строк
            $raw = trim((string)$phone->get_inner_text());
            $item->phone = (string)preg_replace('/\s+/', ' ', $raw);
        } else {
            TOOLS::$log->warn('Телефон не найден на ' . $item->url, __METHOD__);
        }

        $item->normedPhone = BoardRules::getNormedPhone($item->phone);

        // дата публикации
        $rawDate = $this->readPostedDateRaw($item->url);
        $item->postedDateRaw = $rawDate;
        $item->postedDate = self::parseHumanDate($rawDate) ?? '';

        if ($item->postedDate === '' && $rawDate !== '') {
            // доска поменяла формат - объявление не отсеется как старое,
            // но в логе будет видно, что разбор даты сломался
            TOOLS::$log->warn(
                'Не удалось разобрать дату "' . $rawDate . '" на ' . $item->url,
                __METHOD__
            );
        }

        return $item;
    }

    /**
     * Сырая строка даты публикации со страницы объявления.
     */
    private function readPostedDateRaw(string $url): string
    {
        // дата лежит в подписи под заголовком, а не в отдельном
        // элементе с устойчивым классом - читаем текст объявления
        $source = WEB::$webpage->get_source();

        if (preg_match('/Объявление добавлено\s+(.+?)(?:<|\r|\n)/u', $source, $m)) {
            return trim(strip_tags($m[1]));
        }

        return '';
    }

    /**
     * Человеческая дата в Y-m-d.
     *
     * Доска отдаёт "сегодня", "вчера", "5 дней назад" или число
     * с названием месяца.
     *
     * @return string|null null если формат не распознан
     */
    public static function parseHumanDate(string $text): ?string
    {
        if ($text === '')
            return null;

        $lower = mb_strtolower($text);
        $now = time();

        if (str_contains($lower, 'сегодня') || str_contains($lower, 'час')) {
            return date('Y-m-d', $now);
        }
        if (str_contains($lower, 'вчера')) {
            return date('Y-m-d', $now - 86400);
        }

        // относительные: "5 дней назад", "2 недели назад", "3 часа назад"
        if (preg_match('/(\d+)\s*(минут|час|дн|недел)/u', $lower, $m)) {
            $n = (int)$m[1];
            $unit = $m[2];

            if ($unit === 'минут')
                $shift = $n * 3600;          // "5 минут назад" - грубо, но часы
            elseif ($unit === 'час')
                $shift = $n * 3600;
            elseif ($unit === 'недел')
                $shift = $n * 7 * 86400;
            else
                $shift = $n * 86400;

            return date('Y-m-d', $now - $shift);
        }

        // абсолютная дата с названием месяца: "12 октября"
        $months = [
            'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
            'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
            'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
        ];

        foreach ($months as $name => $num) {
            if (str_contains($lower, $name)) {
                if (!preg_match('/(\d{1,2})/u', $text, $dm))
                    return null;

                $year = (int)date('Y', $now);
                // месяц позже текущего - значит это прошлый год
                if ($num > (int)date('n', $now))
                    $year--;

                return sprintf('%d-%02d-%02d', $year, $num, (int)$dm[1]);
            }
        }

        // возможно уже пришла в машинном виде
        $ts = strtotime($text);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    /**
     * Ссылку привести к абсолютному виду.
     */
    private static function absoluteUrl(string $pageUrl, string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://'))
            return $href;

        $parts = parse_url($pageUrl);
        $host = $parts['host'] ?? '';

        if ($host === '')
            return $href;

        return 'https://' . $host . (str_starts_with($href, '/') ? $href : '/' . $href);
    }

    /**
     * Ключ дедупликации: путь без схемы и меток.
     */
    private static function key(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === null || $path === '')
            return $url;

        return rtrim($path, '/');
    }
}