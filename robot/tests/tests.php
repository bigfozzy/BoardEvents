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
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSink.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsState.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSelection.php';
require_once __DIR__ . '/../tools/slices/schedule_setup/ScheduleIntervals.php';
require_once __DIR__ . '/../tools/slices/schedule_setup/RobotSchedule.php';
require_once __DIR__ . '/RealPageFixture.php';

// Адаптер записи подключаем напрямую, а не через tools/robotInit.php:
// тот требует core/Settings.php и core/Tools.php, которые поднимают
// настройки и обращаются к запущенной Studio. Здесь нужна только
// запись файла - она от XHE не зависит (фасады в адаптере есть только
// в ветках ошибок).
require_once __DIR__ . '/../tools/core/contracts/SpreadsheetWriter.php';
require_once __DIR__ . '/../tools/core/helpers/CsvHelper.php';
require_once __DIR__ . '/../tools/core/adapters/CsvSpreadsheetWriter.php';

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

// Неразобранное объявление не должно попадать в отчёт строкой из одних
// пустых ячеек и при этом не должно помечаться просмотренным - иначе
// после починки парсера оно исчезнет навсегда. Проверяем по коду:
// fillDetails обязан возвращать null, а слайс - считать и не отмечать.
$sliceSrc = file_get_contents(
    __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSlice.php'
);
check('слайс обрабатывает неудачный разбор',
    str_contains($sliceSrc, '$filled === null'), true);
check('неудачный разбор считается',
    str_contains($sliceSrc, '$parseFailed++'), true);
check('неудачный разбор не отмечается просмотренным',
    str_contains($sliceSrc, 'НЕ отмечается'), true);

// сигнатура fillDetails должна допускать null
$scraperMethod = new ReflectionMethod('AutoriaListingsScraper', 'fillDetails');
$returnType = $scraperMethod->getReturnType();
check('fillDetails может вернуть null',
    $returnType !== null && $returnType->allowsNull(), true);

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

// --- Колонки выгрузки ---------------------------------------------------------------

// Раньше строка собиралась позициями, и порядок полей обязан был
// совпадать с порядком заголовков: переставил поле - значение уехало в
// соседнюю колонку. Теперь писатель выбирает колонки по именам.

$item2 = AutoriaListingItem::fromVehicleData($data);
$record = $item2->toRecord();

$headersNoPhone = AutoriaListingsSink::headers(false);
$headersPhone = AutoriaListingsSink::headers(true);

check('без телефонов колонок семнадцать', count($headersNoPhone), 17);
check('с телефонами колонок девятнадцать', count($headersPhone), 19);

// без согласия RIA телефонных колонок в файле нет вообще: пустая
// колонка «телефон» выглядит как «собирали и не нашли»
check('колонки телефона нет', in_array('phone', $headersNoPhone, true), false);
check('колонки маски нет', in_array('phone_masked', $headersNoPhone, true), false);

// отчёт накапливается, поэтому первые две колонки - кто нашёл и когда.
// без них в списке нельзя отличить сегодняшнее от позавчерашнего
check('первая колонка - задача', $headersNoPhone[0], 'board');
check('вторая колонка - время находки', $headersNoPhone[1], 'found_at');

// с согласием - телефоны стоят перед продавцом
check('порядок с телефонами', array_slice($headersPhone, 16, 3), ['phone', 'phone_masked', 'seller']);
check('продавец последний', $headersPhone[18], 'seller');

// каждая колонка файла должна быть и в записи объекта, иначе в строке
// окажется пустая ячейка вместо данных. board и found_at исключены:
// их проставляет писатель в момент записи, у объявления таких полей нет
$runLevelColumns = ['board', 'found_at'];
foreach ($headersPhone as $header) {
    if (in_array($header, $runLevelColumns, true)) {
        continue;
    }
    check("колонка '$header' есть в записи", array_key_exists($header, $record), true);
}
check('у объявления нет полей прогона', array_key_exists('found_at', $record), false);

// значения живого объявления на своих местах
check('url', $record['url'], 'https://auto.ria.com/auto_bmw_x5_40521845.html');
check('title', $record['title'], 'BMW X5 2023');
check('price', $record['price'], '97900');
check('currency', $record['currency'], 'USD');
check('mileage', $record['mileage_km'], '56000');
check('city', $record['city'], 'kiev');
check('brand', $record['brand'], 'BMW');
check('model', $record['model'], 'X5');
check('year', $record['year'], '2023');
check('vin', $record['vin'], '5UX33EU06R9T12665');
check('body_type', $record['body_type'], 'Легковые');
check('color', $record['color'], 'Черный');
check('fuel', $record['fuel'], 'Бензин');
check('transmission', $record['transmission'], 'Автомат');
check('seller', $record['seller'], 'Kiev Autotrade');

// маска не должна попасть в колонку phone - по ней нельзя позвонить
check('в phone нет маски', $record['phone'], '');
check('маска в своей колонке', $record['phone_masked'], '(068) XXX XX XX');
check('звонить нечем', $item2->hasCallablePhone(), false);
check('маска видна', $item2->hasPhoneMask(), true);

// порядок полей в записи не должен влиять на выгрузку - это и есть
// причина, по которой запись именованная
$reordered = array_reverse($record, true);
$rowFromReordered = [];
foreach ($headersNoPhone as $h) { $rowFromReordered[] = (string)($reordered[$h] ?? ''); }
$rowFromOrdered = [];
foreach ($headersNoPhone as $h) { $rowFromOrdered[] = (string)($record[$h] ?? ''); }
check('перестановка полей не меняет строку', $rowFromReordered, $rowFromOrdered);

// --- Раскрытие телефона ------------------------------------------------------------
//
// Тексты ниже сняты из реальных всплывающих окон auto.ria после клика
// по кнопке с маской (6 объявлений, 5 раскрылись). Разбор должен
// брать именно номер, а не что-нибудь соседнее вроде «406 отзывов».

$dealerPopup = "BMW X5 2023\nКомпанія\nKiev Autotrade\nОткрывается завтра в 10:00\n"
    . "Авторизован через Дію\n(068) 302 77 77\nПопросить продавца перезвонить\n"
    . "Перезвоните мне\nНаписать в чат\nОцените продавца";
$privatePopup = "BMW X3 2015\nПродавець\nАлександр Никиша\n(050) 689 98 28\n"
    . "Попросить продавца перезвонить\nПерезвоните мне\nНаписать в чат\nОцените продавца";

check('номер дилера из окна', BoardRules::findPhoneInText($dealerPopup), '+380683027777');
check('номер частника из окна', BoardRules::findPhoneInText($privatePopup), '+380506899828');

// в окне бывают посторонние числа - год, счётчик отзывов
$noisy = "BMW X5 2023\n2018\n406 отзывов\n(050) 689 98 28\n51 предложение продавца";
check('шумные числа не мешают', BoardRules::findPhoneInText($noisy), '+380506899828');

// маска - не номер
check('из одной маски номера нет', BoardRules::findPhoneInText("(068) XXX XX XX"), '');
check('маска с другими словами', BoardRules::findPhoneInText("Продавець\n(068) XXX XX XX"), '');

// окно без номера: требуется вход или номер не показан
check('окно без номера', BoardRules::findPhoneInText("Продавець\nВойдите, чтобы увидеть номер"), '');
check('пустое окно', BoardRules::findPhoneInText(''), '');

// чужой код в окне не должен становиться номером для звонка
check('чужой код не берётся', BoardRules::findPhoneInText("Продавець\n+48 601 234 567"), '');
check('короткий номер не берётся', BoardRules::findPhoneInText("Продавець\n12345"), '');

// --- Нумерация задач планировщика --------------------------------------------------
//
// Номера в планировщике Studio начинаются с НУЛЯ - это прямо написано в
// образцах вендора (delete.php: «numbering starts from zero», edit.php
// берёт задачу с индексом 0). Обход с единицы пропускал задачу №0: робот
// считал бы, что своей задачи нет, и добавлял новую при каждом запуске.
// Через неделю задач десятки, и доска проверяется во столько раз чаще,
// сколько было запусков.

check('нет задач - нет номеров', RobotSchedule::taskIndexes(0), []);
check('отрицательное количество - тоже пусто', RobotSchedule::taskIndexes(-3), []);
check('одна задача имеет номер 0', RobotSchedule::taskIndexes(1), [0]);
check('две задачи: 0 и 1', RobotSchedule::taskIndexes(2), [0, 1]);
check('пять задач: от 0 до 4', RobotSchedule::taskIndexes(5), [0, 1, 2, 3, 4]);
check('задача №0 попадает в обход', in_array(0, RobotSchedule::taskIndexes(3), true), true);
check('номера не выходят за количество',
    in_array(3, RobotSchedule::taskIndexes(3), true), false);

// обход не должен начинаться с единицы - это и была ошибка
$scheduleSrc = file_get_contents(
    __DIR__ . '/../tools/slices/schedule_setup/RobotSchedule.php'
);
check('в коде нет обхода с единицы', str_contains($scheduleSrc, 'for ($num = 1;'), false);

// --- Проверка времени запуска ------------------------------------------------------
//
// add() внутри делает date_parse() и смотрит только факт разбора, а не
// ошибки в нём: мусор прошёл бы молча и превратился в нули.

check('время 09:00 принимается', RobotSchedule::validateTime('09:00'), '09:00:00');
check('время 23:59 принимается', RobotSchedule::validateTime('23:59'), '23:59:00');
check('минуты без ведущих нулей', RobotSchedule::validateTime('9:05'), '09:05:00');
check('пробелы обрезаются', RobotSchedule::validateTime('  07:30  '), '07:30:00');
check('мусор заменяется на безопасное значение', RobotSchedule::validateTime('завтра'), '09:00:00');
check('пустая строка', RobotSchedule::validateTime(''), '09:00:00');
check('часов 25 быть не может', RobotSchedule::validateTime('25:00'), '09:00:00');
check('минут 70 быть не может', RobotSchedule::validateTime('10:70'), '09:00:00');

// --- Проверка связи со Studio --------------------------------------------------------
//
// Обращения к XHE - это HTTP к запущенной Studio. Без неё каждый вызов
// возвращает "PHP not connected to Application" вместе с адресом команды,
// и покупатель видит простыню вместо одного понятного сообщения. Проверка
// обязана идти до любого обращения к API, включая логинатор.

$robotSrc = file_get_contents(__DIR__ . '/../tools/Robot.php');
$checkPos = strpos($robotSrc, '$this->checkStudio()');
$firstApiPos = strpos($robotSrc, 'TOOLS::$passport->getRobotVersion()');
$browserPos = strpos($robotSrc, 'WEB::$browser->set_default_download');

check('проверка связи есть', $checkPos !== false, true);
check('проверка до обращения к паспорту', $checkPos < $firstApiPos, true);
check('проверка до обращения к браузеру', $checkPos < $browserPos, true);
check('в сообщении есть подсказка про Studio',
    str_contains($robotSrc, 'Human Emulator Studio не запущена'), true);
check('в сообщении есть подсказка про запуск из Studio',
    str_contains($robotSrc, 'запустите оттуда') || str_contains($robotSrc, 'запустите из неё'), true);

// --- Контракт настроек -------------------------------------------------------------
//
// Robot.php читает параметры через global. Если кто-то переименует
// переменную в run.php, PHP не пожалуется - значение просто станет
// null, и поведение молча выключится. Так уже было с
// $scheduleRegisterOnRun: без него планировщик просто не настраивался,
// и никто бы об этом не узнал.
//
// Параметр считается заданным, если он есть в run.php, в settings.json
// (оттуда их подставляет SETTINGS::$settings->selfConfigure) или его
// создаёт сам XHE в своём init.php.

$robotGlobals = [];
if (preg_match_all('/global\s+([^\/;]+);/', file_get_contents(__DIR__ . '/../tools/Robot.php'), $gm)) {
    foreach ($gm[1] as $list) {
        foreach (explode(',', $list) as $var) {
            $var = trim($var);
            if (preg_match('/^\$\w+$/', $var)) {
                $robotGlobals[$var] = true;
            }
        }
    }
}

// эти создаёт сам XHE в Templates\init.php, в репозитории их нет
$xheProvided = ['$browser', '$outlook'];

$runSrc = file_get_contents(__DIR__ . '/../run.php');

// в settings.json вендора стоит метка UTF-8, а из-за неё json_decode
// возвращает null и молча ничего не разбирает - снимаем метку заранее
$settingsRaw = file_get_contents(__DIR__ . '/../settings/settings.json');
$settingsRaw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$settingsRaw);
$settingsKeys = array_keys((array)json_decode($settingsRaw, true));

$missing = [];

foreach (array_keys($robotGlobals) as $var) {
    if (in_array($var, $xheProvided, true)) {
        continue;
    }

    $inRun = preg_match('/^\s*' . preg_quote($var, '/') . '\s*=/m', $runSrc) === 1;
    $inSettings = in_array(substr($var, 1), $settingsKeys, true);

    if (!$inRun && !$inSettings) {
        $missing[] = $var;
    }
}

check('все параметры Robot.php откуда-то приходят',
    $missing, []);
check('Robot.php вообще читает параметры', count($robotGlobals) > 20, true);

// ключевые для поведения проверяем отдельно, чтобы причина была видна
foreach ([
    '$autoriaBoards', '$autoriaLimit', '$autoriaPages', '$collectPhones',
    '$dataFolderPath', '$scriptPath', '$scheduleRegisterOnRun',
    '$scheduleInterval', '$scheduleFirstRunTime',
] as $var) {
    check("параметр $var объявлен",
        preg_match('/^\s*' . preg_quote($var, '/') . '\s*=/m', $runSrc) === 1, true);
}

// --- Имя файла из названия задачи --------------------------------------------------

// Имена разных задач не должны совпадать: два фильтра, пишущие в один
// файл, тихо затирали бы друг друга. Проверяем на тех названиях, на
// которых пробная транслитерация всё ломала: кириллица схлопывалась
// в 'board', и задачи «Киев, до 5000» и «Рома» писали бы в один файл.
//
// Robot.php лежит рядом, но обращается к XHE только внутри методов, так
// что класс грузится целиком; приватный метод дёргаем через reflection.
require_once __DIR__ . '/../tools/Robot.php';

$robotFile = new ReflectionClass('Robot');
$fileNameFor = $robotFile->getMethod('fileNameFor');
$fileNameFor->setAccessible(true);
$robotInstance = $robotFile->newInstanceWithoutConstructor();

$names = [
    'auto.ria — BMW',
    'auto.ria — Audi A4, до 10 000 $',
    'Киев, до 5000',
    'Рома',
    'Київ: Audi/Range Rover',
    'BMW///X5',
    '   ',
];

$fileNames = [];
foreach ($names as $boardName) {
    $fileNames[] = $fileNameFor->invoke($robotInstance, $boardName);
}

check('кириллица не схлопывается', in_array('Рома', $fileNames, true), true);
check('название с городом читаемо', in_array('Киев_до_5000', $fileNames, true), true);
check('пустое название даёт запасное имя', in_array('board', $fileNames, true), true);

check('разные названия дают разные файлы', count(array_unique($fileNames)), count($fileNames));

// Windows не терпит эти символы в именах
foreach ($fileNames as $fileName) {
    check("в имени '$fileName' нет запрещённых символов",
        preg_match('/[<>:"|?*\x00-\x1F]/u', $fileName), 0);
    check("имя '$fileName' не длиннее 100", mb_strlen($fileName) <= 100, true);
}

// --- Страницы выдачи ----------------------------------------------------------------

// У auto.ria нет сортировки по новизне, поэтому робот обязан смотреть
// больше одной страницы: иначе новое объявление просто не попадёт в
// первую двадцатку. Адрес страницы строится чисто, это и проверяем.

check('первая страница не меняет адрес',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw', 1), 'https://auto.ria.com/car/bmw');
check('вторая страница добавляет параметр',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw', 2), 'https://auto.ria.com/car/bmw?page=2');
check('третья страница', AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw', 3), 'https://auto.ria.com/car/bmw?page=3');

// самое важное: фильтры покупателя должны уцелеть. Покупатель копирует
// адрес с фильтрами, и потеря query молча расширила бы выборку
check('фильтры не теряются',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw?currency=UAH', 2),
    'https://auto.ria.com/car/bmw?currency=UAH&page=2');
check('несколько фильтров не теряются',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/used/?currency=UAH&pricefrom=1000', 3),
    'https://auto.ria.com/car/used/?currency=UAH&pricefrom=1000&page=3');

// если покупатель уже поставил page в скопированном адресе, он
// заменяется, а не добавляется вторым: ?page=5&page=2 - неопределённость
check('номер страницы заменяется',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw?page=5', 2), 'https://auto.ria.com/car/bmw?page=2');
check('замена сохраняет другие фильтры',
    AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw?currency=UAH&page=5', 3),
    'https://auto.ria.com/car/bmw?currency=UAH&page=3');
check('после замены параметр один',
    substr_count(AutoriaListingsScraper::pageUrl('https://auto.ria.com/car/bmw?page=5', 2), 'page='), 1);

// в настройках должно быть больше одной страницы по умолчанию,
// иначе робот вернётся к пропуску новых объявлений
$runPhp = file_get_contents(__DIR__ . '/../run.php');
check('в run.php задано обход страниц',
    preg_match('/^\$autoriaPages\s*=\s*\d+\s*;/m', $runPhp) === 1, true);
check('страниц по умолчанию больше одной',
    preg_match('/^\$autoriaPages\s*=\s*([2-9]\d*)\s*;/m', $runPhp) === 1, true);

// --- Ожидание карточек перед сбором -----------------------------------------------

// get_all_by_class() элементов не ждёт - он возвращает то, что нашёл на
// момент вызова. wait_js() ждёт только завершения скриптов, а не
// появления карточек в DOM. Без явного ожидания список на медленной
// сети приходил пустым, и робот молча не видел объявлений.

$scraperSrc = file_get_contents(
    __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsScraper.php'
);
check('перед сбором есть ожидание карточки',
    str_contains($scraperSrc, "wait_element_exist_by_attribute('class', self::CARD_CLASS"), true);
check('ожидание идёт до get_all_by_class',
    strpos($scraperSrc, 'wait_element_exist_by_attribute') <
    strpos($scraperSrc, 'get_all_by_class(self::CARD_CLASS'), true);
check('после navigate есть wait_js',
    str_contains($scraperSrc, 'WEB::$browser->wait_js()'), true);

// --- Срок жизни записи состояния ---------------------------------------------------
//
// Запись обрезается по возрасту. Срок был месяц - этого мало: на
// auto.ria машина висит в выдаче месяцами, и через месяц запись
// обрезалась, объявление снова попадало в «новые», и в накопительном
// отчёте появлялся дубль. Держать состояние год недорого: в файл
// попадают только по-настоящему новые объявления.

$ttlDir = sys_get_temp_dir() . '/board-robot-ttl-' . getmypid();
@mkdir($ttlDir, 0777, true);

$oldAd = new AutoriaListingItem('https://auto.ria.com/auto_bmw_x5_40d.html', 'BMW X5');
$stateTtl = new AutoriaListingsState($ttlDir . '/state.json');
$stateTtl->markSeen($oldAd);

// 40 дней при сроке в год объявление должно остаться
$aged40 = $stateTtl->state();
$aged40['seen']['/auto_bmw_x5_40d.html']['seenAt'] = time() - 40 * 86400;
$stateTtl->setState($aged40);
$stateTtl->prune();
check('объявление 40-дневной давности осталось в состоянии',
    isset($stateTtl->state()['seen']['/auto_bmw_x5_40d.html']), true);

// а вот годовой давности уже устарел
$aged366 = $stateTtl->state();
$aged366['seen']['/auto_bmw_x5_40d.html']['seenAt'] = time() - 366 * 86400;
$stateTtl->setState($aged366);
$stateTtl->prune();
check('объявление годовой давности убрано',
    isset($stateTtl->state()['seen']['/auto_bmw_x5_40d.html']), false);

foreach (glob($ttlDir . '/*') ?: [] as $f) { @unlink($f); }
@rmdir($ttlDir);

// --- Сбор телефонов: по умолчанию выключен -----------------------------------------
//
// Условия RIA (п. 1.21 оферты) прямо запрещают автоматический сбор
// номеров телефонов. Сбор остальных данных разрешён. Поэтому выключенным
// это должно быть в run.php, а не «удобной галочкой» - иначе продажа
// робота становится нарушением, причём тихим.

$runPhp = file_get_contents(__DIR__ . '/../run.php');

// значение по умолчанию должно быть false
$defaultOff = preg_match('/^\$collectPhones\s*=\s*false\s*;/m', $runPhp);
check('телефоны по умолчанию выключены', $defaultOff === 1, true);

// и рядом обязано быть объяснение, иначе кто-то включит и не поймёт зачем
$pos = strpos($runPhp, '$collectPhones');
$comment = $pos === false ? '' : substr($runPhp, max(0, $pos - 1600), 1600);
check('у настройки есть предупреждение', str_contains($comment, '1.21'), true);
check('предупреждение упоминает письменное согласие',
    str_contains($comment, 'письменного') || str_contains($comment, 'письменное'), true);

// --- Отбор объявлений: что попадёт в отчёт ----------------------------------------
//
// Это и есть продукт: покупатель платит за то, что сюда попало. Пока
// решение жило вперемешку с навигацией по браузеру, оно не было покрыто
// ни одной проверкой. AutoriaListingsState ходит в фай��, но не в браузер,
// поэтому отбор проверяется целиком.

$selDir = sys_get_temp_dir() . '/board-robot-sel-' . getmypid();
@mkdir($selDir, 0777, true);

$mkItem = static fn (string $path, string $posted = '', string $phone = '')
    => new AutoriaListingItem('https://auto.ria.com' . $path, 'BMW X5', 100, 'USD', 1000,
        'kiev', 'BMW', 'X5', 2020, 'VIN1', 'Кроссовер', 'Черный', 'Бензин', 'Автомат',
        '', $phone, 'Продавец', $posted);

// --- первый запуск: всё новое -------------------------------------------------

$state = new AutoriaListingsState($selDir . '/state.json');
$items = [
    $mkItem('/auto_bmw_x5_1.html'),
    $mkItem('/auto_bmw_x5_2.html'),
    $mkItem('/auto_bmw_x5_3.html'),
];

$r = AutoriaListingsSelection::pick($items, $state, '2026-10-01', true);
check('первый запуск принял все три', count($r['accepted']), 3);
check('уже приходивших ноль', $r['skippedAlreadySeen'], 0);
check('старых по дате ноль', $r['skippedOld'], 0);
check('без даты три', $r['skippedNoDate'], 3);
check('состояние запомнило все три', count($state->state()['seen']), 3);

// --- второй запуск: то же самое показывать нельзя -----------------------------

$r2 = AutoriaListingsSelection::pick($items, $state, '2026-10-01', true);
check('второй запуск не принял ничего', count($r2['accepted']), 0);
check('все три отсеяны как уже приходившие', $r2['skippedAlreadySeen'], 3);
check('пропущено старых по дате', $r2['skippedOld'], 0);

// --- то же объявление под другим адресом --------------------------------------
// метки в ссылке не должны пробивать дедупликацию

$r3 = AutoriaListingsSelection::pick(
    [$mkItem('/auto_bmw_x5_1.html?utm_source=board')],
    $state,
    '2026-10-01',
    true
);
check('объявление с метками не считается новым', count($r3['accepted']), 0);

// --- действительно новое объявление проходит ----------------------------------

$r4 = AutoriaListingsSelection::pick(
    [$mkItem('/auto_bmw_x5_4.html')],
    $state,
    '2026-10-01',
    true
);
check('новое объявление принято', count($r4['accepted']), 1);

// --- фильтр по дате, когда доска её отдаёт -----------------------------------

$state2 = new AutoriaListingsState($selDir . '/state2.json');
$dated = [
    $mkItem('/auto_bmw_x5_old.html', '2026-09-01'),   // старше задачи
    $mkItem('/auto_bmw_x5_new.html', '2026-10-05'),   // новее
    $mkItem('/auto_bmw_x5_none.html', ''),           // даты нет
];
$r5 = AutoriaListingsSelection::pick($dated, $state2, '2026-10-01', true);

$urls = array_map(static fn ($i) => $i->url, $r5['accepted']);
check('старый отсеян, новый и без даты прошли', count($r5['accepted']), 2);
check('старый в счётчике', $r5['skippedOld'], 1);
check('без даты в счётчике', $r5['skippedNoDate'], 1);
check('новое на месте', in_array('https://auto.ria.com/auto_bmw_x5_new.html', $urls, true), true);
check('без даты на месте', in_array('https://auto.ria.com/auto_bmw_x5_none.html', $urls, true), true);
check('старого в отчёте нет', in_array('https://auto.ria.com/auto_bmw_x5_old.html', $urls, true), false);

// выключенный фильтр пропускает всё
$state3 = new AutoriaListingsState($selDir . '/state3.json');
$r6 = AutoriaListingsSelection::pick($dated, $state3, '2026-10-01', false);
check('без фильтра по дате принято всё', count($r6['accepted']), 3);
check('без фильтра никто не отсеян по дате', $r6['skippedOld'], 0);
check('без фильтра счётчик «без даты» пуст', $r6['skippedNoDate'], 0);

// --- пустой результат ----------------------------------------------------------

$state4 = new AutoriaListingsState($selDir . '/state4.json');
$r7 = AutoriaListingsSelection::pick([], $state4, '2026-10-01', true);
check('пустой список даёт пустой отчёт', count($r7['accepted']), 0);

// --- счётчик телефона -----------------------------------------------------------

$state5 = new AutoriaListingsState($selDir . '/state5.json');
$r8 = AutoriaListingsSelection::pick(
    [$mkItem('/auto_bmw_x5_1.html'), $mkItem('/auto_bmw_x5_2.html', '', '8 (912) 345-67-89')],
    $state5,
    '2026-10-01',
    true
);
check('посчитан объявлений без телефона', $r8['withoutCallablePhone'], 1);
check('приняты оба', count($r8['accepted']), 2);

// --- строка счётчиков для лога -------------------------------------------------

$summary = AutoriaListingsSelection::summary($r8);
check('в сводке есть количество принятых', str_contains($summary, 'Принято 2'), true);
check('в сводке есть счётчик без телефона', str_contains($summary, 'без доступного телефона 1'), true);
check('сводка без принятых', str_contains(AutoriaListingsSelection::summary($r7), 'Принято 0'), true);

// --- Отчёт накапливается, а не переписывается -------------------------------------
//
// Раньше заголовок писался при каждом запуске, а он открывает файл на
// запись с обрезкой. Второй прогон стирал всё, найденное в первый: для
// робота с проверкой каждые пять минут это означало файл за последние
// пять минут вместо списка за день. Проверено на живом запуске до правки.

$accPath = sys_get_temp_dir() . '/board-robot-accum-' . getmypid() . '.csv';
@unlink($accPath);

$adOne = new AutoriaListingItem('https://auto.ria.com/auto_bmw_x5_1.html', 'BMW X5', 100, 'USD', 1000, 'kiev');
$adTwo = new AutoriaListingItem('https://auto.ria.com/auto_bmw_x3_2.html', 'BMW X3', 200, 'USD', 2000, 'odessa');

(new AutoriaListingsSink(new CsvSpreadsheetWriter($accPath), $accPath))->write([$adOne, $adTwo]);
$afterFirst = (string)file_get_contents($accPath);

(new AutoriaListingsSink(new CsvSpreadsheetWriter($accPath), $accPath))
    ->write([new AutoriaListingItem('https://auto.ria.com/auto_bmw_x1_3.html', 'BMW X1')]);

$afterSecond = (string)file_get_contents($accPath);
$linesSecond = preg_split('/\r\n|\n/', trim($afterSecond));

check('заголовок в файле один раз', substr_count($afterSecond, 'url;'), 1);
check('объявления первого запуска уцелели', str_contains($afterSecond, 'auto_bmw_x5_1'), true);
check('объявления второго запуска уцелели', str_contains($afterSecond, 'auto_bmw_x3_2'), true);
check('новое объявление второго запуска добавлено', str_contains($afterSecond, 'auto_bmw_x1_3'), true);
check('строк: заголовок плюс три объявления', count(array_filter($linesSecond)), 4);
check('первый запуск дал три строки', count(array_filter(preg_split('/\r\n|\n/', trim($afterFirst)))), 3);

// --- метка UTF-8 для Excel --------------------------------------------------------
//
// Писатель отдаёт UTF-8 без метки, а Excel такие файлы открывает
// догадками. Метка дописывается один раз - при создании отчёта.

check('метка UTF-8 появилась', str_starts_with($afterFirst, "\xEF\xBB\xBF"), true);
check('при добавлении метка не задваивается',
    substr_count($afterSecond, "\xEF\xBB\xBF"), 1);
check('кириллица после метки цела', str_contains($afterSecond, 'Киев') || str_contains($afterSecond, 'odessa'), true);

@unlink($accPath);

foreach (glob($selDir . '/*') ?: [] as $f) { @unlink($f); }
@rmdir($selDir);

// --- Интервалы и планировщик --------------------------------------------------------
//
// Здесь повторяется тот класс бага, что стоил C#-версии: строка
// интервала разошлась с условием в коде, и интервал молча перестал
// работать. Соответствие интервал -> тип задачи проверяется тестом,
// поэтому разойтись ему больше не с чем.

check('минута -> минута', ScheduleIntervals::toMinutes('раз в минуту'), 1);
check('3 минуты', ScheduleIntervals::toMinutes('раз в 3 минуты'), 3);
check('5 минут', ScheduleIntervals::toMinutes('раз в 5 минут'), 5);
check('10 минут', ScheduleIntervals::toMinutes('раз в 10 минут'), 10);
check('30 минут', ScheduleIntervals::toMinutes('раз в 30 минут'), 30);
check('час', ScheduleIntervals::toMinutes('раз в час'), 60);
check('2 часа', ScheduleIntervals::toMinutes('раз в 2 часа'), 120);
check('10 часов', ScheduleIntervals::toMinutes('раз в 10 часов'), 600);
check('сутки', ScheduleIntervals::toMinutes('раз в сутки'), 1440);
check('неделя', ScheduleIntervals::toMinutes('раз в неделю'), 10080);

// ровно та строка, что разошлась с интерфейсом в C#
// и из-за которой интервал не работал вообще
check('10 часов без "в" не распознаётся', ScheduleIntervals::toMinutes('раз 10 часов'), -1);
check('черес полчаса не распознаётся', ScheduleIntervals::toMinutes('через полчаса'), -1);
check('пустая строка', ScheduleIntervals::toMinutes(''), -1);
check('null', ScheduleIntervals::toMinutes(null), -1);

// Список из UI прежней версии должен остаться в том же виде,
// иначе робот перестанет понимать то, что человек уже настроил.
check('в списке 16 интервалов', count(ScheduleIntervals::PRESETS), 16);
check('список отсортирован по времени',
    array_map('ScheduleIntervals::toMinutes', ScheduleIntervals::PRESETS), [1, 3, 5, 10, 15, 20, 30, 60, 120, 180, 240, 300, 600, 720, 1440, 10080]);

// Каждый интервал из списка обязан что-то значить: неизвестная строка
// не должна молча превращаться в 0 минут.
foreach (ScheduleIntervals::PRESETS as $preset) {
    check("интервал '$preset' распознан", ScheduleIntervals::toMinutes($preset) > 0, true);
}

// Типы задач планировщика - из исходников XHE.
check('минута', ScheduleIntervals::toSchedulerType('раз в минуту'), ScheduleIntervals::TYPE_EVERY_MINUTE);
check('5 минут', ScheduleIntervals::toSchedulerType('раз в 5 минут'), ScheduleIntervals::TYPE_EVERY_5_MIN);
check('10 минут', ScheduleIntervals::toSchedulerType('раз в 10 минут'), ScheduleIntervals::TYPE_EVERY_10_MIN);
check('полчаса', ScheduleIntervals::toSchedulerType('раз в 30 минут'), ScheduleIntervals::TYPE_HALF_HOUR);
check('час', ScheduleIntervals::toSchedulerType('раз в час'), ScheduleIntervals::TYPE_HOURLY);
check('сутки', ScheduleIntervals::toSchedulerType('раз в сутки'), ScheduleIntervals::TYPE_DAILY);
check('неделя', ScheduleIntervals::toSchedulerType('раз в неделю'), ScheduleIntervals::TYPE_WEEKLY);

// Эти интервалы планировщик не умеет. Подставлять вместо них
// ближайший молча нельзя - человек получит не то, что просил,
// поэтому toSchedulerType обязан вернуть 0.
check('3 минуты не выражаются', ScheduleIntervals::toSchedulerType('раз в 3 минуты'), 0);
check('15 минут не выражаются', ScheduleIntervals::toSchedulerType('раз в 15 минут'), 0);
check('2 часа не выражаются', ScheduleIntervals::toSchedulerType('раз в 2 часа'), 0);
// 6 часов нет в списке вообще: неизвестная строка тоже даёт 0,
// чтобы вызывающий её отверг, а не поставил задачу наугад
check('6 часов не в списке', ScheduleIntervals::toMinutes('раз в 6 часов'), -1);
check('6 часов не выражаются', ScheduleIntervals::toSchedulerType('раз в 6 часов'), 0);
check('неизвестный интервал', ScheduleIntervals::toSchedulerType('через полчаса'), 0);
check('null', ScheduleIntervals::toSchedulerType(null), 0);
check('пустая строка', ScheduleIntervals::toSchedulerType(''), 0);

// Поддерживаемые интервалы не должны ругаться.
foreach (['раз в минуту', 'раз в 5 минут', 'раз в 10 минут', 'раз в 30 минут',
          'раз в час', 'раз в сутки', 'раз в неделю'] as $supported) {
    check("'$supported' описан как поддерживаемый", ScheduleIntervals::describe($supported), '');
    check("'$supported' не просит замены", ScheduleIntervals::fallback($supported), '');
}

// Неподдерживаемые обязаны объяснять, а не молчать.
check('2 часа требуют объяснения',
    str_contains(ScheduleIntervals::describe('раз в 2 часа'), 'не умеет'), true);
// замена предлагается меньшая: лучше проверять реже, чем навязывать лишнее
check('для 2 часов предлагается раз в час', ScheduleIntervals::fallback('раз в 2 часа'), 'раз в час');
check('для 3 минут предлагается раз в минуту', ScheduleIntervals::fallback('раз в 3 минуты'), 'раз в минуту');
check('для 15 минут предлагается раз в 10 минут', ScheduleIntervals::fallback('раз в 15 минут'), 'раз в 10 минут');
check('для 10 часов предлагается раз в час', ScheduleIntervals::fallback('раз в 10 часов'), 'раз в час');

// --- Запись результата в файл -----------------------------------------------------

// До этого момента проверялось только «что парсер вернул», но никогда -
// «что файл содержит». Файл и есть то, за что платит покупатель, поэтому
// здесь прогоняется настоящая цепочка: объекты -> писатель -> файл.

$csvPath = sys_get_temp_dir() . '/board-robot-output-test-' . getmypid() . '.csv';
@unlink($csvPath);

$adFromFixture = AutoriaListingItem::fromVehicleData($data);

// объявление с неприятными данными: разделитель внутри значения,
// кавычки и кириллица - именно на этом ломаются выгрузки
$adTricky = new AutoriaListingItem(
    'https://auto.ria.com/auto_bmw_x3_40459290.html',
    'BMW X3; тест "кавычки" и запятая',
    12345,
    'USD',
    1000,
    'odessa',
    'BMW',
    'X3',
    2019,
    'WBAXXX1234',
    'Кроссовер',
    'Синий',
    'Дизель',
    'Механика',
    '(068) XXX XX XX',
    '',
    'Частное лицо'
);

$writer = new CsvSpreadsheetWriter($csvPath);
$sink = new AutoriaListingsSink($writer, $csvPath);
$saved = $sink->write([$adFromFixture, $adTricky]);

check('файл создан', file_exists($csvPath), true);
check('save вернул путь файла', $saved === $csvPath, true);

$csv = file_get_contents($csvPath);
$lines = preg_split('/\r\n|\n/', trim($csv));

check('строк: заголовок + два объявления', count($lines), 3);

// Разбираем CSV правилом, а не подсчётом символов: разделитель внутри
// значения в кавычках разделителем не считается, и наивный substr_count
// даёт лишний. Проверять надо структуру, а не символы.
$headerCells = str_getcsv($lines[0], ';');
$firstCells  = str_getcsv($lines[1], ';');
$trickyCells = str_getcsv($lines[2], ';');

check('колонок в заголовке', count($headerCells), count($headersNoPhone));
check('колонок в первом объявлении', count($firstCells), count($headersNoPhone));
check('колонок во втором объявлении', count($trickyCells), count($headersNoPhone));

// значения сверяем по имени заголовка, а не по номеру: так проверка
// не ломается от перестановки колонок. Раньше тут стояли индексы -
// и добавление двух колонок их сдвинуло.
$headerIndex = array_flip(array_map(
    static fn (string $h): string => ltrim($h, "\xEF\xBB\xBF"),
    $headerCells
));
$cell = static fn (array $cells, string $header): string
    => (string)($cells[$headerIndex[$header] ?? -1] ?? '<нет колонки>');

check('заголовок начинается с задачи', ltrim($headerCells[0], "\xEF\xBB\xBF"), 'board');
check('вторая колонка - время находки', $headerCells[1], 'found_at');
check('третья колонка - адрес', $headerCells[2], 'url');
check('четвёртая колонка - заголовок', $headerCells[3], 'title');

// отчёт накапливается: в нём должно быть видно, когда и кем найдено
check('время находки заполнено', preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cell($firstCells, 'found_at')), 1);
// в этом прогоне задача не передавалась - колонка есть, но пустая
check('колонка задачи доступна', $cell($firstCells, 'board'), '');

// в файле без согласия RIA нет ни телефона, ни маски
check('в файле нет колонки телефона', in_array('phone', $headerCells, true), false);
check('в файле нет колонки маски', in_array('phone_masked', $headerCells, true), false);

// первое объявление пришло с живого объявления
check('в файле VIN', in_array('5UX33EU06R9T12665', $firstCells, true), true);
check('в файле цена', in_array('97900', $firstCells, true), true);
check('в файле город', in_array('kiev', $firstCells, true), true);

// значение с точкой с запятой и кавычками должно остаться ОДНИМ полем:
// если разъехалось - число колонок выше это покажет
check('заголовок с запятой остался одним полем', $cell($trickyCells, 'title'), 'BMW X3; тест "кавычки" и запятая');
check('колонка url второго объявления', $cell($trickyCells, 'url'), 'https://auto.ria.com/auto_bmw_x3_40459290.html');

// значения стоят именно в своих колонках - сверяем по имени заголовка,
// а не по номеру: так проверка не ломается от перестановки колонок

check('body_type второго объявления', $cell($trickyCells, 'body_type'), 'Кроссовер');
check('продавец второго объявления', $cell($trickyCells, 'seller'), 'Частное лицо');
check('цена второго объявления', $cell($trickyCells, 'price'), '12345');
check('цвет второго объявления', $cell($trickyCells, 'color'), 'Синий');
check('VIN второго объявления', $cell($trickyCells, 'vin'), 'WBAXXX1234');
check('телефон в файле пуст', $cell($trickyCells, 'phone'), '<нет колонки>');

// кодировка: файл теперь с меткой UTF-8, поэтому Excel открывает
// кириллицу верно без танцев с импортом
check('кириллица не потерялась', str_contains($csv, 'Кроссовер'), true);
check('метка UTF-8 есть', str_starts_with($csv, "\xEF\xBB\xBF"), true);
check('метка одна', substr_count($csv, "\xEF\xBB\xBF"), 1);

@unlink($csvPath);

// --- Итог --------------------------------------------------------------------------

echo "\n";
foreach ($GLOBALS['failed'] as $failure) {
    echo "FAIL: $failure\n\n";
}

printf("%d/%d проверок пройдено\n", $GLOBALS['passed'], $GLOBALS['passed'] + count($GLOBALS['failed']));

exit(count($GLOBALS['failed']) > 0 ? 1 : 0);
