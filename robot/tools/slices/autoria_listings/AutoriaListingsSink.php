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

    /** Колонка продавца - всегда, имя продавца условиями не запрещено */
    private const SELLER_COLUMN = 'seller';

    /** Метка порядка байтов UTF-8, без неё Excel ломает кириллицу */
    private const UTF8_BOM = "\xEF\xBB\xBF";

    private SpreadsheetWriter $writer;

    private string $filePath;

    public function __construct(SpreadsheetWriter $writer, string $filePath)
    {
        $this->writer = $writer;
        $this->filePath = $filePath;
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
     * Дописать объявления в отчёт и вернуть путь к файлу.
     *
     * ОТЧЁТ НАКАПЛИВАЕТСЯ, а не переписывается. Раньше заголовок писался
     * при каждом запуске, а он открывает файл на запись с обрезкой, -
     * и второй прогон стирал всё, что нашли в первый. Для робота,
     * который следит за доской, это убивало сам смысл: проверки идут
     * каждые пять минут, и покупатель получал файл с объявлениями за
     * последние пять минут вместо списка за день.
     *
     * @param AutoriaListingItem[] $items
     * @param bool $withPhones Добавить телефонные колонки
     * @return string
     */
    public function write(array $items, bool $withPhones = false): string
    {
        $headers = self::headers($withPhones);
        $isNewFile = !$this->reportExists();

        // Заголовок пишем только при создании файла. При добавлении он
        // и обрезал бы файл, и повторился бы второй строкой.
        if ($isNewFile) {
            $this->writer->writeHeader($headers);
        }

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

        $saved = $this->writer->save();

        if ($isNewFile) {
            $this->ensureUtf8Bom();
        }

        return $saved;
    }

    /**
     * Есть ли уже отчёт.
     *
     * Файл считается отсутствующим и пустым файлом: после обрыва
     * записи мог остаться ноль байт, и дописывать в него строки без
     * заголовка бессмысленно.
     */
    private function reportExists(): bool
    {
        if (!file_exists($this->filePath)) {
            return false;
        }

        return filesize($this->filePath) > 0;
    }

    /**
     * Дописать метку порядка байтов, если её нет.
     *
     * CSV-писатель отдаёт UTF-8 без метки, а Excel такие файлы открывает
     * догадками и показывает вместо кириллицы кракозябры. Метку дописываем
     * сами - через порт это сделать нельзя, а файл к этому моменту уже
     * записан целиком.
     *
     * Только для CSV: в xlsx метка не нужна и только сломала бы файл.
     */
    private function ensureUtf8Bom(): void
    {
        if (!self::isCsv($this->filePath)) {
            return;
        }

        $content = file_get_contents($this->filePath);

        if ($content === false || $content === '' || str_starts_with($content, self::UTF8_BOM)) {
            return;
        }

        // файл целиком в память: отчёт маленький, а дописывание в начало
        // иначе требует переписать содержимое
        if (file_put_contents($this->filePath, self::UTF8_BOM . $content) === false) {
            TOOLS::$log->warn(
                'Не удалось дописать метку UTF-8 в ' . $this->filePath
                . ' - Excel может показать кириллицу неверно',
                __METHOD__
            );
        }
    }

    /**
     * Это CSV, а не xlsx.
     */
    private static function isCsv(string $path): bool
    {
        return !in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['xlsx', 'xls'],
            true
        );
    }
}