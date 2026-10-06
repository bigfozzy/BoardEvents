<?php

/**
 * Описание задач и их разбор — в самом run.php. Здесь только вызовы.
 * Добавление новой доски не трогает этот файл.
 */
class Robot
{
    /**
     * @var string|bool Версия Робота
     */
    private string|bool $robotVersion;

    /**
     * Выполнить конфигурацию Робота
     * @throws Exception
     */
	protected function configure(): void
	{
		global $mailerType, $browser, $resFolderPath, $logFolderPath, $proxy, $mode, $isClearAll, $PHP_Use_Trought_Shell, $logFilePath, $debug_panel_translit_text;
        global $smtpServer, $smtpPort, $smtpUserName_variable, $smtpPassword_variable, $smtpSecure, $imapUserName_variable, $imapPassword_variable;
        global $imapServer, $imapPort, $imapUserName, $imapPassword, $imapSecure;
        global $outlook;
        global $log_level;

        /*// Init Logger
        SYSTEM::$log->init($log_level,
            $PHP_Use_Trought_Shell,
            true,
            $logFilePath,
            "",
            "d-m-Y H:i:s",
            "en",
            false,
            $debug_panel_translit_text
        );*/

		TOOLS::$log->debug("Start init", __METHOD__);

        // Проверка связи со Studio идёт ДО любых обращений к API. Каждое
        // такое обращение - это HTTP к запущенной Studio, и без неё каждое
        // возвращает "PHP not connected to Application" вместе с адресом
        // команды. Без проверки покупатель видит простыню непонятных
        // сообщений вместо одного понятного.
        $studioError = $this->checkStudio();
        if ($studioError !== '') {
            TOOLS::$log->error($studioError, __METHOD__, true);
            throw new RuntimeException($studioError);
        }

        // Получить версию Робота
        $this->robotVersion = TOOLS::$passport->getRobotVersion();
        if ($this->robotVersion)
            TOOLS::$log->info("Версия Робота: " . $this->robotVersion, __METHOD__);

		// при операциях скачивания файлов сохранять файлы в указанную папку
		WEB::$browser->set_default_download($resFolderPath);
		// при операциях скачивания файлов не показывать диалог загрузки файлов (скачивание выполняется автоматически)
		$browser->enable_download_file_dialog(false);

        if (!TOOLS::isLinux()) {
            TOOLS::$log->debug("Установка модели браузера Chromium", __METHOD__);
            $browser->set_model('Chromium');
        }
        WEB::$browser->close_all_tabs();

        // Создать папку лог
        if (!file_exists($logFolderPath))
            mkdir($logFolderPath);

        if ($isClearAll) {
            TOOLS::$log->debug("Чистим все данные браузера...", __METHOD__);
            TOOLS::$web->clearBrowserInfo();
        }

        // Выбираем способ отправки почтовых сообщений
        if (isset($mailerType)) {
            TOOLS::$log->info("Установка способа отправки почты $mailerType", __METHOD__);
            // ВНИМАНИЕ: настройки $smtpUserName_variable и $smtpPassword_variable необходимо хранить в "Менеджере безопасности".
            // Данный пример применений настроек для демонстрации работы
            if ($mailerType === 'smtp') {
                TOOLS::$mailer::createSMTP($smtpServer,
                    $smtpPort,
                    new EncodedVariable($smtpUserName_variable),
                    new EncodedVariable($smtpPassword_variable),
                    $smtpSecure);
                TOOLS::$mailer::getMailer()->setIsTurnedOff(false); // false: отправка писем активна
                TOOLS::$mailer::getMailer()
                    ->setSendTryCount(5) // Задать количество попыток отправить письмо в случае любой ошибки
                    ->setWaitBetweenTryToSend(10); // Пауза между повторными попытками отправить письмо (секунды)
            }
            else if($mailerType == "outlook")
            {
                TOOLS::$mailer::makeOutlook();

                // Отключить Outlook
                $outlook->kill();
            }
            else {
                TOOLS::$log->warn("Конфигурация отправки почты не была выполнена из-за отсутствия настроек", __METHOD__);
            }
        }

        // Установка способа получения почты IMAP
        if ($imapServer && $imapUserName) {
            TOOLS::$log->info("Установка способа получения почты IMAP", __METHOD__);
            // ВНИМАНИЕ: настройки $imapUserName_variable и $imapPassword_variable необходимо хранить в "Менеджере безопасности"
            // Данный пример применений настроек для демонстрации работы
            TOOLS::$mailerHub::createIMAP($imapServer,
                (int)$imapPort,
                new EncodedVariable($imapUserName_variable),
                new EncodedVariable($imapPassword_variable),
                $imapSecure);

            /** @var MailerHubIMAP $mailerHub */
            $mailerHub = TOOLS::$mailerHub::getMailerHub();
            $messageCount = $mailerHub->getMessagesCount();

            TOOLS::$log->debug("Всего писем $messageCount", __METHOD__);
        }

        // Задать прокси
        if ($proxy) {
            TOOLS::$log->info("Задать прокси: $proxy", __METHOD__);
            $browser->enable_proxy("", $proxy, true);
        }

        // Режим работы ('smart', 'demo', 'release')
        TOOLS::$log->info("Режим работы Робота: $mode" , __METHOD__, true);
        if ($mode == "demo")
            TOOLS::$log->warn("ВНИМАНИЕ! Для режима DEMO Робота НЕ предусмотрено внесение изменений на сайте!", __METHOD__, true);
        else
            TOOLS::$log->info("ВНИМАНИЕ! Робот вносит изменения на сайте", __METHOD__, true);

        $this->configureSchedule();

        TOOLS::$log->debug("End init", __METHOD__);
	}

    /**
     * Проверить, что Human Emulator Studio отвечает.
     *
     * get_port() - самый дешёвый вызов: он ничего не грузит. При
     * недоступной Studio возвращает пустую строку, а не исключение,
     * поэтому проверяется именно он.
     *
     * Проверка обязана идти до инициализации логинатора: сам логинатор
     * XHE тоже ходит в Studio и напечатал бы ту же самую ошибку.
     *
     * @return string '' если всё в порядке, иначе текст для показа
     */
    private function checkStudio(): string
    {
        $port = '';

        try {
            $port = (string)WINDOW::$app->get_port();
        } catch (Throwable $e) {
            return 'Human Emulator Studio не отвечает: ' . $e->getMessage();
        }

        if (trim($port) === '') {
            return 'Human Emulator Studio не запущена или не отвечает.' . "\n"
                . 'Откройте Studio, добавьте этот скрипт в проект и запустите оттуда.'
                . "\nИз консоли то же самое: сначала дождитесь окончания загрузки"
                . "\n" . 'браузера в Studio, потом запускайте скрипт.';
        }

        return '';
    }

    /**
     * Завести или обновить задачу в планировщике Studio.
     *
     * Идемпотентно: своя задача обновляется, чужие не трогаются.
     * Выключается одной настройкой $scheduleRegisterOnRun в run.php.
     *
     * @return void
     */
    private function configureSchedule(): void
    {
        global $scheduleRegisterOnRun, $scheduleInterval, $scheduleFirstRunTime, $scriptPath;

        if (!$scheduleRegisterOnRun) {
            TOOLS::$log->debug('Регистрация в планировщике выключена настройкой', __METHOD__);
            return;
        }

        try {
            $result = RobotSchedule::register((string)$scriptPath, (string)$scheduleInterval, (string)$scheduleFirstRunTime);

            if ($result === '') {
                TOOLS::$log->debug('Задача в планировщике не изменилась', __METHOD__);
            } else {
                TOOLS::$log->info($result, __METHOD__, true);
            }
        }
        catch (Throwable $e) {
            // Не задача планировщика роняет проверку доски: если он недоступен,
            // робот всё равно должен один раз пройти по объявлениям.
            TOOLS::$log->warn('Не удалось обратиться к планировщику Studio: ' . $e->getMessage(), __METHOD__, true);
        }
    }

    /**
     * Запуск основной
     * @throw \Throwable
     */
    public function run(): void
    {
        global $logFilePath, $xhe_host, $robotID, $mode;
        global $mailFrom, $mailTo, $notifyOnStart;

        // init
		$this->configure();

        TOOLS::$log->info("[{$xhe_host}] [Робот #{$robotID}] Начал работу." , __METHOD__, true);

        $mailer = TOOLS::$mailer::getMailer();

        // Отчёт о старте - только по желанию. В шаблоне вендора он
        // отправляется при каждом запуске, а робот запускается по
        // расписанию: при интервале в 5 минут это 288 писем «начал
        // работу» в сутки плюс столько же «результат работы».
        // Почта перестаёт читаться, её выключают - и вместе с ней
        // пропадает всё остальное.
        if ($mailer && $notifyOnStart) {
            $mailer->setFrom($mailFrom)
                ->setTo($mailTo)
                ->setSubject("Bot #$robotID Начал работу $mode")
                ->setMessage("Начал работу, версия: " . $this->robotVersion
                    . ". Время: " . (new DateTime('now'))->format("d.m.Y H:i"));

            TOOLS::$log->info(
                'Отправлено письмо о старте: ' . $mailer->sendText(),
                __METHOD__
            );
        }

        $total = 0;
        $failed = '';

        try
        {
            $total = $this->runBoards();

            TOOLS::$log->info(
                "Задач обработано, всего новых объявлений: $total",
                __METHOD__, true
            );
        }
        catch (Exception $ex)
        {
            $failed = $ex->getMessage();
            TOOLS::$log->error("Запуск завершился критической ошибкой $failed", __METHOD__, true);
        }
        catch (Error $ex)
        {
            $failed = $ex->getMessage();
            $phpFileLine = $ex->getLine();
            $phpFileName = $ex->getFile();
            if ($phpFileLine != '' && $phpFileName != '')
                $msg = $failed . ' ' . "File: $phpFileName. Line: $phpFileLine";
            else
                $msg = $failed;
            TOOLS::$log->error("Запуск завершился критической ошибкой (сбой) $msg", __METHOD__, true);
        }

        // Отчёт по итогам - только когда есть что сообщить: нашлись новые
        // объявления или запуск сломался. Молчание в логе и письмо о том
        // же самом раз в пять минут - это разные вещи, и второе только
        // мешает.
        if ($mailer && ($total > 0 || $failed !== '')) {
            $body = $failed !== ''
                ? "Робот упал: $failed\nПроверьте лог: " . $logFilePath
                : "Найдено новых объявлений: $total\n"
                    . "Подробности в файле задачи и в логе: " . $logFilePath;

            $mailer->setFrom($mailFrom)
                ->setTo($mailTo)
                ->setSubject($failed !== ''
                    ? "Bot #$robotID сбой"
                    : "Bot #$robotID новые объявления: $total")
                ->setMessage($body)
                ->setAttachments(array());

            TOOLS::$log->info(
                'Отправлено письмо с итогами: ' . $mailer->sendText(),
                __METHOD__, true
            );
        }
    }

    /**
     * Обойти все задачи из настроек.
     *
     * Одна плохая задача не должна отменять остальные: у каждой свой
     * адрес, своё имя и свой файл, поэтому падение одной не значит,
     * что не отработали остальные. Ошибка пишется в лог и обход идёт
     * дальше - иначе опечатка в одном адресе тихо отменяла бы сбор
     * по всем остальным фильтрам.
     *
     * @return int всего новых объявлений по всем задачам
     */
    private function runBoards(): int
    {
        global $autoriaBoards, $autoriaLimit, $dataFolderPath;

        $boards = is_array($autoriaBoards) ? $autoriaBoards : [];

        if ($boards === []) {
            TOOLS::$log->error(
                'В run.php не задано ни одной задачи ($autoriaBoards пуст)',
                __METHOD__, true
            );
            return 0;
        }

        $total = 0;

        foreach ($boards as $index => $board) {
            $name = trim((string)($board['name'] ?? '')) ?: "задача #$index";
            $url = trim((string)($board['url'] ?? ''));

            if ($url === '') {
                TOOLS::$log->error("У задачи '$name' не заполнен url - задача пропущена", __METHOD__, true);
                continue;
            }

            try {
                $total += $this->runBoard($board, $name, $url);
            }
            catch (Throwable $e) {
                TOOLS::$log->error(
                    "Задача '$name' не отработала: " . $e->getMessage()
                    . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
                    __METHOD__, true
                );
            }
        }

        return $total;
    }

    /**
     * Отработать одну задачу.
     *
     * @return int новых объявлений по этой задаче
     */
    private function runBoard(array $board, string $name, string $url): int
    {
        global $autoriaLimit, $dataFolderPath, $collectPhones, $autoriaPages;

        $onlyNew = array_key_exists('onlyNew', $board) ? (bool)$board['onlyNew'] : true;
        $limit = isset($board['limit']) ? (int)$board['limit'] : (int)$autoriaLimit;
        $pages = isset($board['pages']) ? (int)$board['pages'] : (int)$autoriaPages;
        $filePath = trim((string)($board['file'] ?? ''));

        // файл не задали - делаем имя из названия задачи, чтобы два
        // фильтра не затирали результат друг друга
        if ($filePath === '') {
            $filePath = $dataFolderPath . '/' . $this->fileNameFor($name) . '.csv';
        }

        return AutoriaListingsSlice::run(
            $url,
            $filePath,
            $name,
            $onlyNew,
            $limit,
            $dataFolderPath,
            (bool)$collectPhones,
            $pages
        );
    }

    /**
     * Имя файла из названия задачи.
     *
     * Название может быть любым - с пробелами, кириллицей и
     * двоеточиями, а в имя файла такое класть нельзя.
     *
     * Кириллица в имени сохраняется намеренно. Пробная транслитерация
     * давала ужасный результат: «Киев, до 5000» превращалось в
     * «5000.xlsx», а «Рома» - в «board.xlsx», то есть две разные
     * задачи писали бы в один файл и затирали друг друга. Windows
     * кириллицу в именах переносит, а покупателю «Киев_до_5000.xlsx»
     * понятнее, чем «5000».
     */
    private function fileNameFor(string $name): string
    {
        // в Windows нельзя: < > : " / \ | ? * и управляющие символы
        $clean = preg_replace('/[<>:"|?*\x00-\x1F]/u', '_', $name) ?? $name;
        $clean = preg_replace('/\s+/u', '_', $clean) ?? $clean;

        // оставляем буквы любого алфавита, цифры, дефис и подчёркивание
        $slug = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $clean) ?? $clean;
        $slug = trim((string)$slug, '_');
        $slug = preg_replace('/_{2,}/', '_', $slug) ?? $slug;

        // Windows не любит имена длиннее 255 символов, а запас нужен
        // на расширение и на суффикс "- 2" при совпадении имён
        if (mb_strlen($slug) > 100) {
            $slug = mb_substr($slug, 0, 100);
        }

        return $slug !== '' ? $slug : 'board';
    }
}
