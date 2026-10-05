<?php

/**
 * Сквозной прогон всего конвейера без браузера.
 *
 * Почему отдельным файлом, а не в tests.php: здесь нужен заглушечный
 * TOOLS с логинатором, а такой класс в tests.php помешал бы остальным
 * проверкам, которым XHE не требуется.
 *
 * Здесь запускается ровно тот код, который работает в Studio: тот же
 * AutoriaListingsSlice::run(), тот же отбор, тот же писатель, та же
 * запись в файл и то же состояние. Подменена только читалка - она ходит
 * в браузер, а браузера здесь нет.
 *
 *   php tests/pipeline_test.php
 */

require_once __DIR__ . '/../tools/slices/autoria_listings/BoardRules.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingItem.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsVehicleData.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSelection.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsState.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSink.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsScraper.php';
require_once __DIR__ . '/../tools/slices/autoria_listings/AutoriaListingsSlice.php';
require_once __DIR__ . '/../tools/core/contracts/SpreadsheetWriter.php';
require_once __DIR__ . '/../tools/core/adapters/SpreadsheetWriters.php';
require_once __DIR__ . '/../tools/core/helpers/CsvHelper.php';
require_once __DIR__ . '/../tools/core/adapters/CsvSpreadsheetWriter.php';
require_once __DIR__ . '/RealPageFixture.php';

/**
 * Заглушка логинатора: пишет в память, ничего не требует от Studio.
 */
class StubLogger
{
    /** @var string[] */
    public array $lines = [];

    public function info(string $m, $c = null, $b = false): void
    {
        $this->lines[] = 'INFO  ' . $m;
    }

    public function warn(string $m, $c = null, $b = false): void
    {
        $this->lines[] = 'WARN  ' . $m;
    }

    public function error(string $m, $c = null, $b = false): void
    {
        $this->lines[] = 'ERROR ' . $m;
    }

    public function debug(string $m, $c = null, $b = false): void
    {
        $this->lines[] = 'DEBUG ' . $m;
    }
}

/**
 * Минимальный TOOLS: слайсу нужен только логинатор.
 */
class TOOLS
{
    /** @var StubLogger|null */
    public static $log;

    public static function __constructStatic(): void
    {
        self::$log = new StubLogger();
    }
}

TOOLS::__constructStatic();

/**
 * Читалка без браузера: отдаёт заранее заданные объявления и разбирает
 * страницу тем же кодом, что и в Studio, на разметке с живой доски.
 */
class StubScraper extends AutoriaListingsScraper
{
    /** @var string[] адреса объявлений, которые «доска» отдаст */
    public array $available = [];

    public int $harvestCalls = 0;

    /** @return AutoriaListingItem[] */
    public function harvestList(string $listUrl, int $limit = 100, int $pages = 1): array
    {
        $this->harvestCalls++;

        $items = [];

        foreach ($this->available as $path) {
            $items[] = new AutoriaListingItem('https://auto.ria.com' . $path);
        }

        return array_slice($items, 0, $limit);
    }

    public function fillDetails(AutoriaListingItem $item, bool $collectPhones = false): ?AutoriaListingItem
    {
        $data = AutoriaListingsVehicleData::fromPageSource(RealPageFixture::html());

        if ($data === null) {
            return null;
        }

        $filled = AutoriaListingItem::fromVehicleData($data);
        // адрес оставляем тот, что дала «доска» - так проверяется ключ
        $filled->url = $item->url;

        return $filled;
    }
}

$passed = 0;
$failed = [];

function check(string $name, $actual, $expected): void
{
    global $passed, $failed;

    if ($actual === $expected) {
        $passed++;
        return;
    }

    $failed[] = sprintf(
        "%s\n    ожидалось: %s\n    получено : %s",
        $name,
        var_export($expected, true),
        var_export($actual, true)
    );
}

$dir = sys_get_temp_dir() . '/board-pipeline-' . getmypid();
@mkdir($dir, 0777, true);
$report = $dir . '/report.csv';

$paths = [];
foreach (range(1, 5) as $n) {
    $paths[] = '/auto_bmw_x5_' . $n . '.html';
}

$board = 'auto.ria — pipeline';
$url = 'https://auto.ria.com/car/bmw';

// --- первый запуск: доска отдаёт пять объявлений ----------------------------

$scraper = new StubScraper();
$scraper->available = $paths;

$first = AutoriaListingsSlice::run($url, $report, $board, true, 100, $dir, false, 3, $scraper);
check('первый запуск принял все пять', $first, 5);
check('читалка позвана один раз', $scraper->harvestCalls, 1);

$rows = file($report, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// первая ячейка несёт метку UTF-8 - без неё имя колонки не совпадёт
$header = array_map(
    static fn ($h): string => ltrim((string)$h, "\xEF\xBB\xBF"),
    (array)str_getcsv((string)$rows[0], ';')
);

check('в отчёте заголовок и пять строк', count($rows), 6);
check('колонок в отчёте', count($header), 17);
check('есть колонка задачи', in_array('board', $header, true), true);
check('есть колонка времени', in_array('found_at', $header, true), true);
check('нет колонки телефона', in_array('phone', $header, true), false);

// данные пришли из настоящего разбора, а не выдуманы
check('цена из живой разметки попала в отчёт',
    in_array('97900', (array)str_getcsv((string)$rows[1], ';'), true), true);
check('адрес из выдачи сохранён', str_contains((string)$rows[1], '_1.html'), true);
check('задача в строке', str_contains((string)$rows[1], 'pipeline'), true);

// --- второй запуск: та же доска, ничего нового ------------------------------

$second = AutoriaListingsSlice::run($url, $report, $board, true, 100, $dir, false, 3, $scraper);
check('второй запуск не принял ничего', $second, 0);

$rows2 = file($report, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
check('отчёт не изменился', count($rows2), 6);

// --- третий запуск: доска отдала ещё одно новое объявление -----------------

$scraper->available[] = '/auto_bmw_x5_6.html';
$third = AutoriaListingsSlice::run($url, $report, $board, true, 100, $dir, false, 3, $scraper);
check('новое объявление принято', $third, 1);

$rows3 = file($report, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
check('в отчёте стало шесть строк', count($rows3), 7);

// --- состояние на диске ------------------------------------------------------

$stateFile = $dir . '/autoria_state.json';
check('файл состояния создан', file_exists($stateFile), true);

$state = json_decode((string)file_get_contents($stateFile), true);
check('в состоянии шесть объявлений', count($state['seen'] ?? []), 6);
check('дата создания задачи записана', isset($state['tasks'][$board]['createdAt']), true);

// --- пустая выдача не трогает отчёт ----------------------------------------

$empty = new StubScraper();
$fromEmpty = AutoriaListingsSlice::run($url, $report, $board, true, 100, $dir, false, 3, $empty);
check('пустая выдача вернула ноль', $fromEmpty, 0);

$rows4 = file($report, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
check('отчёт цел после пустой выдачи', count($rows4), 7);

// --- итог -------------------------------------------------------------------

foreach ($failed as $f) {
    echo 'FAIL: ' . $f . "\n\n";
}

printf("%d/%d проверок пройдено\n", $passed, $passed + count($failed));

foreach (glob($dir . '/*') ?: [] as $f) {
    @unlink($f);
}
@rmdir($dir);

exit(count($failed) > 0 ? 1 : 0);