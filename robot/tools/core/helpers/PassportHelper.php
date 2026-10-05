<?php

/**
 * Класс для работы с файлом passport.json
 * Плагин "Паспорт робота" находится в верхнем меню: Плагины->Код->Роботы->Паспорт робота
 */
class PassportHelper
{
    /**
     * Путь к файлу паспорта Робота
     */
    private string $passportFilePath;

    public function __construct()
    {
        global $robotPassportFilePath;

        if (isset($robotPassportFilePath))
            $this->passportFilePath = $robotPassportFilePath;
        else
            $this->passportFilePath = getcwd() . "/passport.json";
    }

    /**
     * Установить новое значение для пути к файлу паспорта Робота
     * @param string $robotPassportFilePath
     */
    public function setPassportFilePath(string $robotPassportFilePath): void
    {
        $this->passportFilePath = $robotPassportFilePath;
    }

    /**
     * Существует ли паспорт настройки по указанному пути
     *
     * @return bool
     */
    public function isExistPassport(): bool
    {

        if( !SYSTEM::$file_os->is_exist($this->passportFilePath))
        {
            TOOLS::$log->error("Не удалось найти файл с паспортом Робота! Укажите правильный путь к файлу" . $this->passportFilePath, __METHOD__ );
            return false;
        }
        TOOLS::$log->debug("Файл с паспортом Робота найден" . $this->passportFilePath, __METHOD__ );
        return true;
    }

    /**
     * Получить номер версии робота
     * @return false|string номер версии Робота
     */
    public function getRobotVersion(): bool|string{

        $obj = $this->getObj();
        if( !$obj)
            return false;

        return $obj->PassportVersion;
    }

    /**
     * Получить динамический объект с полями паспорта
     * @return bool|object
     */
    public function getObj(): bool|object
    {
        if( !SYSTEM::$file_os->is_exist($this->passportFilePath))
        {
            TOOLS::$log->error("Не удалось найти файл с паспортом Робота! Укажите правильный путь к файлу" . $this->passportFilePath, __METHOD__ );
            return false;
        }

        $jsonString = SYSTEM::$textfile->read_file($this->passportFilePath, encoding: "UTF-8");
        return @json_decode($jsonString);
    }
}
