<?php

/**
 * Данные одного объявления: только поля, которые попадают в результат.
 *
 * Набор полей взят не из вёрстки, а из schema.org/Vehicle плюс то,
 * что лежит рядом в payload продавца. Причина в README робота и в
 * комментарии AutoriaListingsVehicleData: доска переписывает классы,
 * а объявленный JSON-LD остаётся.
 */
class AutoriaListingItem
{
    /** Адрес объявления */
    public string $url;

    /** Заголовок доски */
    public string $title;

    /** Цена числом */
    public ?int $price;

    /** Валюта цены, например USD */
    public string $currency;

    /** Пробег в километрах */
    public ?int $mileage;

    /** Город, слагом доски: kiev, kyiv, odessa */
    public string $city;

    /** Марка */
    public string $brand;

    /** Модель */
    public string $model;

    /** Год выпуска */
    public ?int $year;

    /** VIN */
    public string $vin;

    /** Кузов */
    public string $bodyType;

    /** Цвет */
    public string $color;

    /** Топливо */
    public string $fuelType;

    /** Коробка */
    public string $transmission;

    /**
     * Телефон, полученный раскрытием: нормализованный, по нему можно
     * позвонить. Пусто, если раскрытие не сработало.
     */
    public string $phone;

    /**
     * Маска, которую видно до клика: (068) XXX XX XX.
     *
     * Нужна как признак, что номер на доске ЕСТЬ и его не показали,
     * а не как замена телефона. В колонку phone она не попадает:
     * по маске позвонить нельзя.
     */
    public string $phoneMasked;

    /** Продавец: имя или название компании */
    public string $sellerName;

    /**
     * Дата публикации Y-m-d.
     *
     * На доске её нет - см. AutoriaListingsVehicleData. Поле оставлено
     * потому, что полноценные объявления и зеркала ведут себя по-разному,
     * и для будущих досок знание о дате может появиться.
     */
    public string $postedDate;

    public function __construct(
        string $url,
        string $title = '',
        ?int $price = null,
        string $currency = '',
        ?int $mileage = null,
        string $city = '',
        string $brand = '',
        string $model = '',
        ?int $year = null,
        string $vin = '',
        string $bodyType = '',
        string $color = '',
        string $fuelType = '',
        string $transmission = '',
        string $phoneMasked = '',
        string $phone = '',
        string $sellerName = '',
        string $postedDate = ''
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->price = $price;
        $this->currency = $currency;
        $this->mileage = $mileage;
        $this->city = $city;
        $this->brand = $brand;
        $this->model = $model;
        $this->year = $year;
        $this->vin = $vin;
        $this->bodyType = $bodyType;
        $this->color = $color;
        $this->fuelType = $fuelType;
        $this->transmission = $transmission;
        $this->phoneMasked = $phoneMasked;
        $this->phone = $phone;
        $this->sellerName = $sellerName;
        $this->postedDate = $postedDate;
    }

    /**
     * Создать объявление из разобранной страницы.
     *
     * @param array $data Результат AutoriaListingsVehicleData::fromPageSource
     */
    public static function fromVehicleData(array $data): self
    {
        $item = new self(
            (string)($data['url'] ?? ''),
            (string)($data['title'] ?? '')
        );

        $item->price = $data['price'] ?? null;
        $item->currency = (string)($data['currency'] ?? '');
        $item->mileage = $data['mileage'] ?? null;
        $item->city = (string)($data['city'] ?? '');
        $item->brand = (string)($data['brand'] ?? '');
        $item->model = (string)($data['model'] ?? '');
        $item->year = $data['year'] ?? null;
        $item->vin = (string)($data['vin'] ?? '');
        $item->bodyType = (string)($data['bodyType'] ?? '');
        $item->color = (string)($data['color'] ?? '');
        $item->fuelType = (string)($data['fuelType'] ?? '');
        $item->transmission = (string)($data['transmission'] ?? '');
        $item->phoneMasked = (string)($data['phoneMasked'] ?? '');
        $item->sellerName = (string)($data['sellerName'] ?? '');

        return $item;
    }

    /**
     * Поля объявления по именам колонок.
     *
     * Именованный массив, а не список: раньше строка собиралась
     * позициями, и порядок полей обязан был совпадать с порядком
     * заголовков в AutoriaListingsSink. Стоило одному переставить поле -
     * и значения молча уезжали в соседние колонки, а тесты ловили
     * это только если их кто-то допишет. Теперь писатель сам выбирает
     * нужные колонки по именам.
     *
     * @return array<string, string>
     */
    public function toRecord(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'price' => $this->price === null ? '' : (string)$this->price,
            'currency' => $this->currency,
            'mileage_km' => $this->mileage === null ? '' : (string)$this->mileage,
            'city' => $this->city,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year === null ? '' : (string)$this->year,
            'vin' => $this->vin,
            'body_type' => $this->bodyType,
            'color' => $this->color,
            'fuel' => $this->fuelType,
            'transmission' => $this->transmission,
            // нормализованный телефон, а не маска: по маске позвонить нельзя
            'phone' => $this->getCallablePhone(),
            'phone_masked' => $this->phoneMasked,
            'seller' => $this->sellerName,
        ];
    }

    /**
     * Телефон, по которому реально можно позвонить.
     */
    public function getCallablePhone(): string
    {
        return BoardRules::getNormedPhone($this->phone);
    }

    /**
     * Есть ли чем позвонить.
     */
    public function hasCallablePhone(): bool
    {
        return $this->getCallablePhone() !== '';
    }

    /**
     * Показывает ли доска маску вместо номера.
     *
     * Отдельный признак нужен в логе: маска - это не «телефона нет»,
     * а «номер отдаётся после клика».
     */
    public function hasPhoneMask(): bool
    {
        return $this->phoneMasked !== '';
    }

    /**
     * Ключ дедупликации: путь без схемы и меток.
     *
     * У одной машины адрес может отличаться параметрами, поэтому
     * сравниваем по пути.
     */
    public function key(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        if ($path === null || $path === '') {
            return $this->url;
        }

        return rtrim($path, '/');
    }

    /**
     * Идентификатор объявления из адреса.
     *
     * Адрес имеет вид /auto_bmw_x5_40521845.html, значит id = 40521845.
     * Он же стоит в атрибуте data-car-id карточки.
     */
    public function carId(): string
    {
        $path = (string)parse_url($this->url, PHP_URL_PATH);

        if (preg_match('/_(\d+)\.html?$/', $path, $m)) {
            return $m[1];
        }

        return '';
    }
}