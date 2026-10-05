<?php

/**
 * Выкладка результата: знает только про порт SpreadsheetWriter,
 * про браузер - ничего.
 */
class AutoriaListingsSink
{
    /**
     * Колонки результата.
     * Один источник истины и для фабрики писателя, и для записи.
     *
     * @var string[]
     */
    private const HEADERS = [
        'url',
        'title',
        'price',
        'city',
        'phone',
        'posted_date',
    ];

    private SpreadsheetWriter $writer;

    public function __construct(SpreadsheetWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * @return string[]
     */
    public static function headers(): array
    {
        return self::HEADERS;
    }

    /**
     * Записать объявления и вернуть путь к файлу результата.
     *
     * @param AutoriaListingItem[] $items
     * @return string
     */
    public function write(array $items): string
    {
        $this->writer->writeHeader(self::HEADERS);

        foreach ($items as $item) {
            $this->writer->writeRow($item->toRow());
        }

        return $this->writer->save();
    }
}