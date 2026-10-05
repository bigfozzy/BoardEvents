<?php

/*
    Пример использования

    $csv = new CsvHelper($csvPath, 5, 15, 'UTF-8', 'windows-1251');

    $dataSourceRow = [
        'ID',
        'НАЗВАНИЕ',
        'ОПИСАНИЕ',
        'ДАТА',
    ];

    if (!$csv->addRow($dataSourceRow)) {
        TOOLS::$log->error("Не удалось добавить строку в CSV файл", __METHOD__, true);

        return;
    }

    $csv->setEncodingSource('windows-1251');
    $csv->setEncodingRes('UTF-8');

    $dataRow = $csv->getRow();
*/

class CsvHelper
{
    /**
     * @var string Путь к CSV файлу
     */
    private string $filePath;
    public bool $isHeaderSet = false;

    /**
     * @var int Число попыток изменить файл. Если другой процесс занимает файл (например антивирус)
     */
    private int $maxTryCount;


    /**
     * @var int Пауза между попытками, сек., изменить файл
     */
    private int $pause;

    /**
     * @var string Кодировка исходного CSV файла
     */
    private string $encodingSource;
    /**
     * @var string Кодировка CSV файла который получается на выходе
     */
    private string $encodingRes;
    /**
     * @var string Разделитель полей в CSV файле
     */
    private string $delimiter = ';';
    /**
     * @var string Символ конца строки в CSV файле
     */
    private string $eol = PHP_EOL;

    /**
     * Конструктор
     * @param string $filePath путь к файлу CSV
     * @param int $maxTryCount Число попыток изменить файл. Если другой процесс занимает файл (например антивирус).
     * @param int $pause Пауза между попытками, сек.
     * @param string $encodingSource кодировка исходного CSV файла
     * @param string $encodingRes кодировка CSV файла который получается на выходе
     * @throws Exception
     */
    function __construct(string $filePath, int $maxTryCount = 5, int $pause = 15, string $encodingSource = 'Windows-1251', string $encodingRes = '')
    {
        if (empty($filePath))
            throw new Exception("CsvHelper constructor. Не указан путь к CSV файлу");

        $this->filePath = $filePath;

        /*if(!$this->fileIsExists())
            throw new Exception("CsvHelper constructor. Файл не найден '$filePath'");*/

        $this->maxTryCount = $maxTryCount;
        $this->pause = $pause;
        $this->encodingSource = $encodingSource;
        $this->encodingRes = $encodingRes;
    }

    /**
     * Получить путь к CSV файлу
     * @return string
     * @version 0.1
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Проверить существование файла CSV
     * @return bool
     * @version 0.2
     */
    public function fileIsExists(): bool
    {
        if (empty($this->filePath)) {
            TOOLS::$log->error("Не указан путь к CSV файлу", __METHOD__);
            return false;
        }

        if (!file_exists($this->filePath)) {
            TOOLS::$log->error("CSV файла не существует '$this->filePath'", __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Считывает все строки из CSV файла
     * @return array
     * @version 0.2
     */
    public function readAllRows(): array
    {
        $result = [];

        $file = new \SplFileObject($this->filePath);

        $first = true;
        while (!$file->eof()) {
            $dataRow = $file->fgetcsv($this->delimiter);
            if ($dataRow === false){
                echo("Ошибка при чтении файла" . PHP_EOL);
                break;
            }

            // Убрать BOM символы
            if ($first) {
                // Убрать BOM символы
                if (count($dataRow) > 0) {
                    $firstVal = $dataRow[0];
                    $bom = pack('CCC', 0xEF, 0xBB, 0xBF);

                    if (substr($firstVal, 0, 3) === $bom){
                        $firstVal = substr($firstVal, 3);
                        $dataRow[0] = $firstVal;
                    }
                }
                $first = false;
            }

            if ($dataRow[0] == '') {
                continue;
            }

            if (!empty($this->encodingRes)) {
                array_walk_recursive($dataRow, function (&$value, $key) {
                    if ($value !== '') {
                        $value = iconv($this->encodingSource, $this->encodingRes, $value);
                    }
                });
            }

            $result[] = $dataRow;
        }

        $file = null;

        return $result;
    }

    /**
     * Установить кодировку исходного CSV файла
     * @param string $encoding - кодировка исходного CSV файла
     * @return void
     * @version 0.1
     */
    public function setEncodingSource(string $encoding): void
    {
        $this->encodingSource = $encoding;
    }

    /**
     * Установить кодировку CSV файла результата
     * @param string $encoding - кодировка CSV файла результата
     * @return void
     */
    public function setEncodingRes(string $encoding): void
    {
        $this->encodingRes = $encoding;
    }

    /**
     * Получить разделитель полей в CSV файле
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Установить разделитель полей в CSV файле
     * @param string $delimiter - разделитель полей в CSV файле
     * @return void
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Установить символ конца строки в CSV файле
     * @param string $eol - символ конца строки
     * @return void
     */
    public function setEOL(string $eol): void
    {
        $this->eol = $eol;
    }

    /**
     * Добавляем строку в CSV файл
     * @param array $arData - данные записываемой строки
     * @return bool Успешно? Да/Нет
     * @version 0.2
     */
    public function addRow(array $arData): bool
    {
        $file = new \SplFileObject($this->filePath, 'a');

        if (!empty($this->encodingRes)) {
            array_walk_recursive($arData, function (&$value, $key) {
                if ($value !== '') {
                    $value = iconv($this->encodingSource, $this->encodingRes, $value);
                }
            });
        }
        $saveResult = false;
        for ($k = 1; $k <= $this->maxTryCount; $k++) {
            $saveResult = $file->fputcsv($arData, $this->delimiter, '"', eol: $this->eol);

            if ($saveResult !== false) {
                break;
            }

            TOOLS::$log->warn("Попытка #$k/$this->maxTryCount. Не удалось добавить строку в CSV файл " . $this->filePath . ". Пауза между попытками $this->pause сек", __METHOD__);

            sleep($this->pause);
        }

        $file = null;

        if (!$saveResult)
            throw new Exception("CsvHelper addRow. Не удалось добавить строку в CSV файл");

        return true;
    }

    /**
     * Читает строку с указанным номером
     * @param int $rowNumber - номер строки
     * @return array|false Массив или false если произошла ошибка
     * @version 0.2
     */
    public function getRow(int $rowNumber = 0): array|false
    {
        $file = new \SplFileObject($this->filePath);

        if ($rowNumber > 0) {
            $file->seek($rowNumber);
        }
        $dataRow = $file->fgetcsv($this->delimiter);
        if ($dataRow === false){
            echo("Ошибка при чтении файла" . PHP_EOL);
            return false;
        }

        $file = null;

        // Убрать BOM символы
        if ($rowNumber == 0 && count($dataRow) > 0) {
            $firstVal = $dataRow[0];
            $bom = pack('CCC', 0xEF, 0xBB, 0xBF);

            if (substr($firstVal, 0, 3) === $bom){
                $firstVal = substr($firstVal, 3);
                $dataRow[0] = $firstVal;
            }
        }

        if (!empty($this->encodingRes)) {
            array_walk_recursive($dataRow, function (&$value, $key) {
                if ($value !== '') {
                    $value = iconv($this->encodingSource, $this->encodingRes, $value);
                }
            });
        }

        return $dataRow;
    }

    /**
     * Записывает заголовок в файл CSV
     * @param array $arrHeader - данные заголовка
     * @return bool Успешно? Да/Нет
     * @version 0.2
     */
    public function createHeader(array $arrHeader): bool
    {
        if (!$this->isHeaderSet) {
            $file = new \SplFileObject($this->filePath, 'w');

            if (!empty($this->encodingRes)) {
                array_walk_recursive($arrHeader, function (&$value, $key) {
                    if ($value !== '') {
                        $value = iconv($this->encodingSource, $this->encodingRes, $value);
                    }
                });
            }
            $saveResult = false;
            for ($k = 1; $k <= $this->maxTryCount; $k++) {
                $saveResult = $file->fputcsv($arrHeader, $this->delimiter, '"', eol: $this->eol);

                if ($saveResult !== false) {
                    break;
                }

                TOOLS::$log->warn("Попытка #$k/{$this->maxTryCount}. Не удалось записать заголовок в файл CSV " . $this->filePath . ". Пауза между попытками $this->pause сек", __METHOD__);

                sleep($this->pause);
            }

            $file = null;

            if (!$saveResult)
                throw new Exception("CsvHelper createHeader. Не удалось записать заголовок в файл CSV");

            $this->isHeaderSet = true;
        }

        return true;
    }

    /**
     * Читает значение из указанной ячейки
     * @param int $rowNumber - номер строки
     * @param int $cellNumber - номер столбца
     * @return string
     * @version 0.1
     */
    public function getCell(int $rowNumber, int $cellNumber): string
    {
        $file = new \SplFileObject($this->filePath);

        if ($rowNumber > 0) {
            $file->seek($rowNumber);
        }

        $dataRow = $file->fgetcsv($this->delimiter, '"');
        if ($dataRow === false){
            return '';
        }

        $file = null;

        if (!empty($this->encodingRes)) {
            array_walk_recursive($dataRow, function (&$value, $key) {
                if ($value !== '') {
                    $value = iconv($this->encodingSource, $this->encodingRes, $value);
                }
            });
        }

        if (isset($dataRow[$cellNumber]))
            return $dataRow[$cellNumber];
        return '';
    }

    /**
     * Запись значения в указанную ячейку
     * @param int $rowNumber - номер строки
     * @param int $cellNumber - номер столбца
     * @param string $text - новое значение ячейки
     * @return bool Успешно? Да/Нет
     * @version 0.2
     */
    public function setCell(int $rowNumber, int $cellNumber, string $text): bool
    {
        $file = new \SplFileObject($this->filePath, 'r');
        $nameFileTmp = pathinfo($this->filePath, PATHINFO_FILENAME) . 'Tmp.csv';
        $pathFileTmp = dirname($this->filePath) . '/' . $nameFileTmp;
        $fileTmp = new \SplFileObject($pathFileTmp, 'w');

        $row = 0;
        while (!$file->eof()) {
            $dataRow = $file->fgetcsv($this->delimiter, '"');

            if ($row == $rowNumber) {
                $rowLength = count($dataRow);
                $rowLastCellNumber = $rowLength - 1;

                if ($cellNumber > ($rowLastCellNumber)) {
                    for ($i = $rowLength; $i < $cellNumber; $i++) {
                        $dataRow[$i] = '';
                    }
                }

                if (!empty($this->encodingRes)) {
                    $text = iconv($this->encodingSource, $this->encodingRes, $text);
                }

                $dataRow[$cellNumber] = $text;
            }

            $fileTmp->fputcsv($dataRow, $this->delimiter, '"', eol: $this->eol);
            $row++;
        }

        $file = null;
        $fileTmp = null;

        unlink($this->filePath);
        rename($pathFileTmp, $this->filePath);
        return true;
    }

    /**
     * Заменяет строку в CSV файле (Для добавления строки использовать addRow)
     * @param int $rowNumber - номер строки
     * @param array $dataNewRow - данные записываемой строки
     * @return bool Успешно? Да/Нет
     * @version 0.2
     */
    public function setRow(int $rowNumber, array $dataNewRow): bool
    {
        $file = new \SplFileObject($this->filePath, 'r');
        $nameFileTmp = pathinfo($this->filePath, PATHINFO_FILENAME) . 'Tmp.csv';
        $pathFileTmp = dirname($this->filePath) . '/' . $nameFileTmp;
        $fileTmp = new \SplFileObject($pathFileTmp, 'w');

        $row = 0;
        while (!$file->eof()) {
            $dataRow = $file->fgetcsv($this->delimiter, '"');

            if ($row == $rowNumber) {
                if (!empty($this->encodingRes)) {
                    array_walk_recursive($dataNewRow, function (&$value, $key) {
                        if ($value !== '') {
                            $value = iconv($this->encodingSource, $this->encodingRes, $value);
                        }
                    });
                }

                $fileTmp->fputcsv($dataNewRow, $this->delimiter, '"', eol: $this->eol);
            } else {
                $fileTmp->fputcsv($dataRow, $this->delimiter, '"', eol: $this->eol);
            }

            $row++;
        }

        $file = null;
        $fileTmp = null;

        unlink($this->filePath);
        rename($pathFileTmp, $this->filePath);
        return true;
    }

    /**
     * Получает количество строк в файле
     * @return int
     * @version 0.2
     */
    public function getRowsCount(): int
    {
        $file = new \SplFileObject($this->filePath, 'r');
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }
}
