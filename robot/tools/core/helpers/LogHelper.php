<?php

/**
 * Логировать сообщение в Панель Отладки (stdout) и в файл (txt, html),
 *
 * @param string $msg Лог сообщение, автоматически преобразуется (var_export) в строку
 * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
 * @deprecated Use class XHELogger
 * @return void
 */
function debug_mess(string $msg, bool $isBold = false): void
{
    SYSTEM::$logger->debug($msg, null, isBold: $isBold);
}

/**
 * Обертка для class XHELogger для поддержания устаревшей функциональности.
 * Использовать в новых проектах класс XHELogger
 */
class LogHelper
{
    function __construct()
    {
        global $debug_file, $logFilePath, $PHP_Use_Trought_Shell, $log_level, $debug_panel_translit_text;

        // Поддержка устаревшего названия '$debug_file'.
        // Вместо '$debug_file' используй $logFilePath
        if (isset($debug_file))
            $logFilePath = $debug_file;

        // Init Logger
        SYSTEM::$logger->init($log_level,
            $PHP_Use_Trought_Shell,
            true,
            $logFilePath,
            "",
            "d-m-Y H:i:s",
            "ru",
            $debug_panel_translit_text,
            true
        );
    }

    /**
     * Логировать сообщение trace в Панель Отладки (stdout) и в файл (txt, html)
     *
     * @param string $msg Лог сообщение
     * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например: "main", "test123" или ""
     * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
     * @param mixed|null $data Данные любого типа для автоматического преобразования в объект
     * @return void
     */
    public function trace(string $msg, string $componentName = '', bool $isBold = false, mixed $data = null): void
    {
        SYSTEM::$logger->trace($msg, $data, $componentName, $isBold);
    }

    /**
     * Логировать сообщение в Панель Отладки (stdout) и в файл (txt, html)
     *
     * @param string $msg Лог сообщение
     * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например: "main", "test123" или ""
     * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
     * @param mixed|null $data Данные любого типа для автоматического преобразования в объект
     * @return void
     */
    public function debug(string $msg, string $componentName = '', bool $isBold = false, mixed $data = null): void
    {
        SYSTEM::$logger->debug($msg, $data, $componentName, $isBold);
    }

    /**
     * Логировать сообщение (ИНФО) в Панель Отладки (stdout) и в
     * файл (txt, html), уровень логирования (log_level) учитывается
     * @param string $msg Лог сообщение
     * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например: "main", "test123" или ""
     * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
     * @param mixed|null $data Данные любого типа для автоматического преобразования в объект
     * @return void
     */
    public function info(string $msg, string $componentName = '', bool $isBold = false, mixed $data = null): void
    {
        SYSTEM::$logger->info($msg, $data, $componentName, $isBold);
    }

    /**
     * Логировать сообщение (ПРЕДУПРЕЖДЕНИЕ) в Панель Отладки (stdout) и в файл (txt, html), уровень логирования (log_level) учитывается
     *
     * @param string $msg Лог сообщение
     * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например: "main", "test123" или ""
     * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
     * @param mixed|null $data Данные любого типа для автоматического преобразования в объект
     * @return void
     */
    public function warn(string $msg, string $componentName = '', bool $isBold = false, mixed $data = null): void
    {
        SYSTEM::$logger->warn($msg, $data, $componentName, $isBold);
    }

    /**
     * Логировать сообщение (ОШИБКА) в Панель Отладки (stdout) и в файл (txt, html), уровень логирования (log_level) учитывается
     *
     * @param string $msg Лог сообщение
     * @param string $componentName (по-умолчанию: "") Место вызова (компонент) (например: "main", "test123" или ""
     * @param bool $isBold Выделять "жирным" сообщение или нет (по-умолчанию - нет)
     * @param mixed|null $data Данные любого типа для автоматического преобразования в объект
     * @return void
     */
    public function error(string $msg, string $componentName = '', bool $isBold = false, mixed $data = null): void
    {
        SYSTEM::$logger->error($msg, $data, $componentName, $isBold);
    }
}