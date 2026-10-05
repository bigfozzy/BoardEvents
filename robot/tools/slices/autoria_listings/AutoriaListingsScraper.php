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
     * Назначение номера страницы в адрес выдачи.
     *
     * Проверено на живой доске: страницы через ?page=N не пересекаются,
     * на каждой по 20 объявлений, всего 10 страниц.
     *
     * Номер добавляется к уже существующим параметрам, а не затирает их:
     * покупатель часто копирует адрес с фильтрами (?currency=UAH и
     * подобными), и потеря фильтров молча扩大ила бы выборку.
     *
     * @param string $listUrl Адрес выдачи
     * @param int $page Номер страницы, начиная с 1
     */
    public static function pageUrl(string $listUrl, int $page): string
    {
        if ($page <= 1) {
            return $listUrl;
        }

        // Если покупатель уже поставил page в скопированном адресе,
        // параметр заменяем, а не добавляем второй: ?page=5&page=2 -
        // это неопределённость, и доска может взять не тот.
        $pattern = '/([?&])page=\d*/';

        if (preg_match($pattern, $listUrl)) {
            return (string)preg_replace($pattern, '$1page=' . $page, $listUrl, 1);
        }

        $separator = str_contains($listUrl, '?') ? '&' : '?';

        return $listUrl . $separator . 'page=' . $page;
    }

    /**
     * На странице выдачи.
     *
     * Обходит $pages страниц подряд. Это не перестраховка, а
     * необходимость: доска НЕ сортирует выдачу по дате, и на марке
     * /car/bmw порядок произвольный. Читая только первую страницу,
     * робот пропускал бы новые объявления - и пропуск выглядел бы как
     * «новых объявлений нет». Сортировки по новизне у доски нет вовсе:
     * в разметке нет ни sort_by, ни переключателя порядка, только
     * фильтры (Все / Б/у / Новые / Под пригон).
     *
     * Постоянные затраты на пагинацию невелики: объявления, которые
     * уже отдавали, не открываются повторно благодаря файлу состояния, так
     * что каждый запуск платит только за загрузку страниц выдачи.
     *
     * @param string $listUrl Адрес выдачи с уже применёнными фильтрами
     * @param int $limit Сколько максимум объявлений взять со всех страниц
     * @param int $pages Сколько страниц обойти, начиная с первой
     * @return AutoriaListingItem[] карточки без деталей
     */
    public function harvestList(string $listUrl, int $limit = 100, int $pages = 1): array
    {
        $pages = $pages > 0 ? $pages : 1;
        $items = [];
        $totalSeen = 0;
        $stopped = false;

        for ($page = 1; $page <= $pages && !$stopped; $page++) {
            $pageItems = $this->harvestPage(self::pageUrl($listUrl, $page), $limit - count($items));

            $totalSeen += count($pageItems);

            foreach ($pageItems as $item) {
                // по ключу, а не по позиции: одна и та же машина может
                // попасть на соседние страницы
                $items[$item->key()] = $item;
            }

            // страница пустая или выбрано всё нужное - дальше идти незачем
            if ($pageItems === [] || count($items) >= $limit) {
                $stopped = true;
            }
        }

        TOOLS::$log->info(sprintf(
            'auto.ria: страниц обойдено %d, адресов собрано %d, взято %d',
            $stopped ? $page : ($page - 1),
            $totalSeen,
            count($items)
        ), __METHOD__);

        return array_slice(array_values($items), 0, $limit);
    }

    /**
     * Одна страница выдачи.
     *
     * @param string $url Адрес страницы
     * @param int $limit Сколько максимум взять
     * @return AutoriaListingItem[]
     */
    private function harvestPage(string $url, int $limit): array
    {
        WEB::$browser->navigate($url);
        // список подрисовывается скриптом: без паузы выдача пустая
        WEB::$browser->wait_js();

        // wait_js() ждёт завернения скриптов, но не появления карточек в
        // DOM, а get_all_by_class() элементов не ждёт - он возвращает
        // что нашёл на момент вызова. На медленной сети или если доска
        // отдала страницу не полностью, список приходил пустым и робот
        // молча не видел объявлений. Поэтому ждём карточку явно.
        DOM::$a->wait_element_exist_by_attribute('class', self::CARD_CLASS, false);

        $found = DOM::$a->get_all_by_class(self::CARD_CLASS, false);
        $count = $found->count();

        if ($count === 0) {
            // пустая страница - это не ошибка, а конец выдачи
            TOOLS::$log->debug('Карточек на странице нет: ' . $url, __METHOD__);
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

            $abs = self::absoluteUrl($url, $href);

            $items[$abs] = new AutoriaListingItem($abs);
        }

        if ($skippedNav > 0) {
            TOOLS::$log->debug(
                "Пропущено служебных ссылок на странице: $skippedNav",
                __METHOD__
            );
        }

        return array_values($items);
    }

/**
     * Разобрать страницу объявления и заполнить объект.
     *
     * @param bool $collectPhones Раскрывать ли телефон. По умолчанию НЕТ:
     *   условия RIA это прямо запрещают - см. AutoriaListingsPhoneReveal
     *   и README, раздел «Условия досок».
     * Меняет объект на месте и возвращает его же, чтобы вызывающий
     * не собирал новый и не терял уже набранное.
     */
    public function fillDetails(AutoriaListingItem $item, bool $collectPhones = false): AutoriaListingItem
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

        if (!$collectPhones) {
            // сбор контактов запрещён условиями доски, поэтому даже маску
            // не оставляем: она из тех же данных, что и номер
            $filled->phone = '';
            $filled->phoneMasked = '';

            return $filled;
        }

        // Телефон в разметке замаскирован, поэтому берём его из окна,
        // которое появляется после клика. Если не раскрылся - оставляем
        // маску и пишем об этом: молча пустая колонка выглядит как
        // «телефона нет», а на деле номер есть и просто не отдали.
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