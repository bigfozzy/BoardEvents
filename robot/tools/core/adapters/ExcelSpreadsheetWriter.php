<?php

/**
 * Адаптер порта SpreadsheetWriter поверх ExcelHelper (SYSTEM::$excelfile, нативный xlsx — Excel не нужен).
 *
 * Строки буферизуются и уходят в файл пачками: каждый вызов add_rows открывает xlsx на стороне
 * сервера, поэтому писать по одной строке — это десятки лишних операций.
 */
class ExcelSpreadsheetWriter implements SpreadsheetWriter
{
    /** @var int Сколько строк копить перед записью пачкой */
    private const FLUSH_CHUNK = 500;

    private ExcelHelper $excel;

    /** @var array[] Строки, ещё не записанные в файл */
    private array $buffer = [];

    /**
     * @param string $filePath Полный путь к .xlsx файлу
     * @param string $sheetTitle Название первого листа
     * @param string[] $headers Заголовок (пишется сразу)
     * @throws Exception Если файл не создался
     */
    public function __construct(string $filePath, string $sheetTitle, array $headers)
    {
        $excel = ExcelHelper::create($filePath, $sheetTitle, $headers);
        if ($excel === false)
            throw new Exception("ExcelSpreadsheetWriter: не удалось создать файл '$filePath'");
        $this->excel = $excel;
    }

    public function writeHeader(array $headers): void
    {
        // Заголовок уже записан конструктором; отдельный вызов не требуется.
    }

    public function writeRow(array $row): void
    {
        $this->buffer[] = $row;
        if (count($this->buffer) >= self::FLUSH_CHUNK)
            $this->flush();
    }

    public function save(): string
    {
        $this->flush();
        $this->excel->save();
        return $this->excel->getFilePath();
    }

    /**
     * Сбросить буфер в файл.
     * @return void
     */
    private function flush(): void
    {
        if (empty($this->buffer))
            return;
        $rows = $this->buffer;
        $this->buffer = [];
        if (!$this->excel->addRows(0, $rows))
            throw new Exception("ExcelSpreadsheetWriter: не удалось записать строки в '" . $this->excel->getFilePath() . "'");
    }
}
