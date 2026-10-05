<?php

/**
 * Класс для работы с ФО excelfile
 */
class ExcelHelper
{
    /**
     * @var string Путь к Excel файлу
     */
    private string $filePath;

    /**
     * @var int Число попыток изменить файл. Если другой процесс занимает файл (например антивирус)
     */
    private int $maxTryCount;


    /**
     * @var int Пауза между попытками, сек., изменить файл
     */
    private int $pause;

    /**
     * Конструктор
     * @param string $filePath путь к файлу Excel
     * @param int $maxTryCount Число попыток изменить файл. Если другой процесс занимает файл (например антивирус).
     * @param int $pause Пауза между попытками, сек.
     * @throws Exception
     */
    function __construct(string $filePath, int $maxTryCount = 5, int $pause = 15)
    {
        if (empty($filePath))
            throw new Exception("ExcelHelper constructor. Не указан путь к excel файлу");

        $this->filePath = $filePath;

        if (!$this->fileIsExists())
            throw new Exception("ExcelHelper constructor. Файл не найден '$filePath'");

        // закрыть поток, который работает с файлом.
        if (SYSTEM::$excelfile->is_opened($this->filePath))
            SYSTEM::$excelfile->close($this->filePath);

        $this->maxTryCount = $maxTryCount;
        $this->pause = $pause;
    }

    /**
     * Создать файл Excel и объект ExcelHelper
     * @param string $filePath путь к файлу Excel
     * @param string $firstSheetTitle Заголовок первого Листа
     * @param string[] $headers Заголовки для 1 строки
     * @return false|ExcelHelper Объект класса ExcelHelper или false в случае ошибки
     * @throws Exception если параметры ошибочные
     */
    public static function create(string $filePath, string $firstSheetTitle, array $headers): false|ExcelHelper
    {
        if (empty($filePath))
            throw new Exception("Не указан путь к excel файлу");

        if (empty($firstSheetTitle))
            throw new Exception("Не указан заголовок первого Листа");

        if (!SYSTEM::$excelfile->create($filePath, $firstSheetTitle, $headers)) {
            TOOLS::$log->error("Не удалось создать файл Excels по пути: $filePath", __METHOD__);
            return false;
        }
        return new ExcelHelper($filePath);
    }

    /**
     * @return string путь к Excel файлу
     */
    public function getFilePath() : string
    {
        return $this->filePath;
    }

    /**
     * @return bool Проверить существование файла Excel
     */
    public function fileIsExists(): bool
    {
        global $file_os;

        if (empty($this->filePath))
        {
            TOOLS::$log->error("Не указан путь к excel файлу", __METHOD__);
            return false;
        }

        if (!$file_os->is_exist($this->filePath))
        {
            TOOLS::$log->error("Excel файлу не существует '$this->filePath'", __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Поучить значение ячейки
     * @param int $sheetNumber Номер Листа таблицы Excel файла (счет с 0-ой)
     * @param int $rowIndex Номер строки (счет с 1-цы и 1 это заголовок как правило)
     * @param int $colIndex Номер колонки (счет с 1-цы)
     * @return string значение ячейки
     * @version 0.2
     */
    public function getCell(int $sheetNumber, int $rowIndex, int $colIndex) : string
    {
        return SYSTEM::$excelfile->get_cell($this->filePath, $sheetNumber, $rowIndex, $colIndex);
    }

    /**
     * Поучить значение строки
     * @param int $sheetNumber Номер Листа таблицы Excel файла (счет с 0-ой)
     * @param int $rowIndex Номер строки (счет с 1-цы и 1 это заголовок как правило)
     * @return array массив значений строки
     */
    public function getRow(int $sheetNumber, int $rowIndex) : array
    {
        return SYSTEM::$excelfile->get_row($this->filePath, $sheetNumber, $rowIndex);
    }

    /**
     * Получить Лист. Все строчки в виде массива массивов + Строка-заглушка с индексом 0
     * @param int $sheetIdx номер страницы
     * @param bool $needInsertZeroRow вставить в начало строку заглушку
     * @return array|false
     * @version 0.2
     */
    public function getSheet(int $sheetIdx, bool $needInsertZeroRow = false): array|bool
    {
        $rows = SYSTEM::$excelfile->get_sheet($this->filePath, $sheetIdx);

        // Строка-заглушка (Пустая) чтобы учесть отсчет для Excel от 1 (единицы)
        if ($needInsertZeroRow)
            array_unshift($rows, array());
        return $rows;
    }

    /**
     * Получить все строчки в виде массива массивов + Строка-заглушка с индексом 0
     * Строка заглушка для учета того, что отсчет строк в Excel идет с 1
     * Строки с заголовками будут включены в результат
     *
     * @param int $sheetNumber Номер Листа в Excel файле
     * @param bool $needInsertZeroRow вставить в начало строку заглушку
     * @return array
     * @throws Exception
     * @version 0.2
     */
    public function readAllRows(int $sheetNumber, bool $needInsertZeroRow = false): array
    {
        // результат
        $rows = array();

        // Строка-заглушка (Пустая) чтобы учесть отсчет для Excel от 1 (единицы)
        if ($needInsertZeroRow)
            $rows[] = array();

        // Счет строк начинается с единицы (+1)
        $rowsCount = SYSTEM::$excelfile->get_rows_count($this->filePath, $sheetNumber) + 1;

        // Отсчет строк в Excel идет с 1
        for($k = 1; $k < $rowsCount; $k++) {
            $rows[] = SYSTEM::$excelfile->get_row($this->filePath, $sheetNumber, $k);
        }
        return $rows;
    }

    /**
     * Создать новый Лист Excel и добавить после последнего Листа
     * @param string $sheetTitle Заголовок Листа
     * @param string[] $headers Заголовки для 1 строки нового Листа
     * @param bool $isNeedOpen
     * @return bool Успешно Да/Нет
     * @throws Exception если параметры ошибочные
     */
    public function addSheet(string $sheetTitle,
                             array $headers = array(),
                             bool $isNeedOpen = true): bool
    {

        if (empty($sheetTitle))
            throw new Exception("Не указан заголовок первого Листа");

        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        if (!SYSTEM::$excelfile->add_sheet($this->filePath, $sheetTitle, $headers)) {
            TOOLS::$log->error("Не удалось создать Лист", __METHOD__);
            return false;
        }
        return true;
    }


    /**
     * Установить значения строки в таблице Excel файла
     *
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param int $rowNumber Номер строки Листа таблицы Excel файла
     * @param array $row Массив значений
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @return bool Успешно Да/Нет
     * @version 0.6
     */
    public function setRow(int $sheetNumber,
                           int $rowNumber,
                           array $row,
                           bool $isNeedOpen = true): bool
    {
        $setResult = false;
        // Несколько попыток записи. Ошибка конкуренции при работе антивируса
        for ($k = 1; $k <= $this->maxTryCount; $k++) {

            if ($isNeedOpen)
                if (!SYSTEM::$excelfile->is_opened($this->filePath))
                    SYSTEM::$excelfile->open($this->filePath);

            // применить преобразование
            foreach ($row as $cellKey => $cellValue) {
                $row[$cellKey] = $this->fixCell($cellValue);
            }

            $setResult = SYSTEM::$excelfile->set_row($this->filePath, $sheetNumber, $rowNumber, $row);
            if ($setResult)
                break;

            TOOLS::$log->warn("Попытка #$k/{$this->maxTryCount}. Не удалось сохранить данные в файл " . $this->filePath . ". Пауза между попытками $this->pause сек", __METHOD__);
            // Пауза между попытками
            sleep($this->pause);
        }
        if (!$setResult){
            TOOLS::$log->warn("Не удалось записать данные в Excel файл: $this->filePath", __METHOD__);
            return false;
        }
        return true;
    }

    /**
     * Добавить страницу
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param array $rows Массив значений
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @param int $timeout Время ожидания операции, сек
     * @return bool Успешно Да/Нет
     * @version 0.1
     */
    public function setSheet(int $sheetNumber, array $rows, bool $isNeedOpen = true, int $timeout = 3000): bool
    {
        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        // применить преобразование
        foreach ($rows as $row) {
            foreach ($row as $cellKey => $cellValue)
                $row[$cellKey] = $this->fixCell($cellValue);
        }

        $result = \SYSTEM::$excelfile->set_sheet($this->filePath, 0, $rows, $timeout);

        if (!$result){
            TOOLS::$log->warn("Не удалось записать данные в Excel файл: $this->filePath", __METHOD__);
            return false;
        }
        return true;
    }

    /**
     * Добавить несколько строк
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param array $rows Массив значений
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @param int $timeout Время ожидания операции, сек
     * @return bool Успешно Да/Нет
     * @version 0.2
     */
    public function addRows(int $sheetNumber, array $rows, bool $isNeedOpen = true, int $timeout = 3000): bool
    {
        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        // применить преобразование
        foreach ($rows as $row) {
            foreach ($row as $cellKey => $cellValue)
                $row[$cellKey] = $this->fixCell($cellValue);
        }

        $addRowsResult = \SYSTEM::$excelfile->add_rows($this->filePath, 0, $rows, $timeout);

        if (!$addRowsResult){
            TOOLS::$log->warn("Не удалось записать данные в Excel файл: $this->filePath", __METHOD__);
            return false;
        }
        return true;
    }

    /**
     * Добавить несколько строк постранично (для крупных массивов)
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param array $rows Массив значений
     * @param int $limit Лимит записей для одной итерации
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @param int $timeout Время ожидания операции, сек
     * @return bool Успешно Да/Нет
     * @version 0.2
     */
    public function addRowsByPages(int $sheetNumber, array $rows, int $limit = 1000, bool $isNeedOpen = true, int $timeout = 3000): bool
    {
        // Разбить массив всех строк на группы массивов
        $matrix = array_chunk($rows, $limit);

        // Сколько страниц (групп массивов)
        $matrixCount = count($matrix);

        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        for ($m = 0; $m < $matrixCount;$m++){
            TOOLS::$log->debug("Группа строк #" . ($m + 1) . ' количество записей = ' . count($matrix[$m]), __METHOD__);
            echo 'Start indx #' . ($m * $limit) . PHP_EOL;

            // применить преобразование
            $rows = array();
            foreach ($matrix[$m] as $row) {
                foreach ($row as $cellKey => $cellValue)
                    $row[$cellKey] = $this->fixCell($cellValue);
                $rows[] = $row;
            }

            $addRowsResult = \SYSTEM::$excelfile->add_rows($this->filePath, 0, $rows, $timeout);

            if (!$addRowsResult){
                TOOLS::$log->warn("Не удалось записать данные в Excel файл: $this->filePath", __METHOD__);
                return false;
            }
        }
        return true;
    }

    /**
     * Установить значение ячейки в таблице Excel файла
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param int $rowNumber Номер строки Листа таблицы Excel файла
     * @param int $columnNumber Номер колонки Листа таблицы Excel файла
     * @param string $text Текстовое значение для ячейки
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @version 0.5
     */
    public function setCell(int $sheetNumber,
                            int $rowNumber,
                            int $columnNumber,
                            string $text,
                            bool $isNeedOpen = true) : bool
    {
        $setResult = false;
        // Несколько попыток записи. Ошибка конкуренции при работе антивируса
        for ($k = 1; $k <= $this->maxTryCount; $k++) {

            if ($isNeedOpen)
                if (!SYSTEM::$excelfile->is_opened($this->filePath))
                    SYSTEM::$excelfile->open($this->filePath);

            // применить преобразование
            $text = $this->fixCell($text);

            $setResult = SYSTEM::$excelfile->set_cell($this->filePath, $sheetNumber, $rowNumber, $columnNumber, $text);
            if ($setResult)
                break;

            TOOLS::$log->warn("Попытка #$k/{$this->maxTryCount}. Не удалось сохранить данные в файл " . $this->filePath . ". Пауза между попытками $this->pause сек", __METHOD__);
            // Пауза между попытками
            sleep($this->pause);
        }
        if (!$setResult){
            TOOLS::$log->warn("Не удалось записать значение в ячейку Excel файл: $this->filePath", __METHOD__);
            return false;
        }
        return true;
    }

    /**
     * Задать высоту и ширину строк с учетом содержимого
     * @param int $sheetNumber Номер листа
     * @param int $row номер строки
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @return bool Успешно Да/нет
     * @version 0.3
     */
    public function autosizeRow(int $sheetNumber,
                                int $row = -1,
                                bool $isNeedOpen = true) : bool
    {
        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        $setResult = SYSTEM::$excelfile->autosize_row($this->filePath, $sheetNumber, $row);
        if (!$setResult)
            TOOLS::$log->warn("Для файла Excel не удалось установить размеры строк", __METHOD__);

        return true;
    }

    /**
     * Задать высоту и ширину колонок с учетом содержимого
     * @param int $sheetNumber
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @return bool Успешно Да/нет
     * @version 0.3
     */
    public function autosizeColumns(int $sheetNumber,
                                    bool $isNeedOpen = true) : bool
    {
        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        $setResult = SYSTEM::$excelfile->autosize_col($this->filePath, $sheetNumber, '');
        if (!$setResult)
            TOOLS::$log->warn("Для файла Excel не удалось установить автоматически размеры", __METHOD__);
        return true;
    }

    /**
     * Количество листов
     * @return int|false
     * @version 0.2
     */
    public function getSheetsCount(): int|bool
    {
        return SYSTEM::$excelfile->get_sheets_count($this->filePath);
    }

    /**
     * Название листа по индексу
     * @param int $sheetIdx
     * @return string
     * @version 0.2
     */
    public function getSheetName(int $sheetIdx) : string
    {
        return SYSTEM::$excelfile->get_sheet_name($this->filePath, $sheetIdx);
    }

    /**
     * Индекс листа по названию
     * @param string $sheetName
     * @return int
     */
    public function getSheetIdx(string $sheetName): int
    {
        return SYSTEM::$excelfile->get_sheet_number_by_name($this->filePath, $sheetName);
    }

    /**
     * Количество занятых колонок в Листе таблицы Excel файла
     *
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param int $row_number (optional) Номер строки Листа таблицы Excel файла
     * @return int количество колонок
     * @version 0.2
     */
    public function getColumnsCount(int $sheetNumber, int $row_number = -1): int
    {
        return SYSTEM::$excelfile->get_cols_count($this->filePath, $sheetNumber, $row_number);
    }

    /**
     * Количество занятых строк в Листе таблицы Excel файла
     *
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @return int количество строк
     * @version 0.2
     */
    public function getRowsCount(int $sheetNumber): int
    {
        // Счет строк начинается с единицы (+1)
        return SYSTEM::$excelfile->get_rows_count($this->filePath, $sheetNumber);
    }

    /**
     * Добавить столбец
     * @param int $sheetNumber Номер Листа таблицы Excel файла
     * @param string $columnTitle Заголовок колонки
     * @param bool $isNeedOpen выполнить да/нет отдельно команду Open (требуется для очень больших файлов)
     * @return int|false Номер столбца или false если произошла ошибка
     * @version 0.3
     */
    public function addColumn(int $sheetNumber,
                              string $columnTitle,
                              bool $isNeedOpen = true) : int|bool
    {

        if ($isNeedOpen)
            if (!SYSTEM::$excelfile->is_opened($this->filePath))
                SYSTEM::$excelfile->open($this->filePath);

        $columnsCount = $this->getColumnsCount($sheetNumber);
        $botOperationResultColumnNum = $columnsCount + 1;
        if (!$this->setCell($sheetNumber, 1, $botOperationResultColumnNum, $columnTitle))
            return false;

        return $botOperationResultColumnNum;
    }

    /**
     * Получить номер колонки по имени
     * @param int $sheetNumber номер Листа
     * @param int $rowNumber Номер строки с заголовками
     * @param string $columnName Наименование колонки
     * @return int|null номер колонки или null если такой колонки нет
     * @version 0.1
     */
    public function getColumnIndexByName(int $sheetNumber, int $rowNumber, string $columnName): ?int
    {
        $rowHeaders = $this->getRow($sheetNumber, $rowNumber);

        foreach ($rowHeaders as $arrColIndex => $colHeader) {
            if ($colHeader === $columnName) {
                // счет номеров колонок начинается с 1
                $orderNumberColIndex = $arrColIndex + 1;
                break;
            }
        }

        return $orderNumberColIndex ?? null;
    }

    /**
     * Сохранить файл
     * @throws Exception Если не удалось записать данные в файл
     * @version 0.3
     */
    public function save() : void
    {
        $setResult = false;

        if (!SYSTEM::$excelfile->is_opened($this->filePath)) {
            //TOOLS::$log->warn("Файл закрыт: " . $this->filePath, __METHOD__);
            return;
        }

        // Несколько попыток записи. Ошибка конкуренции при работе антивируса
        for ($k = 1; $k <= $this->maxTryCount; $k++) {
            $setResult = SYSTEM::$excelfile->save($this->filePath);
            if ($setResult)
                break;

            TOOLS::$log->warn("Попытка #$k/{$this->maxTryCount}. Не удалось сохранить данные в файл " . $this->filePath . ". Пауза между попытками $this->pause сек", __METHOD__);
            // Пауза между попытками
            sleep($this->pause);
        }

        SYSTEM::$excelfile->close($this->filePath);

        if (!$setResult)
            throw new Exception("Не удалось записать данные в Excel файла: $this->filePath");
    }

    /**
     * Применить преобразование текста ячейки для корректной работы.
     * @param string|null $cellValue значение текста ячейки
     * @return string|null Значение ячейки после преобразования
     * @version 0.1
     */
    protected function fixCell(?string $cellValue): ?string
    {
        if (empty($cellValue)) {
            return $cellValue;
        }

        // Требуется дополнительно экранировать в значении ячейки символ '\' (косая черта).
        // Требуется добавить дополнительные символы косая черта (\) чтобы экранировать.
        // Значение ячейки будет восстановлено в ядре
        if (str_contains($cellValue, '\\')){
            $newCellValue = str_replace('\\', '\\\\', $cellValue);
            echo 'Подмена: "' . $cellValue . '" на "' . $newCellValue . '"' . PHP_EOL;
            return $newCellValue;
        }
        return $cellValue;
    }
}