<?php

/**
 * Разбор объявления auto.ria из HTML.
 *
 * Сделано на JSON-LD, а не на классах CSS, и это не stylistic выбор.
 * Вёрстку карточек доска переписывает регулярно: класс `mainlink`,
 * на котором держался прежний C#-парсер, на доске отсутствует
 * полностью. JSON-LD `schema.org/Vehicle` — это контракт разметки,
 * объявленный стандартом, и он переживает косметические правки.
 *
 * Класс не ходит в браузер и не знает про XHE: на входе строка HTML,
 * на выходе массив полей. Поэтому он проверяется обычным PHP.
 *
 * ЧТО В РАЗМЕТКЕ ЕСТЬ, а ЧТО НЕТ (проверено на живой доске):
 *
 *  есть  schema.org/Vehicle: марка, модель, год, VIN, пробег, кузов,
 *        цвет, топливо, коробка, двери, цена и валюта (offers),
 *        а также хлебные крошки, из которых берётся город;
 *
 *  нет   даты публикации объявления - ни на странице объявления,
 *        ни в карточке выдачи, ни в payload. Прежний парсер брал её
 *        из текста «Объявление добавлено ...», которого больше нет;
 *
 *  нет   открытого телефона. В разметке он замаскирован:
 *        «(068) XXX XX XX». Полный номер приходит отдельным запросом
 *        после клика по блоку телефона, поэтому из HTML не читается.
 *
 * Отсюда решение по «новым» объявлениям: новым считается то, чего
 * ещё не было в файле состояния, а не то, у которого свежая дата.
 * Дата на доске не нужна - см. AutoriaListingsState.
 */
class AutoriaListingsVehicleData
{
    /** Город не найден */
    public const CITY_UNKNOWN = '';

    /**
     * Разобрать страницу объявления.
     *
     * @param string $html Исходный код страницы
     * @return array|null null если Vehicle-блока нет
     */
    public static function fromPageSource(string $html): ?array
    {
        $vehicle = self::findVehicleBlock($html);

        if ($vehicle === null) {
            return null;
        }

        $offers = is_array($vehicle['offers'] ?? null) ? $vehicle['offers'] : [];

        // offers бывает списком, а не объектом
        if (array_is_list($offers)) {
            $offers = $offers[0] ?? [];
        }

        $mileage = is_array($vehicle['mileageFromOdometer'] ?? null)
            ? $vehicle['mileageFromOdometer']
            : [];

        return [
            'url' => (string)($vehicle['url'] ?? self::buildUrlFromId($vehicle)),
            'title' => trim((string)($vehicle['name'] ?? '')),
            'brand' => self::nestedName($vehicle['brand'] ?? null),
            'model' => trim((string)($vehicle['model'] ?? '')),
            'year' => self::toInt($vehicle['productionDate'] ?? null),
            'vin' => trim((string)($vehicle['vehicleIdentificationNumber'] ?? '')),
            'mileage' => self::toInt($mileage['value'] ?? null),
            'bodyType' => self::nestedName($vehicle['bodyType'] ?? null),
            'color' => trim((string)($vehicle['color'] ?? '')),
            'fuelType' => trim((string)($vehicle['fuelType'] ?? ($vehicle['vehicleEngine']['fuelType'] ?? ''))),
            'transmission' => trim((string)($vehicle['vehicleTransmission'] ?? '')),
            'doors' => self::toInt($vehicle['numberOfDoors'] ?? null),
            'price' => self::toInt($offers['price'] ?? null),
            'currency' => trim((string)($offers['priceCurrency'] ?? '')),
            'city' => self::findCity($html),
            'phoneMasked' => self::findPhoneMask($html),
            'sellerName' => self::findSellerName($html),
        ];
    }

    /**
     * Достать блок schema.org/Vehicle из разметки.
     *
     * На странице их два: первый несёт характеристики, второй -
     * только картинки. Берём тот, где есть хоть одно поле из
     * интересующих, иначе второй перебьёт первый.
     */
    private static function findVehicleBlock(string $html): ?array
    {
        $blocks = self::extractJsonLd($html);
        $fallback = null;

        foreach ($blocks as $block) {
            if (!self::isVehicle($block)) {
                continue;
            }

            $hasData = isset($block['mileageFromOdometer'])
                || isset($block['offers'])
                || isset($block['vehicleIdentificationNumber']);

            if ($hasData) {
                return $block;
            }

            $fallback = $fallback ?? $block;
        }

        return $fallback;
    }

    /**
     * Блок объявления — это Vehicle или Car, в массиве или по одному.
     */
    private static function isVehicle($block): bool
    {
        if (!is_array($block)) {
            return false;
        }

        $types = $block['@type'] ?? null;

        if (is_array($types)) {
            foreach ($types as $type) {
                if (in_array($type, ['Vehicle', 'Car'], true)) {
                    return true;
                }
            }
            return false;
        }

        return in_array($types, ['Vehicle', 'Car'], true);
    }

    /**
     * Все разобранные блоки ld+json со страницы.
     *
     * @return array<int, array>
     */
    private static function extractJsonLd(string $html): array
    {
        $pattern = '#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#is';

        if (!preg_match_all($pattern, $html, $matches)) {
            return [];
        }

        $result = [];

        foreach ($matches[1] as $raw) {
            $decoded = json_decode(trim($raw), true);

            if (is_array($decoded)) {
                $result[] = $decoded;
            }
        }

        return $result;
    }

    /**
     * Город из хлебных крошек.
     *
     * Крошки повторяют город одним и тем же способом:
     * /legkovie/city/kiev/ и /car/bmw/city/kiev/.
     * Порядок не важен - годен любой сегмент city/.
     */
    private static function findCity(string $html): string
    {
        $pattern = '#[a-z0-9\-]+/city/([a-z0-9\-]+)/#i';

        if (!preg_match($pattern, $html, $m)) {
            return self::CITY_UNKNOWN;
        }

        return self::prettySlug($m[1]);
    }

    /**
     * «kiev» -> «Киев».
     *
     * Транслитерацию доски не разбираем по таблице - её пришлось бы
     * поддерживать вручную. Отдаём slug как есть: он однозначен и
     * годится для фильтра в настройках.
     */
    private static function prettySlug(string $slug): string
    {
        return $slug;
    }

    /**
     * Замаскированный телефон из payload продавца.
     *
     * В разметке он лежит в JSON-конфиге блока autoPhone как
     * "content":"(068) XXX XX XX". Маска - это сигнал, что номер
     * отдаётся только после клика, а не что номера нет.
     */
    private static function findPhoneMask(string $html): string
    {
        // сначала вариант внутри экранированного JSON
        if (preg_match('#"phone"\s*:\s*\{[^}]*?"content"\s*:\s*"([^"]+)"#s', $html, $m)) {
            return trim($m[1]);
        }

        if (preg_match('#"content"\s*:\s*"(\(?\d{2,3}\)?[^"]*X[^"]*)"#i', $html, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Имя продавца.
     *
     * В payload оно встречается в двух видах: как объект
     * "params":{"userName":"Kiev Autotrade"} и как пара в массиве
     * "data":[["userName","Kiev Autotrade"]]. Раньше был разобран
     * только первый вид, и продавец молча терялся.
     */
    private static function findSellerName(string $html): string
    {
        // второй вид: пара в массиве data
        if (preg_match('#\["userName"\s*,\s*"([^"]+)"\]#', $html, $m)) {
            return trim($m[1]);
        }

        // первый вид: обычное поле объекта
        if (preg_match('#"userName"\s*:\s*"([^"]+)"#', $html, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Идентификатор объявления из @id - последние цифры адреса.
     */
    private static function buildUrlFromId(array $vehicle): string
    {
        $id = (string)($vehicle['@id'] ?? '');
        return $id === '' ? '' : $id;
    }

    /**
     * Имя из объекта вида {"@type":"Brand","name":"BMW"}.
     */
    private static function nestedName($value): string
    {
        if (is_array($value)) {
            return trim((string)($value['name'] ?? ''));
        }

        return trim((string)$value);
    }

    /**
     * Привести к int, бухгалтерский ноль - это отсутствие значения.
     */
    private static function toInt($value): ?int
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}