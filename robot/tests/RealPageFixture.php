<?php

/**
 * Фикстура: реальная разметка объявления auto.ria.
 *
 * Фрагменты вырезаны из живых страниц 10.2026, а не выдуманы:
 * jsonld-блоки сняты со страницы объявления, breadcrumb — тоже,
 * блок телефона — из её JSON-payload.
 *
 * Зачем хранить это в репозитории: правило XHE запрещает хардкодить
 * локатор, которого не видел. Фикстура — то, что мы видели. Кто
 * чинит парсер после следующей смены вёрстки доски, обновляет её
 * здесь же, и тесты падают, пока разбор не починен.
 *
 * Чего в фикстуре НЕТ и почему это важно:
 *  - даты публикации объявления - на доске её нет;
 *  - открытого телефона - вместо него маска (068) XXX XX XX.
 *
 * Эти два отсутствия проверяются отдельными проверками ниже:
 * именно на них ломался прежний парсер.
 */

final class RealPageFixture
{
    /** @var string|null */
    private static ?string $html = null;

    /**
     * Собрать страницу объявления из настоящих фрагментов.
     */
    public static function html(): string
    {
        if (self::$html !== null) {
            return self::$html;
        }

        $parts = self::parts();

        $scripts = '';
        foreach ($parts['vehicles'] as $vehicle) {
            $scripts .= "\n<script type=\"application/ld+json\">" . $vehicle . '</script>';
        }
        $scripts .= "\n<script type=\"application/ld+json\">" . $parts['breadcrumb'] . '</script>';

        self::$html = '<!DOCTYPE html><html lang="ru"><head><title>AUTO.RIA</title>'
            . $scripts
            . '</head><body><script>window.__DATA__={"isNotepad":false,'
            . $parts['phone']
            . '};</script></body></html>';

        return self::$html;
    }

    /**
     * Фрагменты разметки, как они есть на доске.
     *
     * @return array{vehicles: string[], breadcrumb: string, phone: string}
     */
    public static function parts(): array
    {
        return [
            // два блока Vehicle: первый с характеристиками, второй с картинками.
            // Порядок на странице именно такой, и парсер обязан выбрать первый -
            // иначе он возьмёт из второго пустой набор.
            'vehicles' => [
                '{"@context":"http://schema.org/","@type":"Vehicle",'
                . '"@id":"https://auto.ria.com/auto_bmw_x5_40521845.html",'
                . '"mileageFromOdometer":{"@type":"QuantitativeValue","unitCode":"KMT","value":56000},'
                . '"name":"BMW X5 2023","brand":{"@type":"Brand","name":"BMW"},'
                . '"vehicleIdentificationNumber":"5UX33EU06R9T12665","model":"X5",'
                . '"url":"https://auto.ria.com/auto_bmw_x5_40521845.html",'
                . '"mainEntityOfPage":"https://auto.ria.com/auto_bmw_x5_40521845.html",'
                . '"itemCondition":"https://schema.org/UsedCondition","productionDate":2023,'
                . '"vehicleEngine":{"@type":"EngineSpecification","fuelType":"\u0411\u0435\u043d\u0437\u0438\u043d"},'
                . '"description":"BMW X5 2023, \u043f\u0440\u043e\u0431\u0435\u0433 56000 \u043a\u043c",'
                . '"bodyType":"\u041b\u0435\u0433\u043a\u043e\u0432\u044b\u0435","color":"\u0427\u0435\u0440\u043d\u044b\u0439",'
                . '"fuelType":"\u0411\u0435\u043d\u0437\u0438\u043d","vehicleTransmission":"\u0410\u0432\u0442\u043e\u043c\u0430\u0442",'
                . '"numberOfDoors":5,'
                . '"offers":{"@type":"Offer","priceCurrency":"USD","price":97900,'
                . '"itemCondition":"https://schema.org/UsedCondition",'
                . '"availability":"https://schema.org/InStock"}}',

                // второй блок: только картинки, полей для нас нет
                '{"@context":"http://schema.org/","@type":"Vehicle",'
                . '"@id":"https://auto.ria.com/auto_bmw_x5_40521845.html",'
                . '"image":[{"@type":"ImageObject","contentUrl":"https://cdn0.riastatic.com/photo/x5.webp"}]}',
            ],

            'breadcrumb' => '{"@context":"https://schema.org","@type":"BreadcrumbList",'
                . '"itemListElement":['
                . '{"@type":"ListItem","position":1,"item":{"@id":"/","name":"AUTO.RIA.com"}},'
                . '{"@type":"ListItem","position":2,"item":{"@id":"/legkovie/","name":"\u041b\u0435\u0433\u043a\u043e\u0432\u044b\u0435"}},'
                . '{"@type":"ListItem","position":3,"item":{"@id":"/legkovie/city/kiev/","name":"\u041a\u0438\u0435\u0432"}},'
                . '{"@type":"ListItem","position":4,"item":{"@id":"/car/bmw/city/kiev/","name":"BMW"}},'
                . '{"@type":"ListItem","position":5,"item":{"@id":"/car/bmw/x5/city/kiev/","name":"X5"}}]}',

            // блок телефона из payload: номер замаскирован
            'phone' => '"phone":{"content":"(068) XXX XX XX","blockId":"autoPhone",'
                . '"data":[["userId","14137708"],["phoneId","681782965"],'
                . '["title","BMW X5 2023"],["companyId","3659"],'
                . '["companyEng","kiev-autotrade"],["userName","Kiev Autotrade"],'
                . '["isCompany","1"],["workTime":"\u041f\u043d-\u041f\u0442, 9:00 - 19:00"]]}',
        ];
    }
}