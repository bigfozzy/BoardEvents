<?php

/**
 * Правила, общие для всех досок. Браузера здесь нет - поэтому файл
 * проверяется обычным PHP без запущенной Studio:
 *
 *   php tests/tests.php
 *
 * Правила перенесены из C#-версии (legacy-csharp/tools) и покрыты
 * теми же проверками. Меняете правило - меняйте здесь и в тестах.
 */
class BoardRules
{
    /**
     * Тип доски по адресу.
     *
     * olx.com и olx.ua - один тип: это одна доска, просто зеркала.
     *
     * @return string одна из констант TYPE_* либо 'unknown'
     */
    public static function getTypeByUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return 'unknown';
        }

        $url = mb_strtolower($url);

        if (str_contains($url, 'auto.ria.com') || str_contains($url, 'autoria.com')) {
            return self::TYPE_AUTORIA;
        }
        if (str_contains($url, 'olx.ua') || str_contains($url, 'olx.com')) {
            return self::TYPE_OLX;
        }
        if (str_contains($url, 'rst.ua')) {
            return self::TYPE_RST;
        }

        return 'unknown';
    }

    public const TYPE_AUTORIA = 'autoria.com';
    public const TYPE_OLX = 'olx.ua';
    public const TYPE_RST = 'rst.ua';

    /**
     * Поддерживается ли доска.
     *
     * null раньше ронял метод на IndexOf - здесь просто false.
     */
    public static function isUrlOfType(?string $url, string $type): bool
    {
        return self::getTypeByUrl($url) === $type;
    }

    /**
     * Нормализованный телефон либо пустая строка.
     *
     * Звонить имеет смысл только по +7 и +38:
     *  - начинается с 0 - украина, добавляем 38;
     *  - начинается с 8 - российский междугородний, 8 МЕНЯЕТСЯ на 7
     *    (8 (912) 345-67-89 это +79123456789, а не +8...);
     *  - начинается с 7 или 38 - добавляем плюс;
     *  - всё остальное пустая строка, звонить нельзя.
     *
     * Пустой результат - это сигнал "не звонить", а не ошибка.
     */
    public static function getNormedPhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $phone);

        // цифр не оказалось (например, телефон не распознан на доске)
        if ($digits === null || $digits === '') {
            return '';
        }

        // украина
        if ($digits[0] === '0') {
            return self::isCallable('+38' . $digits) ? '+38' . $digits : '';
        }

        // российский междугородний префикс 8
        if ($digits[0] === '8' && strlen($digits) > 1) {
            $converted = '+7' . substr($digits, 1);
            return self::isCallable($converted) ? $converted : '';
        }

        if ($digits[0] === '7' || str_starts_with($digits, '38')) {
            $converted = '+' . $digits;
            return self::isCallable($converted) ? $converted : '';
        }

        return '';
    }

    /**
     * Звонок уходит только по российскому и украинскому коду.
     * Остальное не набираем: иначе робот может позвонить не туда.
     */
    private static function isCallable(string $phone): bool
    {
        return str_starts_with($phone, '+7') || str_starts_with($phone, '+38');
    }

    /**
     * Интервал проверки в минутах по строке из настроек.
     *
     * Строки обязаны совпадать со списком в интерфейсе. В C#-версии
     * соответствие жило прямо в StartScheduling, и строка
     * "раз 10 часов" разошлась с "раз в 10 часов" из интерфейса -
     * интервал молча не работал.
     *
     * @return int минуты, либо -1 если строка не распознана
     */
    public static function getIntervalMinutes(?string $timeCheck): int
    {
        switch ($timeCheck) {
            case 'раз в минуту':   return 1;
            case 'раз в 3 минуты': return 3;
            case 'раз в 5 минут':  return 5;
            case 'раз в 10 минут': return 10;
            case 'раз в 15 минут': return 15;
            case 'раз в 20 минут': return 20;
            case 'раз в 30 минут': return 30;
            case 'раз в час':      return 60;
            case 'раз в 2 часа':   return 120;
            case 'раз в 3 часа':   return 180;
            case 'раз в 4 часа':   return 240;
            case 'раз в 5 часов':  return 300;
            case 'раз в 10 часов': return 600;
            case 'раз в 12 часов': return 720;
            case 'раз в сутки':    return 1440;
            case 'раз в неделю':   return 10080;
        }

        return -1;
    }

    /**
     * Добавлять ли объявление в результат.
     *
     * $onlyNew - настройка "только новые". Если дату объявления
     * распарсить не удалось, $postedDate пуст: такое объявление
     * добавляется. Неизвестная дата - не повод потерять объявление.
     * В C#-версии пустая дата считалась "старой" и молча отсекалась.
     *
     * @param string|null $postedDate Y-m-d
     * @param string $taskCreatedAt Y-m-d, дата создания задачи
     */
    public static function shouldAccept(
        ?string $postedDate,
        string $taskCreatedAt,
        bool $onlyNew
    ): bool {
        if (!$onlyNew) {
            return true;
        }

        // дату не распознали - пропускаем объявление, а не теряем его
        if ($postedDate === null || $postedDate === '') {
            return true;
        }

        $posted = strtotime($postedDate);
        $created = strtotime($taskCreatedAt);

        if ($posted === false || $created === false) {
            return true;
        }

        // сравниваем по суткам, а не по секундам: объявление, размещённое
        // сегодня в 00:10, при задаче от сегодняшнего утра - новое
        return strtotime(date('Y-m-d', $posted)) >= strtotime(date('Y-m-d', $created));
    }

    /**
     * Экранирование значения для CSV.
     *
     * Кавычки внутри значения удваиваются, иначе файл разъезжается.
     */
    public static function csvEscape(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Экранирование для HTML.
     *
     * В письма попадают данные, спарсенные со сторонних досок:
     * без экранирования это HTML-инъекция в письме.
     */
    public static function htmlEscape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Ссылка на телефон в письме: tel: с плюсом.
     * Чужой код превратится в пустую строку - звонить по нему нельзя.
     */
    public static function phoneHref(?string $phone): string
    {
        $normed = self::getNormedPhone($phone);
        return $normed === '' ? '' : 'tel:' . self::htmlEscape($normed);
    }
}