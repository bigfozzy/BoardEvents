<?php

/**
 * Для отправки писем с помощью функционального объекта mail
 */
class MailerSMTP extends Mailer
{
    /**
     * Тип E-mail сообщения текст для функционального объекта mail
     */
    public const SMTP_TYPE_MESSAGE_TEXT = 1;

    /**
     * Тип E-mail сообщения HTML для функционального объекта mail
     */
    public const SMTP_TYPE_MESSAGE_HTML = 2;

    /**
     * @var string Адрес почтового сервера
     */
    private string $strServer;

    /**
     * @var int Номер порта почтового сервера
     */
    private int $intPort;

    /**
     * @var string Имя пользователя почтового сервера
     */
    private string $strLogin;

    /**
     * @var string Пароль пользователя почтового сервера
     */
    private string $strPassword;

    /**
     * @var int тип защиты соединения. 0 : без использования SSL или TLS; 1 : Автоматически определять; 2 : Соединение должно использовать SSL или TLS; 3 : Использовать TLS + STARTTLS ; 4 : Использовать TLS.
     */
    private int $intSslOption;

    /**
     * @var string типы сертификата аутентификации через SSL "s, c, h, e"
     */
    private string $strCertType;

    /**
     * @var int таймаут, сек
     */
    private int $intConnectTimeout;

    /**
     * @var string Путь к файлу логов по работе SMTP
     */
    private string $strLogPath;

    /**
     * @var int Количество попыток отправить письма в случае ошибки
     */
    private int $sendTryCount = 1;

    /**
     * @var int Пауза между повторными попытками отправить письмо, секунд. Получить значение.
     */
    private int $waitBetweenTryToSend = 3;

    /**
     * @param string $strServer Адрес почтового сервера
     * @param int $intPort Номер порта почтового сервера
     * @param string $strLogin Имя пользователя почтового сервера
     * @param string $strPassword Пароль пользователя почтового сервера
     * @param int $intSslOption тип защиты соединения. 0 : без использования SSL или TLS; 1 : Автоматически определять; 2 : Соединение должно использовать SSL или TLS; 3 : Использовать TLS + STARTTLS ; 4 : Использовать TLS.
     * @param string $strCertType типы сертификата аутентификации через SSL "s, c, h, e"
     * @param int $intConnectTimeout таймаут, сек
     * @param string $strLogPath путь к файлу логов работы протоколов
     */
    public function __construct(
        string $strServer,
        int $intPort,
        string $strLogin,
        string $strPassword,
        int $intSslOption,
        string $strCertType,
        int $intConnectTimeout,
        string $strLogPath
    )
    {
        $this->strServer = $strServer;
        $this->intPort = $intPort;
        $this->strLogin = $strLogin;
        $this->strPassword = $strPassword;
        $this->intSslOption = $intSslOption;
        $this->strCertType = $strCertType;
        $this->intConnectTimeout = $intConnectTimeout;
        $this->strLogPath = $strLogPath;
        TOOLS::$log->debug("Server: $strServer:$intPort тип $intSslOption", __METHOD__);
    }

    /**
     * Устанавливает соединение с smtp сервером
     * @return bool успешно соединились
     **/
    protected function connect(): bool
    {
        $bResult = WEB::$mail->smtp_connect(
            $this->strServer,
            $this->intPort,
            $this->strLogin,
            $this->strPassword,
            $this->intSslOption,
            $this->strCertType,
            $this->intConnectTimeout,
            $this->strLogPath
        );
        if (!$bResult)
            TOOLS::$log->error("Не смогли подключиться к SMTP серверу", "MailerSMTP.connect");

        return $bResult;
    }

    /**
     * Количество попыток отправить письма в случае ошибки. Установить значение.
     * @param int $tryCount Количество попыток
     * @return self
     * @throws Exception Если значение недопустимое
     */
    public function setSendTryCount(int $tryCount)
    {
        if ($tryCount < 1)
            throw new Exception("Значение для количество попыток отправить письма. Должно быть больше 1. Ошибочное значение: $tryCount");
        $this->sendTryCount = $tryCount;
        return $this;
    }

    /**
     * Количество попыток отправить письма в случае ошибки. Получить значение.
     * @return int
     */
    public function getSendTryCount(): int
    {
        return $this->sendTryCount;
    }

    /**
     * Пауза между повторными попытками отправить письмо, секунд. Установить значение.
     * @return self
     * @throws Exception Если значение недопустимое
     */
    public function setWaitBetweenTryToSend(int $waitBetweenTryToSend)
    {
        if ($waitBetweenTryToSend < 2)
            throw new Exception("Значение для количество попыток отправить письма. Должно быть больше 2. Ошибочное значение: $waitBetweenTryToSend");
        $this->waitBetweenTryToSend = $waitBetweenTryToSend;
        return $this;
    }

    /**
     * Пауза между повторными попытками отправить письмо, секунд. Получить значение.
     * @return int
     */
    public function getWaitBetweenTryToSend(): int
    {
        return $this->waitBetweenTryToSend;
    }

    /**
     * Отправляет сообщение с типом Plain/Text (учитывается boolIsTurnedOff)
     * @param bool $isNeedAuth Авторизоваться на сервере SMTP (если это требует протокол)
     * @return bool Успешно Да/нет
     */
    public function sendText(bool $isNeedAuth = false): bool
    {
        if ($this->boolIsTurnedOff) {
            TOOLS::$log->warn("Отправка писем отключена! Смотри значение переменной 'boolIsTurnedOff'", __METHOD__);
            return true;
        }

        return $this->send(self::SMTP_TYPE_MESSAGE_TEXT, $isNeedAuth);
    }

    /**
     * Отправляет сообщение с типом Html (учитывается boolIsTurnedOff)
     * @param bool $isNeedAuth Авторизоваться на сервере SMTP (если это требует протокол)
     * @return bool Успешно Да/нет
     **/
    public function sendHtml(bool $isNeedAuth = false): bool
    {
        if ($this->boolIsTurnedOff) {
            TOOLS::$log->warn("Отправка писем отключена! Смотри значение переменной 'boolIsTurnedOff'", __METHOD__);
            return true;
        }

        return $this->send(self::SMTP_TYPE_MESSAGE_HTML, $isNeedAuth);
    }

    /**
     * Отправка email-сообщения через SMTP, для публичного API см. sendText/sendHtml и т.п.
     * Здесь не учитывается boolIsTurnedOff.
     * @param int $intMessageType Тип почтового сообщения (можно передавать int)
     * @param bool $isNeedAuth Авторизоваться на сервере SMTP (если это требует протокол)
     * @return bool
     **/
    protected function send(int $intMessageType, bool $isNeedAuth = false): bool
    {
        $this->checkRequiredParameters();

        $bResult = false;
        try {
            $isConnected = $this->connect();
            if (!$isConnected)
                return false;

            if ($isNeedAuth) {
                TOOLS::$log->debug("Выполнить авторизацию", __METHOD__);
                if (!WEB::$mail->check_smtp_auth())
                {
                    TOOLS::$log->error("Не удалось авторизоваться", __METHOD__);
                    return false;
                }
            }
            $k = 0;
            do {
                $k++;
                TOOLS::$log->debug("Попытка отправить письмо #$k ", __METHOD__);

                $bResult = WEB::$mail->send_mail_via_smtp(
                    $this->strFrom,
                    $this->strTo,
                    $this->strSubject,
                    $this->strMessage,
                    $intMessageType,
                    $this->strCopy,
                    $this->strHiddenCopy,
                    $this->arrAttachments,
                    $this->intSendTimeout,
                    $this->strReplyTo
                );
                if (!$bResult){
                    TOOLS::$log->warn("Письмо отправить не удалось", __METHOD__);
                    TOOLS::$log->debug("Пауза между попытками отправить письмо. Ожидаем " . $this->waitBetweenTryToSend . " сек.", __METHOD__);

                    $m = 0;
                    while ($m < $this->waitBetweenTryToSend){
                        $m++;
                        sleep(1);
                        echo '.';
                    }
                }
                else
                    break;
            } while ($k < $this->sendTryCount);


            if (!$bResult)
                TOOLS::$log->error("Не смогли отправить email", "MailerSMTP.send");
        }
        finally {
            $this->disconnect();
            $this->clear();
        }

        return $bResult;
    }

    /**
     * Отключение от SMTP сервера
     * @return bool
     **/
    protected function disconnect(): bool
    {
        return WEB::$mail->smtp_disconnect();
    }
}
