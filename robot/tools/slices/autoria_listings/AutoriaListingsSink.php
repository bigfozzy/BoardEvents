<?php

/**
 * Выкладка результата: знает только про порт SpreadsheetWriter,
 * про браузер - ничего.
 */
class AutoriaListingsSink
{
    /**
     * Колонки результата, кроме телефонных.
     *
     * Один источник истины для выгрузки и для заголовка файла.
     *
     * @var string[]
     */
    private const COLUMNS = [
        'url',
        'title',
        'price',
        'currency',
        'mileage_km',
        'city',
        'brand',
        'model',
        'year',
        'vin',
        'body_type',
        'color',
        'fuel',
        'transmission',
    ];

    /**
     * Телефонные колонки. Добавляются только при согласии RIA.
     *
     * По умолчанию их нет в файле: пока сбор номеров выключен, они
     * были бы пустыми, а пустая колонка «телефон» в готовом отчёте
     * вводит в заблуждение - выглядит, будто номера собирались и не
     * нашлись. Условия доски это и запрещают, см. README.
     *
     * @var string[]
     */
    private const PHONE_COLUMNS = [
        'phone',
        'phone_masked',
    ];

    /** Колонка продавца - всегда, имя продажца условиями не запрещено */
    private const SELLER_COLUMN = 'seller';

    private SpreadsheetWriter $writer;

    public function __construct(SpreadsheetWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Колонки результата.
     *
     * @param bool $withPhones Добавить телефонные колонки
     * @return string[]
     */
    public static function headers(bool $withPhones = false): array
    {
        $columns = self::COLUMNS;

        if ($withPhones) {
            $columns = array_merge($columns, self::PHONE_COLUMNS);
        }

        $columns[] = self::SELLER_COLUMN;

        return $columns;
    }

    /**
     * Записать объявления и вернуть путь к файлу результата.
     *
     * @param AutoriaListingItem[] $items
     * @param bool $withPhones Добавить телефонные колонки
     * @return string
     */
    public function write(array $items, bool $withPhones = false): string
    {
        $headers = self::headers($withPhones);

        $this->writer->writeHeader($headers);

        foreach ($items as $item) {
            $record = $item->toRecord();
            $row = [];

            // колонки выбираются по именам, поэтому порядок полей в
            // AutoriaListingItem больше не важен
            foreach ($headers as $header) {
                $row[] = (string)($record[$header] ?? '');
            }

            $this->writer->writeRow($row);
        }

        return $this->writer->save();
    }
}