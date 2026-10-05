<?php

class SystemHelper
{
    /**
     * Ожидание начала загрузки файла с учетом id предыдущей загрузки
     * @param int $previousDownloadId Id предыдущей загрузки
     * @param int $waitSeconds Время ожидания загрузки
     * @return int|bool $downloadId Идентификатор загрузки
     * @vesrsion 0.2.1
     */
    function waitDownloadingFile(int $previousDownloadId, int $waitSeconds = 80) : int|bool
    {
        global $browser, $webpage, $body;

        $m = 0;
        while (true) {
            $m++;
            $downloadId = $browser->get_last_download_id();
            if($downloadId != -1 && $downloadId != $previousDownloadId ){
                break;
            }
            sleep(1);
            echo('.');
            if($m > $waitSeconds){
                /*
                // ищем json сообщение с ошибкой
                $bodyInnerText = $body->get_by_number(0)->get_inner_text();
                if(str_contains($bodyInnerText, 'localizedMessage') ){
                    $errorMessage = $webpage->get_body_inter_prefix('localizedMessage":"', '","');
                    TOOLS::$log->error("Файл не загрузился! Ошибка: $errorMessage", 'functions');
                }
                else
                {
                    TOOLS::$log->error("Файл не загрузился!", 'functions');
                }*/
                TOOLS::$log->error("Файл не загрузился! Не дождались ID загрузки", 'systemHelper');
                return false;
            }
        }

        $m = 0;
        while (true) {
            $m++;
            if($browser->is_download_complete($downloadId)){
                break;
            }
            sleep(1);
            echo('.');
            if($m > 80){
                TOOLS::$log->error("Файл не загрузился!", 'functions');
                return false;
            }
        }
        return $downloadId;
    }


    /**
     * Очистить JSON строку от спецсимволов типа nbsp
     * @param string $jsonStr строка JSON
     * @return string строка JSON
     */
    static function clearJsonStr(string $jsonStr): string
    {
        $jsonStr = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $jsonStr );
        return trim($jsonStr,chr(0xC2).chr(0xA0));
    }

    /**
     * Скопировать файлы в папку
     * @param string $intranetFolder путь к папке (сетевой) для копирования файлов
     * @param string[] $files файлы для копирования
     */
    function copyFileToIntranetFolder(string $intranetFolder, array $files): void
    {
        global $folder, $file_os;

        if (isset($intranetFolder)) {
            if ($folder->is_exist($intranetFolder))
            {
                try {
                    foreach ($files as $fileToCopy) {
                        if( $file_os->is_exist($fileToCopy))
                        {
                            if( $file_os->copy($fileToCopy, $intranetFolder . basename($fileToCopy)))
                                TOOLS::$log->info("Копировании файла '$fileToCopy' в папку: '$intranetFolder' прошло успешно", 'functions');
                            else
                                TOOLS::$log->warn("Копировать файл '$fileToCopy' в папку: '$intranetFolder' не удалось", 'functions');
                        }
                        else
                        {
                            TOOLS::$log->warn("Файл '$fileToCopy' не найден", 'functions');
                        }
                    }
                }
                catch (Exception $ex) {
                    TOOLS::$log->error("Ошибка при копировании файла '$fileToCopy' в папку: '$intranetFolder'. " . $ex->getMessage());
                }
            }
            else
            {
                TOOLS::$log->warn("Сетевая папка не существует. Путь: '$intranetFolder'");
            }
        }
        else{
            TOOLS::$log->warn("Глобальная переменная 'intranetFolder' не имеет значения");
        }
    }
}