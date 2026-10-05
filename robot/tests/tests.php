<?php

/**
 * Проверки чистых правил: браузер не нужен, Studio не нужна.
 *
 * Запуск:  php tests/tests.php
 *
 * Рамка намеренно своя, без PHPUnit: у покупателя на машине нет
 * composer, а проверки должны запускаться тем же PHP, что и робот.
 *
 * Файлы правил и дат лежат в tools/slices/autoria_listings/ и не
 * зависят от XHE - их можно читать и проверять без платформы.
 */

require_once __DIR__ . '/../tools/slices/autoria_listings/BoardRules.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingItem.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsScraper.php';

$GLOBALS['passed'] = 0;
$GLOBALS['failed'] = [];

function check(string $name, $actual, $expected): void
{
    if ($actual === $expected) {
        $GLOBALS['passed']++;
        return;
    }

    $GLOBALS['failed'][] = sprintf(
        "%s\n    ожидалось: %s\n    получено : %s",
        $name,
        var_export($expected, true),
        var_export($actual, true)
    );
}

// --- Тип доски по адресу -----------------------------------------------------------

check('auto.ria.com', BoardRules::getTypeByUrl('https://auto.ria.com/ukraine/bmw/'), 'autoria.com');
check('autoria.com', BoardRules::getTypeByUrl('https://autoria.com/ukraine/'), 'autoria.com');
check('olx.ua', BoardRules::getTypeByUrl('https://www.olx.ua/transport/'), 'olx.ua');
check('olx.com идёт в olx.ua', BoardRules::getTypeByUrl('https://www.olx.com/'), 'olx.ua');
check('rst.ua', BoardRules::getTypeByUrl('https://rst.ua/'), 'rst.ua');
check('чужая доска', BoardRules::getTypeByUrl('https://example.com/'), 'unknown');
// null раньше ронял метод на IndexOf
check('null не падает', BoardRules::getTypeByUrl(null), 'unknown');
check('пустая строка', BoardRules::getTypeByUrl(''), 'unknown');
check('регистр не важен', BoardRules::getTypeByUrl('https://AUTO.RIA.COM/ukraine/'), 'autoria.com');

check('свой адрес', BoardRules::isUrlOfType('https://auto.ria.com/car/123/', 'autoria.com'), true);
check('чужой адрес', BoardRules::isUrlOfType('https://www.olx.ua/transport/', 'autoria.com'), false);
check('null не свой', BoardRules::isUrlOfType(null, 'autoria.com'), false);

// --- Нормализация телефона ---------------------------------------------------------

check('россия с восьмёркой', BoardRules::getNormedPhone('8 (912) 345-67-89'), '+79123456789');
check('россия с семёркой', BoardRules::getNormedPhone('+7 912 345 67 89'), '+79123456789');
check('россия без префикса', BoardRules::getNormedPhone('9123456789'), '');
check('украина 0', BoardRules::getNormedPhone('063 123 45 67'), '+380631234567');
check('украина 38', BoardRules::getNormedPhone('+38 063 123 45 67'), '+380631234567');
check('узбекистан не звоним', BoardRules::getNormedPhone('+998 90 123 45 67'), '');
check('польша не звоним', BoardRules::getNormedPhone('+48 601 234 567'), '');
check('пустой телефон', BoardRules::getNormedPhone(''), '');
check('null телефон', BoardRules::getNormedPhone(null), '');
check('только скобки', BoardRules::getNormedPhone('()'), '');
check('одна восьмёрка', BoardRules::getNormedPhone('8'), '');
check('телефон не распознан', BoardRules::getNormedPhone('не определён'), '');
check('много пробелов', BoardRules::getNormedPhone('  8   912  345 67 89 '), '+79123456789');

// --- Интервалы --------------------------------------------------------------------

check('каждую минуту', BoardRules::getIntervalMinutes('раз в минуту'), 1);
check('10 минут', BoardRules::getIntervalMinutes('раз в 10 минут'), 10);
check('час', BoardRules::getIntervalMinutes('раз в час'), 60);
check('10 часов', BoardRules::getIntervalMinutes('раз в 10 часов'), 600);
check('сутки', BoardRules::getIntervalMinutes('раз в сутки'), 1440);
check('неделя', BoardRules::getIntervalMinutes('раз в неделю'), 10080);
// именно эта строка разошлась с интерфейсом в C# и интервал не работал
check('10 часов без "в"', BoardRules::getIntervalMinutes('раз 10 часов'), -1);
check('неизвестная строка', BoardRules::getIntervalMinutes('через полчаса'), -1);
check('null', BoardRules::getIntervalMinutes(null), -1);

// --- Отсев по дате -----------------------------------------------------------------

$taskCreated = '2026-10-01';

check('новое с фильтром', BoardRules::shouldAccept('2026-10-03', $taskCreated, true), true);
check('старое с фильтром', BoardRules::shouldAccept('2026-09-01', $taskCreated, true), false);
check('старое без фильтра', BoardRules::shouldAccept('2026-09-01', $taskCreated, false), true);
// сравнение по суткам: объявление от сегодняшнего утра при задаче от сегодня
check('тот же день', BoardRules::shouldAccept('2026-10-01', $taskCreated, true), true);
// БЕЗ ДАТЫ объявление принимается: неизвестная дата - не повод его потерять.
// В C# пустая дата считалась старой и молча отсекалась.
check('без даты, фильтр вкл', BoardRules::shouldAccept('', $taskCreated, true), true);
check('null дата, фильтр вкл', BoardRules::shouldAccept(null, $taskCreated, true), true);
check('ерунда в дате', BoardRules::shouldAccept('позавчера вечером', $taskCreated, true), true);

// --- Разбор даты с доски ----------------------------------------------------------

check('сегодня', AutoriaListingsScraper::parseHumanDate('сегодня в 09:15'), date('Y-m-d'));
check('час назад', AutoriaListingsScraper::parseHumanDate('час назад'), date('Y-m-d'));
check('вчера', AutoriaListingsScraper::parseHumanDate('вчера в 18:00'), date('Y-m-d', strtotime('-1 day')));
check('2 дня назад', AutoriaListingsScraper::parseHumanDate('2 дня назад'), date('Y-m-d', strtotime('-2 days')));
check('3 недели назад', AutoriaListingsScraper::parseHumanDate('3 недели назад'), date('Y-m-d', strtotime('-21 days')));
check('пустая дата', AutoriaListingsScraper::parseHumanDate(''), null);
check('непонятная дата', AutoriaListingsScraper::parseHumanDate('когда-то'), null);

$october = AutoriaListingsScraper::parseHumanDate('12 октября');
check('абсолютная дата разбирается', $october !== null && str_ends_with($october, '-10-12'), true);

// месяц позже текущего - это прошлый год
$future = AutoriaListingsScraper::parseHumanDate('31 декабря');
$expectedYear = (int)date('Y');
if (12 > (int)date('n')) {
    $expectedYear--;
}
check('будущий месяц = прошлый год', $future, date('Y-m-d', mktime(0, 0, 0, 12, 31, $expectedYear)));

// --- Ключ дедупликации -------------------------------------------------------------

$item = new AutoriaListingItem('https://auto.ria.com/car/abc-123/?utm_source=x');
check('ключ без меток', $item->key(), '/car/abc-123');
check('тот же ключ у зеркала', (new AutoriaListingItem('https://auto.ria.com/car/abc-123'))->key(), '/car/abc-123');
check('разные машины', (new AutoriaListingItem('https://auto.ria.com/car/zzz/'))->key(), '/car/zzz');

// --- Экранирование ----------------------------------------------------------------

check('CSV с запятой', BoardRules::csvEscape('привет, мир'), '"привет, мир"');
check('CSV с кавычками', BoardRules::csvEscape('он сказал "да"'), '"он сказал ""да"""');
check('CSV с переводом строки', BoardRules::csvEscape("строка\nвторая"), "\"строка\nвторая\"");
check('CSV пусто', BoardRules::csvEscape(''), '""');

check('HTML теги', BoardRules::htmlEscape('<b>жирно</b>'), '&lt;b&gt;жирно&lt;/b&gt;');
check('HTML кавычки', BoardRules::htmlEscape('a"b'), 'a&quot;b');
check('HTML amp', BoardRules::htmlEscape('a&b'), 'a&amp;b');
check('HTML null', BoardRules::htmlEscape(null), '');

check('tel для RU', BoardRules::phoneHref('8 (912) 345-67-89'), 'tel:+79123456789');
check('tel для UA', BoardRules::phoneHref('063 123 45 67'), 'tel:+380631234567');
// чужой код не должен превратиться в tel:, иначе письмо предложит позвонить не туда
check('tel для чужой страны пуст', BoardRules::phoneHref('+48 601 234 567'), '');

// --- Согласованность колонок ------------------------------------------------------

// порядок полей в toRow() обязан совпадать с заголовками слайса,
// иначе url уедет в колонку title
$item2 = new AutoriaListingItem(
    'https://auto.ria.com/car/x/',
    'BMW X5',
    '25 000 $',
    'Киев',
    '8 (912) 345-67-89',
    '2026-10-05',
    'сегодня'
);
$row = $item2->toRow();
check('колонок шесть', count($row), 6);
check('колонка 1 адрес', $row[0], 'https://auto.ria.com/car/x/');
check('колонка 2 заголовок', $row[1], 'BMW X5');
check('колонка 3 цена', $row[2], '25 000 $');
check('колонка 4 город', $row[3], 'Киев');
check('колонка 5 телефон нормализован', $row[4], '+79123456789');
check('колонка 6 дата', $row[5], '2026-10-05');
check('сырой телефон не попал в строку', in_array('8 (912) 345-67-89', $row, true), false);

// --- Итог --------------------------------------------------------------------------

echo "\n";
foreach ($GLOBALS['failed'] as $failure) {
    echo "FAIL: $failure\n\n";
}

printf("%d/%d проверок пройдено\n", $GLOBALS['passed'], $GLOBALS['passed'] + count($GLOBALS['failed']));

exit(count($GLOBALS['failed']) > 0 ? 1 : 0);