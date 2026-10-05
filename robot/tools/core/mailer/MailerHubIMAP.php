<?php

/**
 * Класс для классов чтения E-mail сообщений с помощью протокола IMAP
 */
class MailerHubIMAP extends MailerHub
{

    /**
     * @param string $server Адрес почтового сервера
     * @param int $port Номер порта почтового сервера
     * @param string $login Имя пользователя почтового сервера
     * @param string $password Пароль пользователя почтового сервера
     * @param int $sslOption тип защиты соединения. 0 : без использования SSL или TLS; 1 : Автоматически определять; 2 : Соединение должно использовать SSL или TLS; 3 : Использовать TLS + STARTTLS ; 4 : Использовать TLS.
     * @param string $certType типы сертификата аутентификации через SSL "s, c, h, e"
     * @param int $timeout таймаут, сек
     */
    public function __construct(
        string $server,
        int    $port,
        string $login,
        string $password,
        int    $sslOption,
        string $certType,
        int    $timeout,
    )
    {
        $this->server = $server;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->sslOption = $sslOption;
        $this->certType = $certType;
        $this->timeout = $timeout;
    }

    /**
     * Устанавливает соединение с IMAP сервером
     * @return bool успешно соединились
     **/
    protected function connect(): bool
    {
        $bResult = WEB::$mail->imap_connect(
            $this->server,
            $this->port,
            $this->login,
            $this->password,
            $this->sslOption,
            $this->certType,
            $this->timeout
        );
        if (!$bResult)
            TOOLS::$log->error("Не смогли подключиться к IMAP серверу", __METHOD__);

        return $bResult;
    }

    /**
     * Отключение от IMAP сервера
     * @return bool
     **/
    public function disconnect(): bool
    {
        return WEB::$mail->imap_disconnect();
    }

    /**
     * Получить общее количество писем в заданной папке
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX. Пустое значение соответствует поиску в папке INBOX
     * @return int количество писем или -1 если произошла ошибка
     */
    public function getMessagesCount(string $folderName = ''): int
    {
        $res = false;
        try {
            if ($this->connect())
                $res = WEB::$mail->get_message_count_via_imap($folderName);
            if ($res == -1) {
                TOOLS::$log->error("Ошибка в работе с IMAP сервером", __METHOD__);
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Получить письмо по порядковому номеру в заданной папке (счет с 0)
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX
     * @return bool|XHEMailMessage объект письма или false
     */
    public function getMessageByNumber(string $folderName, int $number): bool|XHEMailMessage
    {
        $res = false;
        try {
            if ($this->connect())
                $res = WEB::$mail->get_message_by_number_via_imap($folderName, $number);
            if (!$this->checkIsSuccessWhenAnswerIsXHEMailMessage($res)) {
                TOOLS::$log->warn("Сообщение по номеру #$number не найдено", __METHOD__);
                return false;
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Получить первое найденное письмо по фильтру поля "Тема". Поиск письма по порядку в заданной папке
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX. Пустое значение соответствует поиску в папке INBOX
     * @param string $subjectPhrase фраза для поиска по значению "Тема"
     * @param int $skipNumberCount с какого номера письма по порядку начинать поиск
     * @return bool|XHEMailMessage объект письма или false
     */
    public function getMessageBySubjectAndNotRead(string $folderName, string $subjectPhrase, int $skipNumberCount = 0): bool|XHEMailMessage
    {
        try {
            if ($this->connect()){
                $messageCount = WEB::$mail->get_message_count_via_imap($folderName);
                if ($messageCount == 0){
                    TOOLS::$log->error("Указанная папка в ящике пуста", __METHOD__);
                    return false;
                }
                else if (!$messageCount) {
                    TOOLS::$log->error("Ошибка работы с IMAP сервером", __METHOD__);
                    return false;
                }

                TOOLS::$log->debug("Всего писем $messageCount", __METHOD__);

                for ($k = 0; $k < $messageCount; $k++) {
                    /** @var XHEMailMessage $mailMessage */

                    $mailMessage = WEB::$mail->get_message_by_number_via_imap($folderName, $k);
                    if (!$this->checkIsSuccessWhenAnswerIsXHEMailMessage($mailMessage)) {
                        TOOLS::$log->warn("Сообщение по номеру #$k вернуло пустой объект", __METHOD__);
                        continue;
                    }

                    if (!$mailMessage)
                        continue;

                    if ($mailMessage->is_readed === "True") {
                        continue;
                    }
                    TOOLS::$log->debug("#$k/" . $mailMessage->message_id . " " . $mailMessage->date . ' ' . $mailMessage->from . "Тема:  " . $mailMessage->subject . '  Прочитано: ' . $mailMessage->is_readed, __METHOD__);
                    if (!str_contains($mailMessage->subject, $subjectPhrase))
                        continue;

                    TOOLS::$log->debug("Письмо найдено!! Тема:  " . $mailMessage->subject, __METHOD__);

                    // Вывести текст письма, если оно не прочитано
                    //$msg = MailerHubIMAP::getMsgTextBodyCleared($mailMessage);
                    //$msg = substr($msg, 0, 200);
                    //TOOLS::$log->debug("Текст:  $msg", __METHOD__);
                    return  $mailMessage;
                }
            }
        }
        finally {
            $this->disconnect();
        }

        return false;
    }

    /**
     * Получить письмо по фильтру поля "Тема" (отсчет с определенной позиции с 0)
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX
     * @param string $subjectPhrase фраза для поиска по значению "Тема"
     * @param bool $exactly точное соответствие текста "Тема"
     * @param int $skipNumberCount с какого номера письма по порядку начинать поиск
     * @return bool|XHEMailMessage объект письма или false
     */
    public function getMessageBySubject(string $folderName, string $subjectPhrase, bool $exactly = false, int $skipNumberCount = 0): bool|XHEMailMessage
    {
        $res = false;
        try {
            if ($this->connect()){
                $res = WEB::$mail->get_message_by_subject_via_imap($folderName, $subjectPhrase, $exactly, $skipNumberCount);
                if (!$this->checkIsSuccessWhenAnswerIsXHEMailMessage($res)) {
                    TOOLS::$log->warn("Сообщение #$skipNumberCount не подходит по критериям поиска", __METHOD__);
                    return false;
                }
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Получить письмо по фильтру поля "От кого" (отсчет с определенной позиции с 0)
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX
     * @param string $fromPhrase фраза для поиска по значению "Отправитель"
     * @param bool $exactly точное соответствие текста "Отправителя"
     * @param int $skipNumberCount с какого номера письма по порядку начинать поиск
     * @return bool|XHEMailMessage объект письма или false
     */
    public function getMessageByFrom(string $folderName, string $fromPhrase, bool $exactly = false, int $skipNumberCount = 0): bool|XHEMailMessage
    {
        $res = false;
        try {
            if ($this->connect()){
                $res = WEB::$mail->get_message_by_from_via_imap($folderName, $fromPhrase, $exactly, $skipNumberCount);
                if (!$this->checkIsSuccessWhenAnswerIsXHEMailMessage($res)) {
                    TOOLS::$log->warn("Сообщение #$skipNumberCount не подходит по критериям поиска", __METHOD__);
                    return false;
                }
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Получить email-сообщение по ID
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX
     * @param string $messageId ID сообщения
     * @param int $timeout таймаут
     * @return string|bool Текст сообщения или false
     */
    public function getMessageByTd(string $folderName, string $messageId, int $timeout = 300): string|bool
    {
        $res = false;
        try {
            if ($this->connect())
                $res = WEB::$mail->get_message_by_id_via_imap($folderName, $messageId, $timeout);
            if (!$this->checkIsSuccessWhenAnswerIsXHEMailMessage($res)) {
                TOOLS::$log->warn("Не смогли получить E-mail: $folderName/$messageId", __METHOD__);
                return false;
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Удалить email-сообщение по ID
     * @param string $folderName название папки. Пустое значение соответствует поиску в папке INBOX
     * @param string $messageId ID сообщения
     * @param int $timeout таймаут
     * @return bool Успешно выполнено Да/Нет
     */
    public function deleteMessageById(string $folderName, string $messageId, int $timeout = 300): bool
    {
        $res = false;
        try {
            if ($this->connect()) {
                $res = WEB::$mail->delete_message_by_id_via_imap($folderName, $messageId, $timeout);
            }
            if (!$res) {
                TOOLS::$log->error("Не смогли удалить E-mail: $folderName/$messageId", __METHOD__);
                return false;
            }
        }
        finally {
            $this->disconnect();
        }

        return $res;
    }

    /**
     * Получить текст сообщения без html тэг-ов из объекта XHEMailMessage
     * @param XHEMailMessage $mailMessage
     * @return string
     */
    public static function getMsgTextBodyCleared(XHEMailMessage $mailMessage): string {
        return trim(strip_tags($mailMessage->text_body));
    }

    /**
     * Проверить ответ севера с учетом типа XHEMailMessage
     * @param XHEMailMessage|false $res
     * @return bool Ответ положительный? Да/Нет
     */
    public function checkIsSuccessWhenAnswerIsXHEMailMessage(XHEMailMessage|bool $res): bool
    {
        if (!$res)
            return false;
        // если объект типа XHEMailMessage у которого все поля пустые
        if (get_class($res) == "XHEMailMessage" && empty($res->message_id))
            return false;
        if (!$res)
            return false;
        return true;
    }
}
