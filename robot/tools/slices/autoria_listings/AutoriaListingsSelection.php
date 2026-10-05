<?php

/**
 * Отбор объявлений к отчёту - решение «что попадёт в файл».
 *
 * Вынесено из цикла со сбором данных намеренно: цикл ходит в браузер,
 * а решение о том, показать объявление покупателю или нет, не зависит от
 * браузера вовсе. Пока логика жила вперемешку с навигацией, она не была
 * покрыта ни одной проверкой - а именно она определяет, за что платит
 * покупатель: лишнее в отчёте и потерянные объявления одинаково плохи.
 *
 * Браузера здесь нет, поэтому проверяется обычным PHP.
 *
 * ГЛАВНОЕ ПРАВИЛО. Новизна определяется файлом состояния, а не датой на
 * доске: даты публикации auto.ria в разметке нет вовсе. В C#-версии по
 * этой причине молча терялись объявления - пустая дата считалась старой.
 */
class AutoriaListingsSelection
{
    /**
     * Отобрать объявления к отчёту.
     *
     * @param AutoriaListingItem[] $candidates Что нашли на доске
     * @param AutoriaListingsState $state Что уже отдавали
     * @param string $createdAt Дата создания задачи Y-m-d
     * @param bool $onlyNew Применять фильтр по дате, если доска её отдаёт
     * @return array{
     *     accepted: AutoriaListingItem[],
     *     skippedAlreadySeen: int,
     *     skippedOld: int,
     *     skippedNoDate: int,
     *     withoutCallablePhone: int
     * }
     */
    public static function pick(
        array $candidates,
        AutoriaListingsState $state,
        string $createdAt,
        bool $onlyNew = true
    ): array {
        $accepted = [];
        $skippedAlreadySeen = 0;
        $skippedOld = 0;
        $skippedNoDate = 0;
        $withoutCallablePhone = 0;

        foreach ($candidates as $item) {
            // уже показывали покупателю - повторно не показываем
            if ($state->isSeen($item->key())) {
                $skippedAlreadySeen++;
                continue;
            }

            // если доска всё-таки отдала дату - уважаем настройку.
            // при отсутствии даты объявление проходит: незнание даты
            // не повод потерять объявление
            if ($item->postedDate !== ''
                && !BoardRules::shouldAccept($item->postedDate, $createdAt, $onlyNew)) {
                $skippedOld++;
                // отмечаем: объявление старое, повторно открывать незачем
                $state->markSeen($item);
                continue;
            }

            if ($onlyNew && $item->postedDate === '') {
                $skippedNoDate++;
            }

            if (!$item->hasCallablePhone()) {
                $withoutCallablePhone++;
            }

            $accepted[] = $item;
            $state->markSeen($item);
        }

        return [
            'accepted' => $accepted,
            'skippedAlreadySeen' => $skippedAlreadySeen,
            'skippedOld' => $skippedOld,
            'skippedNoDate' => $skippedNoDate,
            'withoutCallablePhone' => $withoutCallablePhone,
        ];
    }

    /**
     * Строка счётчиков для лога.
     *
     * @param array $result Результат pick()
     */
    public static function summary(array $result): string
    {
        return sprintf(
            'Принято %d, уже приходивших %d, старых по дате %d, без даты %d, '
            . 'без доступного телефона %d',
            count($result['accepted'] ?? []),
            $result['skippedAlreadySeen'] ?? 0,
            $result['skippedOld'] ?? 0,
            $result['skippedNoDate'] ?? 0,
            $result['withoutCallablePhone'] ?? 0
        );
    }
}