<?php

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

        TOOLS::$log->debug("End init", __METHOD__);
	}

    /**
     * Запуск основной
     * @throw \Throwable
     */
    public function run(): void
    {
        global $logFilePath, $xhe_host, $robotID, $mode;
        global $mailFrom, $mailTo;

        // init
		$this->configure();

        TOOLS::$log->info("[{$xhe_host}] [Робот #{$robotID}] Начал работу." , __METHOD__, true);

        // Послать отчет о старте
        $mailer = TOOLS::$mailer::getMailer();
        if ($mailer) {
            $mailer->setFrom($mailFrom)
                ->setTo($mailTo)
                ->setSubject("Bot #$robotID Начал работу $mode")
                ->setMessage("Начал работу версия: " . $this->robotVersion .". Время отправки письма:" .
                    (new DateTime('now'))->format("d.m.Y H:i"));
            $res = $mailer->sendText();
            TOOLS::$log->info("Результат отправки письма с информацией о старте робота: $res", __METHOD__, true);
        }

        try
        {
            // Бизнес процесс: слайс закрывает одну ответственность, здесь только вызов.
            global $autoriaBoardName, $autoriaListUrl, $autoriaDataFilePath,
                   $autoriaOnlyNew, $autoriaLimit, $dataFolderPath;

            AutoriaListingsSlice::run(
                $autoriaListUrl,
                $autoriaDataFilePath,
                $autoriaBoardName,
                (bool)$autoriaOnlyNew,
                (int)$autoriaLimit,
                $dataFolderPath
            );
        }
        catch (Exception $ex)
        {
            TOOLS::$log->error("Запуск завершился критической ошибкой ".  $ex->getMessage(), __METHOD__, true);
        }
        catch (Error $ex)
        {
            $phpFileLine = $ex->getLine();
            $phpFileName = $ex->getFile();
            if ($phpFileLine != '' && $phpFileName != '')
                $msg = $ex->getMessage() . ' ' . "File: $phpFileName. Line: $phpFileLine";
            else
                $msg = $ex->getMessage();
            TOOLS::$log->error("Запуск завершился критической ошибкой (сбой) ".  $msg, __METHOD__, true);
        }
        finally
        {

        }

        if ($mailer) {
            $attachments = array();

            $mailer->setFrom($mailFrom)
                ->setTo($mailTo)
                ->setSubject("Bot #$robotID результат работы")
                ->setMessage("Это письмо с результатами работы Робота версия: " . $this->robotVersion . ". Письмо содержит: \n" .
                    (new DateTime('now'))->format("d.m.Y H:i"))
                ->setAttachments($attachments);
            $res = $mailer->sendText();

            TOOLS::$log->info("Результат отправки письма с отчетом: $res", __METHOD__, true);
        }

    }
}
