<?php

/**
 * Базовый класс для классов чтения E-mail сообщений
 */
class MailerHub
{
    private static $objMailerHub = null;

    /**
     * @var string Адрес почтового сервера
     */
    protected string $server;

    /**
     * @var int Номер порта почтового сервера
     */
    protected int $port;

    /**
     * @var string Имя пользователя почтового сервера
     */
    protected string $login;

    /**
     * @var string Пароль пользователя почтового сервера
     */
    protected string $password;

    /**
     * @var int тип защиты соединения. 0 : без использования SSL или TLS; 1 : Автоматически определять; 2 : Соединение должно использовать SSL или TLS; 3 : Использовать TLS + STARTTLS ; 4 : Использовать TLS.
     */
    protected int $sslOption;

    /**
     * @var string типы сертификата аутентификации через SSL "s, c, h, e"
     */
    protected string $certType;

    /**
     * @var int таймаут, сек
     */
    protected int $timeout;

    /**
     * Создает объект MailerHubIMAP для получения сообщений через IMAP
     * @param string $server адрес smtp сервера (можно передавать string)
     * @param int $port порт smtp сервера (можно передавать int)
     * @param string $login login для smtp сервера (можно передавать string)
     * @param string $password пароль для smtp сервера (можно передавать string)
     * @param string $secureTypeName тип защиты соединения: ''(определять автоматически), 'none', 'ssl', 'starttls', 'tls'
     * @param string $strCertType типы сертификата аутентификации через SSL "s, c, h, e"
     * @param int $intConnectTimeout (можно передавать int)
     * @return void
     * @throws Exception
     */
    public static function createIMAP(
        string $server,
        int    $port,
        string $login,
        string $password,
        string $secureTypeName = 'ssl',
        string $strCertType = "s, c, h, e",
        int    $intConnectTimeout = 3000
    ): void
    {
        if (empty($server) || empty($login) || empty($password)) {
            throw new Exception("Не удалось создать объект MailerHubIMAP. Обязательные аргументы не получили значения.");
        }

        $intSslOption = self::getSSLOptionByName($secureTypeName);
        self::$objMailerHub = new MailerHubIMAP($server, $port, $login, $password, $intSslOption, $strCertType, $intConnectTimeout);
    }

    /**
     * Получить текущий объект для чтения писем MailerHubIMAP
     * @return MailerHubIMAP|null
     */
    public static function getMailerHub()
    {
        return self::$objMailerHub;
    }

    /**
     * Определить значение протокола безопасности транспортного уровня по названию протокола (SSL, TLS, STARTTLS)
     * @param string $secureTypeName наименование протокола безопасности транспортного уровня
     * @return int SSL Option
     * @throws Exception не удалось определить
     */
    public static function getSSLOptionByName(string $secureTypeName): int
    {
        if (empty($secureTypeName))
            $intSslOption = 1;
        else if (strtolower($secureTypeName) == 'none') {
            $intSslOption = 0;
        } else if (strtolower($secureTypeName == 'ssl')) {
            $intSslOption = 2;
        } else if (strtolower($secureTypeName == 'starttls')) {
            $intSslOption = 3;
        } else if (strtolower($secureTypeName == 'tls')) {
            $intSslOption = 4;
        } else
            throw new Exception("Тип защиты SMTP по значению аргумента smtpSecure не удалось определить: $secureTypeName");

        TOOLS::$log->info("Для smtpSecure = $secureTypeName определено значение intSslOption = $intSslOption", __METHOD__);
        return $intSslOption;
    }


}
