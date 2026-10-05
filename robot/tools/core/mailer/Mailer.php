<?php

/**
 * Пример (SMTP):
 * ```
 *    TOOLS::$mailer::makeSMTP(
 *        $strServer,
 *        $intPort,
 *        $strLogin,
 *        $strPassword,
 *        $intSslOption,
 *        $strCertType,
 *        $intConnectTimeout,
 *        $strLogPath
 *    );
 *    TOOLS::$mailer::getMailer()
 *        ->setIsTurnedOff(true)
 *        ->setFrom('test@example.ru')
 *        ->setTo('test@example.ru;test2@example.ru')
 *        ->setCopy('test3@example.ru;test4@example.ru')
 *        ->setSubject('test subj')
 *        ->setMessage('test message');
 *        ->setAttachments([$filepath1, $filepath2]);
 *    $res = TOOLS::$mailer::getMailer()->sendText();
 *    TOOLS::$log->debug($res);
 * ```
 *
 * Пример (MS Outlook):
 * ```
 *    TOOLS::$mailer::makeOutlook();
 *    TOOLS::$mailer::getMailer()
 *        ->setIsTurnedOff(true)
 *        ->setFrom('test@example.ru')
 *        ->setTo('test@example.ru;test2@example.ru')
 *        ->setCopy('test3@example.ru;test4@example.ru')
 *        ->setSubject('test subj')
 *        ->setMessage('test message');
 *        ->setAttachments([$filepath1, $filepath2]);
 *    $res = TOOLS::$mailer::getMailer()->sendText();
 *    TOOLS::$log->debug($res);
 * ```
 */
class Mailer
{
    private static $objMailer = null;

    /**
     * @var string значение Кому отправить E-mail
     */
    protected string $strTo = '';

    /**
     * @var string E-mail адрес отправителя
     */
    protected string $strFrom = '';

    /**
     * @var string Тема письма
     */
    protected string $strSubject = '';

    /**
     * @var string Тело письма
     */
    protected string $strMessage = '';

    /**
     * @var string E-mail адрес для отправки копии письма
     */
    protected string $strCopy = '';

    /**
     * @var string E-mail адрес для отправки скрытой копии письма
     */
    protected string $strHiddenCopy = '';

    /**
     * @var array|null Пути к прикрепленным файлам
     */
    protected array|null $arrAttachments = null;

    /**
     * @var int таймаут ожидания отправки
     */
    protected int $intSendTimeout = 300;

    /**
     * @var string указать адрес, отличный от From адреса, используемого для ответа на это сообщение.
     */
    protected string $strReplyTo = '';

    /**
     * @var bool Флаг для отключения отправки писем (TRUE - не отправлять письмо)
     */
    protected bool $boolIsTurnedOff = false;

    /**
     * Создает объект MailerOutlook для отправки сообщений через MS Outlook
     * @return void
     **/
    public static function makeOutlook(): void
    {
        self::$objMailer = new MailerOutlook();
    }

    /**
     * Создает объект MailerSMTP для отправки сообщений через smtp
     * @param string $server адрес smtp сервера (можно передавать string)
     * @param int $port порт smtp сервера (можно передавать int)
     * @param string $login login для smtp сервера (можно передавать string)
     * @param string $password пароль для smtp сервера (можно передавать string)
     * @param string $secureTypeName тип защиты соединения: ''(определять автоматически), 'none', 'ssl', 'starttls', 'tls'
     * @param string $certType типы сертификата аутентификации через SSL "s, c, h, e"
     * @param int $timeout (можно передавать int)
     * @param string $logPath путь к файлу логов работы протоколов
     * @return void
     * @throws Exception Если входные аргументы ошибочные
     * @see Порядок подключения по SMTP — в официальной документации продукта
     */
    public static function createSMTP(
        string $server,
        int    $port,
        string $login,
        string $password,
        string $secureTypeName = 'ssl',
        string $certType = "s, c, h, e",
        int    $timeout = 3000,
        string $logPath = ''
    ): void
    {
   
        if (empty($server) || empty($login) || empty($password)) {
            throw new Exception("Не удалось создать объект MailerSMTP. Обязательные аргументы не получили значения.");
        }

        $intSslOption = MailerHub::getSSLOptionByName($secureTypeName);

        self::$objMailer = new MailerSMTP($server, $port, $login, $password, $intSslOption, $certType, $timeout, $logPath);
    }

    /**
     * Создает объект MailerSMTP для отправки сообщений через smtp
     * @param string $strServer адрес smtp сервера (можно передавать string)
     * @param int $intPort порт smtp сервера (можно передавать int)
     * @param string $strLogin login для smtp сервера (можно передавать string)
     * @param string $strPassword пароль для smtp сервера (можно передавать string)
     * @param int $intSslOption тип защиты соединения. 0 : без использования SSL или TLS; 1 : Автоматически определять; 2 : Соединение должно использовать SSL или TLS; 3 : Использовать TLS + STARTTLS ; 4 : Использовать TLS.
     * @param string $strCertType типы сертификата аутентификации через SSL "s, c, h, e"
     * @param int $intConnectTimeout (можно передавать int)
     * @param string $strLogPath путь к файлу логов работы протоколов
     * @return void
     * @see Порядок подключения по SMTP — в официальной документации продукта
     */
    public static function makeSMTP(
        string $strServer,
        int $intPort,
        string $strLogin,
        string $strPassword,
        int $intSslOption = 1,
        string $strCertType = "s, c, h, e",
        int $intConnectTimeout = 3000,
        string $strLogPath = ''
    ): void
    {
        if (empty($strServer) || empty($strLogin) || empty($strPassword)) {
            TOOLS::$log->warn("Не удалось создать объект MailerSMTP. Обязательные аргументы не получили значения.", __METHOD__);
            return;
        }
        self::$objMailer = new MailerSMTP($strServer, $intPort, $strLogin, $strPassword, $intSslOption, $strCertType, $intConnectTimeout, $strLogPath);
    }

    /**
     * Получения текущий объект MailerSMTP/MailerOutlook
     * @return MailerSMTP|MailerOutlook
     **/
    public static function getMailer()
    {
        return self::$objMailer;
    }

    /**
     * Устанавливает email на который будет отправляться почтовое сообщение
     * @param string|array $strValue Email на который будет отправляться почтовое сообщение (можно передавать string или array)
     * @return self
     **/
    public function setTo(string|array $strValue)
    {
        if (is_array($strValue))
            $strValue = implode(';', $strValue);

        $this->strTo = $strValue;
        TOOLS::$log->info("mailto: $strValue", __METHOD__);
        return $this;
    }

    /**
     * Устанавливает email с которого будет происходить отправка почтового сообщения
     * @param string $strValue Email с которого будет происходить отправка почтового сообщения (можно передавать string)
     * @return self
     **/
    public function setFrom(string $strValue)
    {
        $this->strFrom = $strValue;
        return $this;
    }

    /**
     * Устанавливает тему почтового сообщения
     * @param string $strValue Тема почтового сообщения
     * @return self
     **/
    public function setSubject(string $strValue)
    {
        $this->strSubject = $strValue;
        return $this;
    }

    /**
     * Устанавливает тело почтового сообщения
     * @param string $strValue Тело почтового сообщения
     * @return self
     **/
    public function setMessage(string $strValue)
    {
        $this->strMessage = $strValue;
        return $this;
    }

    /**
     * Устанавливает CC для почтового сообщения
     * @param string|array $strValue Сс для почтового сообщения (можно передавать string или array)
     * @return self
     **/
    public function setCopy(string|array $strValue)
    {
        if (is_array($strValue))
            $strValue = implode(';', $strValue);

        $this->strCopy = $strValue;
        return $this;
    }

    /**
     * Устанавливает BCC для почтового сообщения
     * @param string|array $strValue Bcc для почтового сообщения (можно передавать string или array)
     * @return self
     **/
    public function setHiddenCopy(string|array $strValue)
    {
        if (is_array($strValue))
            $strValue = implode(';', $strValue);

        $this->strHiddenCopy = $strValue;
        return $this;
    }

    /**
     * Прикрепляет файлы к почтовому сообщению
     * @param string|array|null $arrValue Массив с файлами, которые нужно прикрепить к почтовому сообщению (можно передавать один файл в виде строки)
     * @return self
     **/
    public function setAttachments(string|array|null $arrValue)
    {
        if (!is_array($arrValue))
            $arrValue = [strval($arrValue)];

        $this->arrAttachments = $arrValue;
        return $this;
    }

    /**
     * Send email timeout
     * @param int $intValue Timeout (можно передавать int)
     * @return self
     **/
    public function setSendTimeout(int $intValue)
    {
        $this->intSendTimeout = $intValue;
        return $this;
    }

    /**
     * Установить Reply-To
     * @param string $strValue
     * @return self
     **/
    public function setReplyTo(string $strValue)
    {
        $this->strReplyTo = $strValue;
        return $this;
    }

    /**
     * Получает флаг, который показывает включена ли отправка почты
     * @return bool
     **/
    public function getIsTurnedOff(): bool
    {
        return $this->boolIsTurnedOff;
    }

    /**
     * Выключает или включает отправку почты
     * @param bool $boolValue
     * @return self
     **/
    public function setIsTurnedOff(bool $boolValue)
    {
        $this->boolIsTurnedOff = $boolValue;
        return $this;
    }



    /**
     * Производит очистку данных после отправки сообщения
     * @return void
     **/
    protected function clear()
    {
        $defValues = get_class_vars(get_class($this));

        $this->strFrom = $defValues['strFrom'];
        $this->strTo = $defValues['strTo'];
        $this->strSubject = $defValues['strSubject'];
        $this->strMessage = $defValues['strMessage'];
        $this->strCopy = $defValues['strCopy'];
        $this->strHiddenCopy = $defValues['strHiddenCopy'];
        $this->arrAttachments = $defValues['arrAttachments'];
        $this->intSendTimeout = $defValues['intSendTimeout'];
        $this->strReplyTo = $defValues['strReplyTo'];
    }

    /**
     * Производит проверку обязательных для отправки почтового сообщения данных
     * @return bool
     **/
    protected function checkRequiredParameters()
    {
        if (empty($this->strFrom) || empty($this->strTo) || empty($this->strSubject)) {
            TOOLS::$log->error("Обязательные параметры не заполнены (from/to/subject)", "Mailer.checkRequiredParameters");
            return false;
        }
        return true;
    }
}
