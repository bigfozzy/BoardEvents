<?php

/**
 * Адаптер порта SpreadsheetWriter поверх CsvHelper.
 */
class CsvSpreadsheetWriter implements SpreadsheetWriter
{
    private CsvHelper $csv;

    /**
     * @param string $filePath Полный путь к .csv файлу
     * @param string $delimiter Разделитель полей
     */
    public function __construct(string $filePath, string $delimiter = ';')
    {
        $this->csv = new CsvHelper($filePath);
        $this->csv->setDelimiter($delimiter);
    }

    public function writeHeader(array $headers): void
    {
        $this->csv->createHeader($headers);
    }

    public function writeRow(array $row): void
    {
        $this->csv->addRow($row);
    }

    public function save(): string
    {
        return $this->csv->getFilePath();
    }
}
