<?php

/**
 * Данные одного объявления: только поля, которые попадают в результат.
 */
class AutoriaListingItem
{
    /** Адрес объявления */
    public string $url;

    /** Заголовок */
    public string $title;

    /** Цена как на доске, строкой - так она и отображается покупателю */
    public string $price;

    /** Город */
    public string $city;

    /** Телефон как в разметке, ненормализованный */
    public string $phone;

    /** Телефон после нормализации, '' если звонить нельзя */
    public string $normedPhone;

    /**
     * Дата публикации Y-m-d.
     * '' если доска отдала неожиданный формат - см. BoardRules::shouldAccept
     */
    public string $postedDate;

    /** Сырая строка даты, для разбора в логе при сбое парсинга */
    public string $postedDateRaw;

    public function __construct(
        string $url,
        string $title = '',
        string $price = '',
        string $city = '',
        string $phone = '',
        string $postedDate = '',
        string $postedDateRaw = ''
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->price = $price;
        $this->city = $city;
        $this->phone = $phone;
        $this->normedPhone = BoardRules::getNormedPhone($phone);
        $this->postedDate = $postedDate;
        $this->postedDateRaw = $postedDateRaw;
    }

    /**
     * Строка для таблицы. Порядок = порядок заголовков в AutoriaListingsSink.
     *
     * @return array<int, string>
     */
    public function toRow(): array
    {
        return [
            $this->url,
            $this->title,
            $this->price,
            $this->city,
            $this->normedPhone,
            $this->postedDate,
        ];
    }

    /**
     * По этому адресу объявление уже приходило.
     *
     * Ключ - путь без схемы, хоста и меток: одна машина может лежать
     * по разным адресам, и без этого пришлось бы уведомлять повторно.
     */
    public function key(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        if ($path === null || $path === '') {
            return $this->url;
        }

        return rtrim($path, '/');
    }
}