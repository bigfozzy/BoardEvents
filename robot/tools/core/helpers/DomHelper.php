<?php

/**
 * Class DomHelper Вспомогательные объекты для работы с DOM
 */
class DomHelper
{
    /**
     * Ожидать пока не исчезнет DOM элемент по тексту или видимости (анимация загрузки/подгрузки)
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM для элемента
     * @param $text string Внутренний текст элемента, строгое соответствие
     * @param $frme int Номер фрейма
     * @param $wait int Количество циклов ожидания
     * @param $pause int Пауза между циклами в секундах
     * @return bool True - элемент не существует или скрыт, False - элемент все еще существует и не скрыт
     */
    public function waitExistsElementByTextIsView(XHEBaseDOMVisual $tag, string $text, int $frme = -1, int $wait = 120, int $pause = 1): bool
    {
        $m = 0;
        do {
            $m++;
            sleep($pause);

           $targetElement = $tag->get_by_inner_text($text, true, $frme);

            if ($targetElement->inner_number == -1){
                TOOLS::$log->debug("Элемент с текстом не существует: '$text' ", __METHOD__);
                return true;
            }


            if (!$targetElement->is_view_now()){
                TOOLS::$log->debug("Элемент с текстом скрыт: '$text'", __METHOD__);
                return true;
            }


            if ($m > $wait) {
                TOOLS::$log->debug("Ожидание остановлено. Элемент с текстом всё ещё на странице: '$text'!. Ожидали " .  $m * $pause  . " сек", __METHOD__);
                return false;
            }
            echo '.';
        } while (true);
    }

    /**
     * Ожидать пока не исчезнет DOM элемент по тексту или видимости (анимация загрузки/подгрузки)
     * @param $parenTag XHEBaseDOMVisual Функциональный объект DOM для родительского элемента
     * @param string $parentAttributeName Имя атрибута родительского элемента
     * @param string $parentAttributeValue Значение атрибута родительского элемента
     * @param string $parentAttributeValueIsExactly Точное соответствие атрибута родительского элемента
     * @param string $childElementText Внутренний текст дочернего элемента
     * @param bool $childElementTextIsExactly Строгое соответствие внутреннего текста дочернего элемента? Да/Нет
     * @param int $parentFrame Номер фрейма родительского элемента
     * @param $wait int Количество циклов ожидания
     * @param $pause int Пауза в секундах между циклами
     * @return bool True - элемент не существует или скрыт, False - элемент все еще существует и не скрыт
     */
    public function waitExistsOrViewElementByParentAndText(XHEBaseDOMVisual $parenTag,
                                                            string $parentAttributeName,
                                                            string $parentAttributeValue,
                                                            string $parentAttributeValueIsExactly,
                                                            string $childElementText,
                                                            bool $childElementTextIsExactly,
                                                            int $parentFrame = -1,
                                                            int $wait = 120,
                                                            int $pause = 1): bool
    {
        // Получить владельца по атрибуту
        $rootDiv = $parenTag->get_by_attribute($parentAttributeName, $parentAttributeValue, $parentAttributeValueIsExactly, $parentFrame);

        $m = 0;
        do {
            $m++;
            sleep($pause);

            // Получить наследника по тексту
            $targetElement = $rootDiv->get_child_by_inner_text($childElementText, $childElementTextIsExactly, true);

            // Проверить существование
            if ($targetElement->inner_number == -1) {
                TOOLS::$log->debug("Элемент не существует. Элемент с текстом: $childElementText", __METHOD__);
                return true;
            }

            // Проверить элемент на видимость
            if (!$targetElement->is_view_now()) {
                TOOLS::$log->debug("Элемент скрыт. Элемент с текстом: $childElementText", __METHOD__);
                return true;
            }

            if ($m > $wait) {
                TOOLS::$log->debug("Элемент с текстом всё ещё видим: $childElementText!. Ожидали " .  $m * $pause  . " сек", __METHOD__);
                return false;
            }
            echo '.';

        } while (true);
    }

    /**
     * Ожидать пока не исчезнет DOM элемент по тексту и видимости ( например анимация загрузки/подгрузки
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM для элемента
     * @param $text string Внутренний текст элемента, строгое соответствие
     * @param bool $isExactly Точное соответствие текста? Да/Нет
     * @param $frme int Номер фрейма
     * @param $wait int Количество циклов ожидания
     * @param $pause int Пауза между циклами в секундах
     * @return bool Элемент DOM появился? Да/Нет
     */
    function waitExistsOrViewElementByText(XHEBaseDOMVisual $tag, string $text, bool $isExactly, int $frme = -1, int $wait = 120, int $pause = 1): bool
    {
        $m = 0;
        do {
            $m++;
            sleep($pause);

            $targetElement = $tag->get_by_inner_text($text, $isExactly, $frme);

            if ($targetElement->inner_number == -1){
                TOOLS::$log->debug("Элемент не существует. Элемент с текстом: $text", __METHOD__);
                return true;
            }


            if (!$targetElement->is_view_now()){
                TOOLS::$log->debug("Элемент скрыт. Элемент с текстом: $text", __METHOD__);
                return true;
            }


            if ($m > $wait) {
                TOOLS::$log->debug("Ожидание остановлено. Элемент с текстом всё ещё на странице: $text!. Ожидали " .  $m * $pause  . " сек", __METHOD__);
                return false;
            }
            echo '.';
        } while (true);
    }

    /**
     * Ожидать (подгрузку) DOM элементов по тексту и общему количеству
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM для элемента
     * @param $text string текст строгое соответствие
     * @param $cnt int общее количество элементов ожидается
     * @param $frme int Номер фрейма
     * @param $wait int сколько циклов ожидать
     * @param $pause int Пауза между циклами в секундах
     * @return bool Элементы DOM появились? Да/Нет
     * @version 0.2
     */
    function waitOnCountElementByText(XHEBaseDOMVisual $tag, string $text, int $cnt, int $frme = -1, int $wait = 10, int $pause = 1): bool
    {
        sleep(1);

        $m = 0;
        while (true)
        {
            $cnt1 = count($tag->get_all_by_inner_text($text, false, $frme)->elements);
            if ($cnt1 >= $cnt)
                break;
            if ($m > $wait) {
                TOOLS::$log->warn("Не дождались нужного количества элементов c текстом: '$text'!. Ожидали " .  $m * $pause  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $m++;
        }

        return true;
    }

    /**
     * Ожидать (подгрузку) DOM элементов по аттрибуту и общему количеству
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param string $attributeName Название аттрибута
     * @param string $attributeValue Значение атрибута
     * @param bool $exactly Точное совпадение значения
     * @param int $count Ожидаемой число (не менее)
     * @param int $frame Номер фрейма
     * @param $totalTry int Сколько циклов ожидать
     * @param $pause int пауза между циклами, сек
     * @return bool Элементы DOM появились? Да/Нет
     */
    function waitOnCountElementByAttribute(XHEBaseDOMVisual $tag, string $attributeName, string $attributeValue, bool $exactly, int $count, int $frame = -1, int $totalTry = 10, int $pause = 1): bool
    {
        sleep(1);

        $m = 0;
        while (true)
        {
            $tagCount = count($tag->get_all_by_attribute($attributeName, $attributeValue, $exactly, $frame)->elements);
            if ($tagCount >= $count)
                break;
            if ($m > $totalTry) {
                TOOLS::$log->warn("Не дождались нужного количества элементов '$count' c аттрибутом: '$attributeName' = '$attributeValue'!. Ожидали " .  $m * $pause  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $m++;
        }

        return true;
    }

    /**
     * Ожидать (подгрузку) DOM элемент по тексту
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param $text string текст строгое соответствие
     * @param $frme int номер фрейма
     * @param $wait int сколько циклов ожидать
     * @param $pause int пауза в секундах между циклами
     * @return bool DOM элемент появился на странице? Да/Нет
     * @version 0.1
     * @deprecated use waitOnElementByInnerText
     */
    function waitOnElementByText(XHEBaseDOMVisual $tag, string $text, int $frme = -1, int $wait = 10, int $pause = 1): bool
    {
        sleep(1);
        $a = 0;
        while (!$tag->is_exist_by_inner_text($text, false, $frme)) {
            if ($a > $wait) {
                TOOLS::$log->warn("Не дождались нужного элемента c текстом: '$text'!. Ожидали " .  ($a * $pause)  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $a++;
        }

        return true;
    }

    /**
     * Ожидать (подгрузку) DOM элемент по тексту
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM
     * @param $text string Текст строгое соответствие? Да/Нет
     * @param $frme int Номер фрейма
     * @param $wait int Сколько циклов ожидать
     * @param $pause int Пауза между циклами, сек
     * @return bool DOM элемент появился на странице? Да/Нет
     * @version 0.1
     */
    function waitOnElementByInnerText(XHEBaseDOMVisual $tag, string $text, bool $isExactly = false, int $frme = -1, int $wait = 10, int $pause = 1): bool
    {
        sleep(1);
        $m = 0;
        while (!$tag->is_exist_by_inner_text($text, $isExactly, $frme)) {
            if ($m > $wait) {
                TOOLS::$log->warn("Не дождались элемента c текстом: '$text'!. Ожидали " .  ($m * $pause)  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $m++;
        }

        return true;
    }

    /**
     * Ожидать появление (подгрузку) DOM элемента на странице по атрибуту
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM
     * @param $att_name string Название атрибута
     * @param $att_text string Значение атрибута нестрогое соответствие
     * @param $frme int Номер фрейма
     * @param $wait int Сколько циклов ожидать
     * @param $pause int Пауза между циклами в секундах
     * @return bool Элемент появился на странице? Да/Нет
     * @deprecated use waitOnElementByAttribute
     */
    function waitOnElementByAtt(XHEBaseDOMVisual $tag, string $att_name, string $att_text, int $frme = -1, int $wait = 10, int $pause = 1): bool
    {
        return $this->waitOnCountElementByAttribute($tag, $att_name, $att_text, false, $frme, $wait, $pause);
    }

    /**
     * Ожидать появления (подгрузку) DOM элемента на странице по атрибуту
     * @param $tag XHEBaseDOMVisual Функциональный объект DOM
     * @param $attributeName string Название атрибута
     * @param $attributeValue string Значение атрибута
     * @param bool $isExactly Точное соответствие значения атрибута? Да/Нет
     * @param $frme int Номер фрейма
     * @param $wait int Сколько циклов ожидать
     * @param $pause int Пауза между циклами в секундах
     * @return bool Элемент появился на странице? Да/Нет
     */
    function waitOnElementByAttribute(XHEBaseDOMVisual $tag, string $attributeName, string $attributeValue, bool $isExactly, int $frme = -1, int $wait = 30, int $pause = 1): bool
    {
        $a = 0;
        sleep(1);
        while (!$tag->is_exist_by_attribute($attributeName, $attributeValue, $isExactly, $frme))
        {
            if ($a > $wait) {
                TOOLS::$log->warn("Не дождались нужного элемента c атрибутом '$attributeName' = '$attributeValue'. Ожидали " .  $a * $pause  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $a++;
        }

        return true;
    }

    /**
     * Ожидать пока не исчезнет DOM элемент по тексту (например анимация загрузки/подгрузки)
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param $text string текст строгое соответствие
     * @param $frme int номер фрейма
     * @param $wait int сколько циклов ожидать
     * @param $pause int пауза в секундах между циклами
     * @return bool DOM элемент исчез? Да/Нет
     * @deprecated use waitExistsElementByInnerText
     */
    function waitExistsElementByText(XHEBaseDOMVisual $tag, string $text, int $frme = -1, int $wait = 30, int $pause = 1): bool
    {
        sleep(1);
        $a = 0;
        while ($tag->is_exist_by_inner_text($text, true, $frme)) {
            if ($a > $wait) {
                TOOLS::$log->warn("Элемент с текстом '$text' всё ещё на странице! Ожидали " .  $a * $pause  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $a++;
        }

        return true;
    }

    /**
     * Ожидать пока не исчезнет DOM элемент по тексту (например анимация загрузки/подгрузки)
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param $text string Текст
     * @param bool $isExactLy Текст строгое соответствие? Да/Нет
     * @param $frme int Номер фрейма
     * @param $wait int сколько циклов ожидать
     * @param $pause int пауза в секундах между циклами
     * @return bool DOM элемент исчез? Да/Нет
     */
    function waitExistsElementByInnerText(XHEBaseDOMVisual $tag, string $text, bool $isExactLy, int $frme = -1, int $wait = 30, int $pause = 1): bool
    {
        sleep(1);
        $a = 0;
        while ($tag->is_exist_by_inner_text($text, $isExactLy, $frme)) {
            if ($a > $wait) {
                TOOLS::$log->warn("Элемент с текстом '$text' всё ещё на странице! Ожидали " .  $a * $pause  . " секунд", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $a++;
        }

        return true;
    }

    /**
     * Ожидать пока не исчезнет DOM элемент по атрибуту (например анимация загрузки/подгрузки)
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param string $attributeName Название атрибута DOM элемента
     * @param string $attributeValue Значение атрибута DOM элемента
     * @param bool $isExactly Точное соответствие значения атрибута? Да/Нет
     * @param $frme int Номер фрейма
     * @param $wait int Сколько циклов ожидать
     * @param $pause int Пауза в секундах между циклами
     * @return bool
     */
    function waitExistsElementByAttribute(XHEBaseDOMVisual $tag, string $attributeName, string $attributeValue, bool $isExactly, int $frme = -1, int $wait = 30, int $pause = 1): bool
    {
        sleep(1);
        $a = 0;
        while ($tag->is_exist_by_attribute($attributeName, $attributeValue, $isExactly, $frme)) {
            if ($a > $wait) {
                TOOLS::$log->warn("Элемент всё ещё на странице с атрибутом '$attributeName' = '$attributeValue'! Ожидали " .  ($a * $pause)  . " сек.", __METHOD__);
                return false;
            }
            sleep($pause);
            echo '.';
            $a++;
        }

        return true;
    }

    /**
     * Получить номер фрейма по тексту в нём для первого найденного по тексту элемента
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param $text string Внутренний текст
     * @return int Номер фрейма
     */
    function getFrameNumberByText(XHEBaseDOMVisual $tag, string $text): int
    {
        for ($i = 0; $i < 15; $i++)
            if ($tag->is_exist_by_inner_text($text, false, $i))
                return $i;

        return -1;
    }

    /**
     * Получить первый видимый DOM элемент по атрибуту
     * @param $tag XHEBaseDOMVisual функциональный объект DOM
     * @param $att_name string название атрибута
     * @param $att_text string значение атрибута нестрогое соответствие
     * @param $frme int номер фрейма
     * @return false|mixed
     */
    function getIsViewNowByAtt(XHEBaseDOMVisual $tag, string $att_name, string $att_text, int $frme = -1): mixed
    {
        $items = $tag->get_all_by_attribute($att_name, $att_text, true, $frme);
        //print_r($items->get_inner_text());
        foreach ($items as $item) {
            if ($item->is_view_now())
                return $item;
        }

        return false;
    }

    /**
     * Выполнить JS двойной клик на этом элементе
     * @param XHEInterface $objInterface Интерфейс для функционального объекта DOM
     * @param bool $isBubbles Если true, тогда событие всплывает
     * @comment Альтернативный вариант, если send_mouse_double_click() не срабатывает
     * @return void
     */
    function sendMouseDoubleClickByJavaScript(XHEInterface $objInterface, bool $isBubbles = true): void
    {
       $scriptJS = "element.dispatchEvent(new MouseEvent('dblclick', {";
       if ($isBubbles)
          $scriptJS = $scriptJS . "bubbles: true,";
       else
          $scriptJS = $scriptJS . "bubbles: false,";

       $scriptJS = $scriptJS
            . "cancelable: true,"
            . "view: window,"
            . "}));";

       $objInterface->run_js($scriptJS);
    }

    /**
     * Выполнить JS клик на этом элементе НЕ РАБОТАЕТ
     * @param XHEInterface $objInterface Интерфейс для функционального объекта DOM
     * @param bool $isBubbles если true, тогда событие всплывает
     * @comment Альтернативный вариант, если send_mouse_double_click() не срабатывает
     * @return void
     */
    function sendMouseClickByJavaScript(XHEInterface $objInterface, bool $isBubbles = true): void
    {
        $scriptJS = "element.dispatchEvent(new MouseEvent('click', {";
        $scriptJS = $isBubbles ? $scriptJS . "bubbles: true," : $scriptJS . "bubbles: false,";

        $scriptJS = $scriptJS
            . "cancelable: true,"
            . "view: window,"
            . "clientX: 5,"
            . "clientY: 5,"
            //. "ctrlKey: false,"
            //. "altKey: false,"
            //. "shiftKey: false,"
            //. "button: 0, " //0 = left, 1 = middle, 2 = right
            //. "relatedTarget: null"
            . "}));";

        $objInterface->run_js($scriptJS);
    }

    /**
     * Выполнить JS клик на дочернем TD для table, где table сам является строкой таблицы. Для выделения строки.
     * @param XHEInterface $objInterface Интерфейс для функционального объекта DOM TABLE!
     * @comment Альтернативный вариант, если send_mouse_double_click() не срабатывает для таблиц сайт СУЭД
     * @return void
     */
    function sendMouseClickByRowAsTableJavaScript(XHEInterface $objInterface): void
    {
        // Найти у table дочерний элемент td
        $scriptJS = "const cell = element.querySelector('td');";
        $scriptJS .= "if (cell) ";
        $scriptJS .= "{ ";
        // Выполнить фокус на нем
        $scriptJS .= "  cell.focus();\n";

        // событие Клик начало
        $scriptJS .= "  cell.dispatchEvent(new MouseEvent('click', {";
        $scriptJS .= "bubbles: true,"
                   . "cancelable: true,"
                   . "view: window,"
                   . "clientX: 5,"
                   . "clientY: 5\n}));";
            //. "ctrlKey: false,"
            //. "altKey: false,"
            //. "shiftKey: false,"
            //. "button: 0, " //0 = left, 1 = middle, 2 = right
            //. "relatedTarget: null"
        // событие Клик конец

        $scriptJS .=  "}";

        $objInterface->run_js($scriptJS);
    }

    /**
     * Установить или отменить если уже был установлен класс для DOM элемента
     * @param XHEInterface $objInterface Интерфейс для функционального объекта DOM
     * @param string $cssClass название класса CSS используемого для данной страницы
     * @return void
     */
    function setUnsetCSSClass(XHEInterface $objInterface, string $cssClass): void
    {
        $scriptJS = "element.classList.contains('$cssClass')? element.classList.remove('$cssClass') :element.classList.add('$cssClass');";
        $objInterface->run_js($scriptJS);
    }

    /**
     * Установить цвет шрифта для DOM элемента element.style.color
     * @param XHEInterface $objInterface Интерфейс для функционального объекта DOM
     * @param string $cssColor Цвет HTML
     * @return void
     */
    function setCSSStyleColor(XHEInterface $objInterface, string $cssColor): void
    {
        $scriptJS = "element.style.color = '$cssColor';";
        $objInterface->run_js($scriptJS);
    }
}
