<?php

/**
 * Пример:
 * ```
 *	if(isset($settingsFilePath) && file_exists($settingsFilePath)) {
 *      SETTINGS::$settings->addSettingsFromFile($settingsFilePath);
 *  } elseif (isset($settingsJson)) {
 *      SETTINGS::$settings->addSettingsFromJson($settingsJson);
 *  }
 *
 *  if(isset($settingsName) && !empty($settingsName)) {
 *      $settings = SETTINGS::$settings->setSettings($settingsName);
 *  }
 * ```
 */
class SettingsHelper
{
    /**
     * Задает значения для настроек и записывает данные настроек в глобальный массив
     *
     * @param string $strSettingsName  Название шаблона настроек (можно передавать string)
     * @return bool
     */
    public function setSettings(string $strSettingsName): bool
    {
        global $settingseditor;

        if(!$this->isExistSettings($strSettingsName)) {
            return false;
        }

        $settingsJson = $settingseditor->set_settings($strSettingsName);

        $settingsData = json_decode($settingsJson, true);

        if(!isset($settingsData['IsSettingsInput'])
            || ($settingsData['IsSettingsInput'] === false)) {
            return false;
        }

        $this->saveSettingsToGlobals($settingsData);

        return true;
    }

    /**
     * Записывает переданный массив настроек в глобальный массив $GLOBALS
     *
     * @param array $arrSettingsData  Массив настроек (можно передавать array)
     */
    public function saveSettingsToGlobals(array $arrSettingsData) {
        foreach ($arrSettingsData as $settingName => $settingValue){
            if($settingName == 'IsSettingsInput') {
                continue;
            }

            if(!isset($GLOBALS[$settingName])) {
                $GLOBALS[$settingName] = $settingValue;
            }
        }
    }

    /**
     * Записывает переданный массив настроек из INI в глобальный массив $GLOBALS
     *
     * @param array $arrSettingsData        Массив настроек (нужно передавать array)
     * @param bool  $ini_process_sections   Если обрабатываем секции INI, то добавляем первый уровень секций в выходной массив
     */
    public function saveIniSettingsToGlobals(array $arrSettingsData, bool $ini_process_sections) {
        // Один уровень массива INI без секций
        if (!$ini_process_sections) {
            foreach ($arrSettingsData as $settingName => $settingValue) {
                $GLOBALS[$settingName] = $GLOBALS[$settingName] ?? $settingValue;
            }
            return;
        }

        // Два уровня INI, а секции в первом уровне
        foreach ($arrSettingsData as $level1Name => $level1Value) {
            if (is_array($level1Value)) {
                // тут с обработкой секций.
                // из названия секции создаётся глобальная переменная (array),
                // затем из названия настройки и её значения создаётся key => value.
                if (isset($tmparr)) { unset($tmparr); }
                $tmparr = [];

                foreach ($level1Value as $level2Name => $level2Value) {
                    $tmparr[$level2Name] = $tmparr[$level2Name] ?? $level2Value;
                }

                $GLOBALS[$level1Name] = $GLOBALS[$level1Name] ?? $tmparr;
            } else {
                // а тут без обработки секций (можно же просто в INI переменные
                // без секций... но это уже про тон записи в INI, но такое есть)
                $GLOBALS[$level1Name] = $GLOBALS[$level1Name] ?? $level1Value;
            }
        }
    }

    /**
     * Добавить настройку из JSON файла (если настройка существует, она заменяется на новую)
     *
     * @param string $strFilePath Путь к файлу с настройками (можно передавать string)
     * @return bool
     */
    public function addSettingsFromFile(string $strFilePath): bool
    {
        global $settingseditor;

        return $settingseditor->add_setting_from_file($strFilePath);
    }

    /**
     * Добавить настройку из строки JSON
     *
     * @param string $jsonData  Строка JSON (можно передавать JSON)
     * @return bool
     */
    public function addSettingsFromJson(string $jsonData): bool
    {
        global $settingseditor;

        return $settingseditor->add_setting_from_json($jsonData);
    }

    /**
     * Проверка существует ли шаблон настройки по указанному имени
     *
     * @param string $strSettingsName Название шаблона настроек (можно передавать string)
     * @return bool
     */
    public function isExistSettings(string $strSettingsName): bool
    {
        global $settingseditor;

        return $settingseditor->is_exist($strSettingsName);
    }

    /**
     * @param string $settingsName имя настроек файла (важно с использованием диалога настроек)
     * @param string $settingsFilePath путь до файла настроек
     * @param bool $ini_process_sections если true вернуть многомерный массив, который включает как название отдельных настроек, так и секции. Это второй параметр функции https://www.php.net/manual/ru/function.parse-ini-file
     * @return void
     */
    public function selfConfigure (
        string &$settingsName,
        string $settingsFilePath,
        bool $ini_process_sections = false
    ): void
    {
        if(!isset($settingsFilePath) || !SYSTEM::$file_os->is_exist($settingsFilePath))
        {
            TOOLS::$log->error("Не удалось найти файл с настройками! Укажите правильный путь к файлу" . var_export($settingsFilePath, true), static::class );
            TOOLS::$app->quit();
        }

        $settingsType = strtolower(trim(SYSTEM::$file_os->get_ext($settingsFilePath)));
        TOOLS::$log->info("Тип настроек: $settingsType");

        // INI

        if ($settingsType == 'ini') {
            if (!$settingsArrData = parse_ini_file($settingsFilePath, $ini_process_sections)){
                TOOLS::$log->error("Ошибка париснга ini настроек!", __METHOD__);
                TOOLS::$app->quit();
            }

            $settingsName = $settingsArrData['Name'] = 'INI';
            TOOLS::$log->debug("Настройки:" . PHP_EOL . var_export($settingsArrData, true));

            $this->saveIniSettingsToGlobals($settingsArrData, $ini_process_sections);

            return;
        }

        // JSON

        $settingsFileData = SYSTEM::$textfile->read_file($settingsFilePath, encoding: "UTF-8");
        if(!$settingsFileData) {
            TOOLS::$log->error("Не удалось прочитать файл с настройками и определить название шаблона настроек");
            TOOLS::$app->quit();
        }

        $settingsArrData = @json_decode($settingsFileData, true);
        //TOOLS::$log->debug("Настройки: " . PHP_EOL . var_export($settingsArrData, true));

        // JSON без окна настроек
        if ( (!isset($settingsArrData['Name'])) or empty($settingsArrData['Name']) ) {
            $settingsName = $settingsArrData['Name'] = 'JSON';

            $this->saveSettingsToGlobals($settingsArrData);

            return;
        }

        // окно с настройками (JSON)

        $settingsName = $settingsArrData['Name'];

        if(!$settingsName) {
            TOOLS::$log->error("Не удалось определить название шаблона настроек" . var_export($settingsArrData, true), static::class);
            TOOLS::$app->quit();
        } else {
            TOOLS::$log->info("Используем следующий шаблон настроек $settingsName", static::class);
        }

        if(!SETTINGS::$settings->addSettingsFromFile($settingsFilePath))
        {
            TOOLS::$log->error("Не удалось загрузить настройки из файла" . var_export($settingsFilePath, true), static::class);
            TOOLS::$app->quit();
        }

        if(!SETTINGS::$settings->setSettings($settingsName))
        {
            TOOLS::$log->error("Не удалось получить список настроек", static::class);
            TOOLS::$app->quit();
        }
    }
}
