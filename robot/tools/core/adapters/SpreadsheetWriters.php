<?php

/**
 * Фабрика адаптеров SpreadsheetWriter: единственное место, где формат выбирается по расширению.
 */
class SpreadsheetWriters
{
    /**
     * Создать писателя таблицы по расширению файла (xlsx — Excel, иначе CSV).
     * @param string $filePath Полный путь к файлу результата
     * @param string[] $headers Колонки; для xlsx пишутся сразу, для csv — при writeHeader()
     * @param string $sheetTitle Название первого листа xlsx
     * @return SpreadsheetWriter
     * @throws Exception Если не удалось создать папку результата
     */
    public static function forFile(string $filePath, array $headers = [], string $sheetTitle = 'Результат'): SpreadsheetWriter
    {
        $folder = dirname($filePath);
        if (!is_dir($folder) && !mkdir($folder, 0777, true) && !is_dir($folder))
            throw new Exception("Не удалось создать папку результата '$folder'");

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'xlsx' || $extension === 'xls')
            return new ExcelSpreadsheetWriter($filePath, $sheetTitle, $headers);

        return new CsvSpreadsheetWriter($filePath);
    }
}
