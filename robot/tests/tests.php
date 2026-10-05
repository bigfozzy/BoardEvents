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
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsVehicleData.php';
require_once __DIR__ . '/RealPageFixture.php';

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

// --- Разбор реальной разметки объявления ------------------------------------------
//
// Дальше идут проверки на фикстуре, снятой с живой доски. Они и есть
// главная защита парсера: пока разбор не работает на настоящей разметке,
// он не считается работающим.

$data = AutoriaListingsVehicleData::fromPageSource(RealPageFixture::html());

check('Vehicle-блок разобран', $data !== null, true);
check('адрес', $data['url'] ?? '', 'https://auto.ria.com/auto_bmw_x5_40521845.html');
check('заголовок', $data['title'] ?? '', 'BMW X5 2023');
check('марка', $data['brand'] ?? '', 'BMW');
check('модель', $data['model'] ?? '', 'X5');
check('год', $data['year'] ?? null, 2023);
check('VIN', $data['vin'] ?? '', '5UX33EU06R9T12665');
check('пробег', $data['mileage'] ?? null, 56000);
check('кузов', $data['bodyType'] ?? '', 'Легковые');
check('цвет', $data['color'] ?? '', 'Черный');
check('топливо', $data['fuelType'] ?? '', 'Бензин');
check('коробка', $data['transmission'] ?? '', 'Автомат');
check('двери', $data['doors'] ?? null, 5);
check('цена', $data['price'] ?? null, 97900);
check('валюта', $data['currency'] ?? '', 'USD');
check('город из крошек', $data['city'] ?? '', 'kiev');
check('продавец', $data['sellerName'] ?? '', 'Kiev Autotrade');

// Именно этот телефон и был причиной, по которой прежний парсер
// не работал: номера в разметке нет, есть маска.
check('телефон замаскирован', $data['phoneMasked'] ?? '', '(068) XXX XX XX');

// Второй блок Vehicle на странице содержит только картинки. Если бы
// парсер брал его, вместо характеристик пришли бы нули.
check('взят блок с данными, а не с картинками', ($data['price'] ?? null) === 97900, true);

// Чего на доске нет - и это обязано быть видно в тестах, иначе
// кто-то снова напишет парсер, ожидающий этих данных.
check('даты публикации в разметке нет', $data['postedDate'] ?? 'нет', 'нет');
check('текста "Объявление добавлено" больше нет', str_contains(RealPageFixture::html(), 'Объявление добавлено'), false);
check('класса phone-wrap больше нет', str_contains(RealPageFixture::html(), 'phone-wrap'), false);
check('класса mainlink больше нет', str_contains(RealPageFixture::html(), 'mainlink'), false);

// Пустая и битая страница не должны ронять разбор.
check('пустая страница', AutoriaListingsVehicleData::fromPageSource(''), null);
check('страница без разметки', AutoriaListingsVehicleData::fromPageSource('<html><body>привет</body></html>'), null);
check('битый JSON не считается данными',
    AutoriaListingsVehicleData::fromPageSource('<script type="application/ld+json">{ битое</script>'), null);

// --- Какие ссылки считаются объявлениями ------------------------------------------
//
// На странице выдачи рядом с объявлениями лежит навигация доски.
// Прежний фильтр '/car/' отбирал именно её, и список выходил пустым
// либо состоял из разделов каталога.

check('объявление X5', AutoriaListingsScraper::isListingPath('/auto_bmw_x5_40521845.html'), true);
check('объявление 3-series', AutoriaListingsScraper::isListingPath('/auto_bmw_3-series_40459290.html'), true);
check('объявление с дефисом', AutoriaListingsScraper::isListingPath('/auto_audi-a4_123456.html'), true);

// это всё пункты меню, а не объявления
check('раздел марок', AutoriaListingsScraper::isListingPath('/car/bmw/'), false);
check('раздел модели', AutoriaListingsScraper::isListingPath('/car/bmw/x5/'), false);
check('подержанные', AutoriaListingsScraper::isListingPath('/car/used/'), false);
check('главная', AutoriaListingsScraper::isListingPath('/'), false);
check('логин', AutoriaListingsScraper::isListingPath('/login.html'), false);
check('notepad', AutoriaListingsScraper::isListingPath('/notepad.html'), false);

// --- Ключ дедупликации -------------------------------------------------------------

$item = new AutoriaListingItem('https://auto.ria.com/car/abc-123/?utm_source=x');
check('ключ без меток', $item->key(), '/car/abc-123');
check('тот же ключ у зеркала', (new AutoriaListingItem('https://auto.ria.com/car/abc-123'))->key(), '/car/abc-123');
check('разные машины', (new AutoriaListingItem('https://auto.ria.com/car/zzz/'))->key(), '/car/zzz');

// id объявления стоит в адресе и в data-car-id карточки
check('id из адреса', (new AutoriaListingItem('https://auto.ria.com/auto_bmw_x5_40521845.html'))->carId(), '40521845');
check('id с метками', (new AutoriaListingItem('https://auto.ria.com/auto_bmw_x5_40521845.html?a=1'))->carId(), '40521845');
check('id нет', (new AutoriaListingItem('https://auto.ria.com/car/bmw/x5/'))->carId(), '');

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

// Порядок полей в toRow() обязан совпадать с заголовками слайса,
// иначе url уедет в колонку title. Заголовки лежат в классе-писателе,
// который лежит в core/, и подключить его здесь нельзя без XHE,
// поэтому сверяем с ожидаемым списком.
$item2 = AutoriaListingItem::fromVehicleData($data);
$row = $item2->toRow();

$expectedHeaders = [
    'url', 'title', 'price', 'currency', 'mileage_km', 'city', 'brand', 'model',
    'year', 'vin', 'body_type', 'color', 'fuel', 'transmission',
    'phone', 'phone_masked', 'seller',
];

check('колонок семнадцать', count($row), count($expectedHeaders));
check('колонки по числу', count($row), count($expectedHeaders));

check('колонка url', $row[0], 'https://auto.ria.com/auto_bmw_x5_40521845.html');
check('колонка title', $row[1], 'BMW X5 2023');
check('колонка price', $row[2], '97900');
check('колонка currency', $row[3], 'USD');
check('колонка mileage', $row[4], '56000');
check('колонка city', $row[5], 'kiev');
check('колонка brand', $row[6], 'BMW');
check('колонка model', $row[7], 'X5');
check('колонка year', $row[8], '2023');
check('колонка vin', $row[9], '5UX33EU06R9T12665');
check('колонка body_type', $row[10], 'Легковые');
check('колонка color', $row[11], 'Черный');
check('колонка fuel', $row[12], 'Бензин');
check('колонка transmission', $row[13], 'Автомат');

// маска не должна попасть в колонку phone - по ней нельзя позвонить,
// это отдельная колонка phone_masked
check('в phone нет маски', $row[14], '');
check('маска в своей колонке', $row[15], '(068) XXX XX XX');
check('колонка seller', $row[16], 'Kiev Autotrade');

check('звонить нечем', $item2->hasCallablePhone(), false);
check('маска видна', $item2->hasPhoneMask(), true);

// Сверяем, что заголовки писателя совпадают с ожидаемыми.
// Класс AutoriaListingsSink лежит в слайсе, но тянет за собой
// порт SpreadsheetWriter из core/, поэтому сравниваем текстом.
$sink = file_get_contents(__DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSink.php');
foreach ($expectedHeaders as $header) {
    check("заголовок '$header' есть в писателе",
        str_contains($sink, "'" . $header . "'"), true);
}

// --- Итог --------------------------------------------------------------------------

echo "\n";
foreach ($GLOBALS['failed'] as $failure) {
    echo "FAIL: $failure\n\n";
}

printf("%d/%d проверок пройдено\n", $GLOBALS['passed'], $GLOBALS['passed'] + count($GLOBALS['failed']));

exit(count($GLOBALS['failed']) > 0 ? 1 : 0);