<?php

/**
 * Чтение auto.ria.com: страница выдачи и страница объявления.
 *
 * Про формат результата и про то, куда он кладётся, этот класс не знает.
 *
 * ЛОКАТОРЫ ПРОВЕРЕНЫ НА ЖИВОЙ ДОСКЕ (10.2026). Это важно: прежний
 * C#-парсер держался на классе `mainlink`, которого на доске больше
 * нет, - список молча становился пустым.
 *
 *  выдача   карточка объявления: <a class="link product-card horizontal"
 *           href="/auto_bmw_x5_40521845.html" data-car-id="40521845">
 *           Отсекаем навигацию: ссылки вида /car/bmw/x5/ - это разделы.
 *
 *  объявление разбирается как schema.org/Vehicle, см.
 *           AutoriaListingsVehicleData.
 *
 * ТЕЛЕФОН. В разметке он замаскирован, например (068) XXX XX XX, и
 * полный номер приходит отдельным запросом после клика по блоку
 * телефона. Кнопку раскрытия в разметке нет - блок собирается из
 * JSON-конфига, - поэтому клик здесь не ставится: хардкодить
 * локатор, которого мы не видели, запрещено правилами XHE, и такой
 * клик просто не сработал бы. Маска и продавец попадают в результат,
 * чтобы покупатель знал, что номер есть и его надо раскрыть руками.
 */
class AutoriaListingsScraper
{
    /**
     * Класс карточки объявления в выдаче.
     * Проверен на доске: 'product-card'.
     */
    private const CARD_CLASS = 'product-card';

    /**
     * Адрес объявления начинается с /auto_ и кончается .html.
     * Прежний фильтр '/car/' ловил пункты меню, а не объявления.
     *
     * Публичная константа, а не приватная: правило не должно
     * разъехаться с разметкой, и проверяется тестами на реальных
     * адресах доски, где есть и объявления, и пункты меню.
     */
    public const LISTING_PATH_PATTERN = '#^/auto_[a-z0-9_\-]+\.html$#i';

    /**
     * Является ли путь адресом объявления.
     */
    public static function isListingPath(string $path): bool
    {
        return (bool)preg_match(self::LISTING_PATH_PATTERN, $path);
    }

    /**
     * На странице выдачи.
     *
     * @param string $listUrl Адрес выдачи с уже применёнными фильтрами
     * @param int $limit Сколько максимум взять
     * @return AutoriaListingItem[] карточки без деталей
     */
    public function harvestList(string $listUrl, int $limit = 100): array
    {
        WEB::$browser->navigate($listUrl);
        // список подрисовывается скриптом: без паузы выдача пустая
        WEB::$browser->wait_js();

        $found = DOM::$a->get_all_by_class(self::CARD_CLASS, false);
        $count = $found->count();

        if ($count === 0) {
            TOOLS::$log->warn(
                "Карточек с классом '" . self::CARD_CLASS . "' не найдено на $listUrl - "
                . 'проверьте локатор: разметка доски изменилась',
                __METHOD__
            );
            return [];
        }

        $items = [];
        $skippedNav = 0;
        $take = min($limit, $count);

        for ($i = 0; $i < $take; $i++) {
            $anchor = $found->get($i);
            if (!$anchor->is_exist())
                continue;

            $href = trim((string)$anchor->get_href());
            if ($href === '')
                continue;

            $path = (string)parse_url($href, PHP_URL_PATH);

            // навигация доски, а не объявление
            if (!self::isListingPath($path)) {
                $skippedNav++;
                continue;
            }

            $url = self::absoluteUrl($listUrl, $href);

            $items[$url] = new AutoriaListingItem($url);
        }

        TOOLS::$log->info(sprintf(
            'auto.ria: карточек %d, взято %d, служебных ссылок пропущено %d',
            $count,
            count($items),
            $skippedNav
        ), __METHOD__);

        return array_values($items);
    }

    /**
     * Разобрать страницу объявления и заполнить объект.
     *
     * Меняет объект на месте и возвращает его же, чтобы вызывающий
     * не собирал новый и не терял уже набранное.
     */
    public function fillDetails(AutoriaListingItem $item): AutoriaListingItem
    {
        WEB::$browser->navigate($item->url);
        WEB::$browser->wait_js();

        $source = WEB::$webpage->get_source();
        $data = AutoriaListingsVehicleData::fromPageSource($source);

        if ($data === null) {
            TOOLS::$log->error(
                'На странице нет блока schema.org/Vehicle: ' . $item->url
                . ' - разметка доски изменилась',
                __METHOD__
            );
            return $item;
        }

        $filled = AutoriaListingItem::fromVehicleData($data);

        // адрес из разметки может отличаться от того, по которому пришли;
        // оставляем фактический - по нему и пойдём в отчёт
        if ($filled->url === '') {
            $filled->url = $item->url;
        }

        // Телефон в разметке замаскирован, поэтому берём его из окна,
        // которое появляется после клика. Если не раскрылся - оставляем
        // маску и пишем об этом: молча пустая колонка выглядит как
        // «телефона нет», а на деле номер есть и просто не отдался.
        $phone = (new AutoriaListingsPhoneReveal())->reveal();

        if ($phone !== '') {
            $filled->phone = $phone;
        } elseif ($filled->hasPhoneMask()) {
            TOOLS::$log->info(
                'Телефон не раскрылся, остаётся маска (' . $filled->phoneMasked . '): '
                . $filled->url,
                __METHOD__
            );
        }

        return $filled;
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
}