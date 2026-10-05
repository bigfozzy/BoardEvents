<?php

/**
 * Class SETTINGS управление настройками робота
 * доступ к статическим экземплярам объектов модулей-хелперов
 */
class SETTINGS
{
    /**
     * @var SettingsHelper управление настройками робота
     * Парсинг словаря настроек из ./settings/settings.json
     * и генерация глобальных переменных из этого словаря
     */
    public static SettingsHelper $settings;

    /**
     * Инициация статических полей
     */
    public static function __constructStatic(): void
    {
        SETTINGS::$settings = new SettingsHelper();
    }
}

