<?php

/**
 * Порт записи табличного результата.
 *
 * Слайс зависит только от этого интерфейса: откуда берётся формат (csv, xlsx) решает фабрика
 * SpreadsheetWriters::forFile(). Новый формат — новый адаптер в core/adapters, а не `if ($format…)`
 * внутри слайса.
 */
interface SpreadsheetWriter
{
    /**
     * Записать строку заголовка (перезаписывает файл).
     * @param string[] $headers Названия колонок в порядке вывода
     * @return void
     */
    public function writeHeader(array $headers): void;

    /**
     * Записать одну строку данных.
     * @param array $row Значения колонкам в том же порядке, что и заголовок
     * @return void
     */
    public function writeRow(array $row): void;

    /**
     * Зафиксировать результат на диске.
     * @return string Путь к записанному файлу
     */
    public function save(): string;
}
