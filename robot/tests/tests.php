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
require_once __DIR__ . '/../tools/slices/schedule_setup/ScheduleIntervals.php';
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
$sink = new AutoriaListingsSink($writer);
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

check('колонок в заголовке', count($headerCells), count($expectedHeaders));
check('колонок в первом объявлении', count($firstCells), count($expectedHeaders));
check('колонок во втором объявлении', count($trickyCells), count($expectedHeaders));

check('заголовок начинается с url', $headerCells[0], 'url');
check('заголовок второй колонки', $headerCells[1], 'title');

// первое объявление пришло с живого объявления
check('в файле VIN', in_array('5UX33EU06R9T12665', $firstCells, true), true);
check('в файле цена', in_array('97900', $firstCells, true), true);
check('в файле город', in_array('kiev', $firstCells, true), true);

// значение с точкой с запятой и кавычками должно остаться ОДНИМ полем:
// если разъехалось - число колонок выше это покажет
check('заголовок с запятой остался одним полем', $trickyCells[1], 'BMW X3; тест "кавычки" и запятая');
check('колонка url второго объявления', $trickyCells[0], 'https://auto.ria.com/auto_bmw_x3_40459290.html');
check('кузов второго объявления', $trickyCells[10], 'Кроссовер');

// маска не должна просочиться в колонку phone (индекс 14)
check('phone пустой', $trickyCells[14], '');
check('маска в своей колонке', $trickyCells[15], '(068) XXX XX XX');
check('продавец второго объявления', $trickyCells[16], 'Частное лицо');

// кодировка: хелпер пишет UTF-8 без BOM. Excel такие файлы открывает
// догадками, поэтому по умолчанию результат .xlsx (см. run.php),
// а этот тест фиксирует фактическое поведение CSV.
check('кириллица не потерялась', str_contains($csv, 'Кроссовер'), true);
check('нет BOM', str_starts_with($csv, "\xEF\xBB\xBF"), false);

@unlink($csvPath);

// --- Итог --------------------------------------------------------------------------

echo "\n";
foreach ($GLOBALS['failed'] as $failure) {
    echo "FAIL: $failure\n\n";
}

printf("%d/%d проверок пройдено\n", $GLOBALS['passed'], $GLOBALS['passed'] + count($GLOBALS['failed']));

exit(count($GLOBALS['failed']) > 0 ? 1 : 0);