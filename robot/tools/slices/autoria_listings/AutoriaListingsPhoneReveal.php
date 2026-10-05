<?php

/**
 * Раскрытие телефона на объявлении auto.ria.
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !  ПО УСЛОВИЯМ ДОСКИ ЭТО ЗАПРЕЩЕНО. КЛАСС ВЫКЛЮЧЕН ПО УМОЛЧАНИЮ.     !
 * !                                                                 !
 * !  Пункт 1.21 оферты RIA (https://www.ria.com/offert/auto/) прямо  !
 * !  запрещает автоматический сбор номеров телефонов:               !
 * !    1.21.1 - любо�� автоматизированное использование номеров;     !
 * !    1.21.2 - использование номеров без отдельного лицензионного   !
 * !              договора с компанией;                              !
 * !    1.21.3 - использование без предварительного письменного        !
 * !              согласия, включая сбор и накопление;                !
 * !    1.21.4 - использование номеров для коммерции или рассылок.     !
 * !                                                                 !
 * !  Автоматический сбор остальных данных объявлений этими пунктами  !
 * !  НЕ запрещён - цену, пробег, VIN, город можно собирать.        !
 * !                                                                 !
 * !  Включать ($collectPhones в run.php) можно только имея письменное  !
 * !  согласие RIA. Продавать продукт, который это нарушает, нельзя:  !
 * !  это тот же случай, что с запретом scraping у OLX.               !
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * КАК ЭТО РАБОТАЕТ (проверено на живой доске 10.2026, 8 объявлений).
 *
 * В исходном HTML номера нет - лежит маска в payload:
 *   "phone":{"content":"(068) XXX XX XX","blockId":"autoPhone"}
 * Но страница рендерится на клиенте, и в отрисованном DOM маска уже
 * лежит в кнопке:
 *
 *   <button class="size-large conversion"
 *           data-type="common-button"
 *           data-action="showBottomPopUp">
 *     <span class="common-text ws-pre-wrap action">(068) XXX XX XX</span>
 *   </button>
 *
 * Клик по кнопке отправляет POST
 *   /bff/final-page/public/auto/popUp/
 * с блоком autoPhone и показывает окно class="popup", где номер уже
 * настоящий: «(050) 689 98 28».
 *
 * ПОЧЕМУ ИЩЕМ КНОПКУ ПЕРЕБОРОМ. Одного класса мало: на странице две
 * кнопки size-large conversion - вторая это «Понимаю и разрешаю».
 * data-action="showBottomPopUp" тоже не годится: таких кнопок шесть.
 * А вот маска в тексте - признак однозначный.
 *
 * ПОЧЕМУ НУЖНЫ ПОВТОРЫ. У дилеров попап появляется медленнее: из шести
 * проверенных объявлений пять раскрылись сразу, дилерское пришлось
 * ждать дольше. Поэтому клик повторяется, а не делается один раз.
 *
 * ЧАСТНЫЕ ПРОДАВЦЫ РАБОТАЮТ. isLoginRequired у проверенных был false, и
 * вход не требовался ни дилеру, ни частнику.
 */
class AutoriaListingsPhoneReveal
{
    /** Класс кнопок-конвертаций, среди которых лежит кнопка телефона */
    private const BUTTON_CLASS = 'size-large conversion';

    /** Класс всплывающего окна с раскрытым телефоном */
    private const POPUP_CLASS = 'popup';

    /** Признак маски в тексте кнопки */
    private const MASK_MARK = 'XXX';

    /** Сколько раз пробуем открыть окно */
    private const MAX_ATTEMPTS = 3;

    /** Пауза между попытками, секунд */
    private const RETRY_PAUSE = 2;

    /**
     * Раскрыть телефон объявления.
     *
     * @return string нормализованный номер либо '' если не раскрылся
     */
    public function reveal(): string
    {
        if (!$this->clickMaskButton()) {
            TOOLS::$log->debug(
                'Кнопка с маской телефона не найдена - возможно, номер виден сразу',
                __METHOD__
            );

            return '';
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            // окно приезжает отдельным запросом, поэтому ждём его
            // появления, а не загрузки страницы
            WEB::$browser->wait_js(self::RETRY_PAUSE);

            $phone = $this->readPopupPhone();

            if ($phone !== '') {
                return $phone;
            }

            TOOLS::$log->debug(
                "Попытка $attempt: окно с номером не появилось, пробуем снова",
                __METHOD__
            );
        }

        return '';
    }

    /**
     * Нажать кнопку с маской телефона.
     *
     * @return bool false если кнопки нет
     */
    private function clickMaskButton(): bool
    {
        $buttons = DOM::$button->get_all_by_class(self::BUTTON_CLASS, false);
        $count = $buttons->count();

        for ($i = 0; $i < $count; $i++) {
            $button = $buttons->get($i);

            if (!$button->is_exist())
                continue;

            $text = trim((string)$button->get_inner_text());

            // маска - единственный признак именно кнопки телефона
            if (!str_contains($text, self::MASK_MARK))
                continue;

            TOOLS::$log->debug('Нажимаем кнопку телефона: ' . $text, __METHOD__);

            // wait_browser = false: это не переход, а запрос за номером,
            // ждать загрузки страницы тут нечего
            $button->click(false);

            return true;
        }

        return false;
    }

    /**
     * Прочитать номер из окна.
     */
    private function readPopupPhone(): string
    {
        // get_by_class внутри ждёт появления элемента сам
        $popup = DOM::$div->get_by_class(self::POPUP_CLASS, false);

        if (!$popup->is_exist()) {
            return '';
        }

        $text = (string)$popup->get_inner_text();

        return BoardRules::findPhoneInText($text);
    }
}