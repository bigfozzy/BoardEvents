<?php

// Автозагрузка библиотек из Composer
$composerLibsAutoloadTmp = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerLibsAutoloadTmp)) { require_once($composerLibsAutoloadTmp); }

/**
 * Подключает все php-файлы папки в алфавитном порядке.
 * Папки `core/contracts`, `core/adapters`, `core/modules`, `slices/*` — точки расширения:
 * файл, положенный в такую папку, подхватывается без правки этого автозагрузчика.
 * @param string $folderPath Абсолютный путь к папке (может не существовать).
 * @return void
 */
function __require_php_folder(string $folderPath): void
{
    if (!is_dir($folderPath))
        return;

    $files = [];
    foreach (new DirectoryIterator($folderPath) as $item)
    {
        if ($item->isFile() && mb_strtolower($item->getExtension()) === 'php')
            $files[] = $item->getFilename();
    }
    // Интерфейсы должны объявиться раньше реализаций, поэтому порядок детерминированный.
    sort($files, SORT_STRING);
    foreach ($files as $file)
        require_once($folderPath . '/' . $file);
}

// Классы-помощники
require_once(__DIR__ . "/core/helpers/SettingsHelper.php");
require_once(__DIR__ . "/core/helpers/DomHelper.php");
require_once(__DIR__ . "/core/helpers/SystemHelper.php");
require_once(__DIR__ . "/core/helpers/AppHelper.php");
require_once(__DIR__ . "/core/helpers/WebHelper.php");
require_once(__DIR__ . "/core/helpers/PassportHelper.php");
require_once(__DIR__ . "/core/helpers/LogHelper.php");
require_once(__DIR__ . "/core/helpers/CsvHelper.php");
require_once(__DIR__ . "/core/helpers/ExcelHelper.php");

// RPAbot Mail Sender
require_once(__DIR__ . "/core/mailer/Mailer.php");
require_once(__DIR__ . "/core/mailer/MailerHub.php");
require_once(__DIR__ . "/core/mailer/MailerOutlook.php");
require_once(__DIR__ . "/core/mailer/MailerSMTP.php");
require_once(__DIR__ . "/core/mailer/MailerHubIMAP.php");

// Порт и адаптеры: слайсы зависят только от интерфейса, реализацию выбирает фабрика.
__require_php_folder(__DIR__ . '/core/contracts');
__require_php_folder(__DIR__ . '/core/adapters');

// Robot app
require_once(__DIR__ . "/Robot.php");

// Стандартные помощники смотри классы: SETTINGS, TOOLS
require_once(__DIR__ . "/core/Settings.php");
SETTINGS::__constructStatic();
require_once(__DIR__ . "/core/Tools.php");
TOOLS::__constructStatic();

// Внешние модули (кладёте файл в core/modules/ — он подключается сам)
__require_php_folder(__DIR__ . '/core/modules');

// Слайсы робота: один слайс = одна папка tools/slices/<name>/
if (is_dir(__DIR__ . '/slices'))
{
    foreach (new DirectoryIterator(__DIR__ . '/slices') as $sliceDir)
    {
        if ($sliceDir->isDir() && !$sliceDir->isDot())
            __require_php_folder(__DIR__ . '/slices/' . $sliceDir->getFilename());
    }
}
