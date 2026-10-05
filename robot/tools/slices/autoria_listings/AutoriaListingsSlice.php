<?php

/**
 * Сбор новых объявлений auto.ria.com с выгрузкой в файл.
 *
 * Одна ответственность: «что нового на доске». Про формат файла
 * этот класс знает ровно настолько, чтобы попросить фабрику писать.
 *
 * Порядок работы:
 *  1. страница выдачи -> адреса объявлений;
 *  2. по каждому новому открывается страница объявления - там JSON-LD;
 *  3. отбор идёт в AutoriaListingsSelection, без браузера;
 *  4. принятые объявления пишутся в файл;
 *  5. состояние сохраняется - атомарно, в самом конце.
 *
 * ПРО «ТОЛЬКО НОВЫЕ». Раньше новизна определялась датой объявления на
 * доске. Сейчас на auto.ria даты в разметке нет вообще, и новизна
 * определяется тем, что объявления ещё не было в файле состояния.
 * Это, кстати, точнее: дата на доске путалась и раньше - C#-версия
 * на этом молча теряла объявления. Фильтр по дате оставлен на случай,
 * если доска его вернёт.
 */
class AutoriaListingsSlice
{
    /** Имя файла состояния рядом с данными */
    private const STATE_FILE = 'autoria_state.json';

    /**
     * @param string $listUrl Адрес выдачи с применёнными фильтрами
     * @param string $filePath Куда положить результат (.csv или .xlsx)
     * @param string $boardName Имя задачи, по нему ведётся дата создания
     * @param bool $onlyNew Применять фильтр по дате, если доска её отдаёт
     * @param int $limit Сколько максимум объявлений взять
     * @param string $stateDir Папка для файла состояния
     * @param bool $collectPhones Раскрывать ли телефон. По умолчанию НЕТ -
     *   условия RIA запрещают автоматический сбор номеров (п. 1.21 оферты),
     *   см. AutoriaListingsPhoneReveal
     * @param int $pages Сколько страниц выдачи обойти
     * @return int сколько объявлений попало в файл
     */
    public static function run(
        string $listUrl,
        string $filePath,
        string $boardName,
        bool $onlyNew = true,
        int $limit = 100,
        string $stateDir = '',
        bool $collectPhones = false,
        int $pages = 1
    ): int {
        if ($stateDir === '') {
            $stateDir = dirname($filePath);
        }

        TOOLS::$log->info(
            "Слайс autoria_listings: $listUrl -> $filePath (фильтр по дате: "
            . ($onlyNew ? 'включён' : 'выключен') . ')',
            __METHOD__
        );

        $state = new AutoriaListingsState($stateDir . '/' . self::STATE_FILE);
        $state->prune();

        $scraper = new AutoriaListingsScraper();
        $candidates = $scraper->harvestList($listUrl, $limit, $pages);

        if ($candidates === []) {
            TOOLS::$log->warn(
                'Выдача пуста: либо доска не отдаёт объявления по этому адресу, '
                . 'либо локатор карточек устарел и надо его поправить. '
                . 'Откройте страницу руками и посмотрите, что в разметке',
                __METHOD__
            );
            return 0;
        }

        // детали читаются только для тех, кого ещё не показывали: зачем
        // открывать страницу объявления, если номер и так известен
        $fresh = [];
        $alreadySeen = 0;

        foreach ($candidates as $candidate) {
            if ($state->isSeen($candidate->key())) {
                $alreadySeen++;
                continue;
            }

            $fresh[] = $scraper->fillDetails($candidate, $collectPhones);
        }

        $result = AutoriaListingsSelection::pick(
            $fresh,
            $state,
            $state->getTaskCreatedAt($boardName),
            $onlyNew
        );

        // уже приходившие отсчитаны здесь, до отбора: в pick() их нет,
        // поэтому в сводку дописываем сами, иначе счётчик всегда ноль
        $result['skippedAlreadySeen'] += $alreadySeen;

        $accepted = $result['accepted'];

        if ($accepted === []) {
            $state->save();

            TOOLS::$log->info(AutoriaListingsSelection::summary($result), __METHOD__);

            return 0;
        }

        $writer = SpreadsheetWriters::forFile(
            $filePath,
            AutoriaListingsSink::headers($collectPhones)
        );
        $saved = (new AutoriaListingsSink($writer, $filePath))->write($accepted, $collectPhones);

        // состояние сохраняем только после успешной записи результата:
        // наоборот - потеряли бы и файл, и память о проверке
        $state->save();

        TOOLS::$log->info(
            AutoriaListingsSelection::summary($result) . '. Файл: ' . $saved,
            __METHOD__,
            true
        );

        if ($result['withoutCallablePhone'] > 0) {
            TOOLS::$log->info(
                "У {$result['withoutCallablePhone']} объявлений нет телефона - сбор "
                . 'номеров выключен настройкой $collectPhones (условия доски)',
                __METHOD__
            );
        }

        return count($accepted);
    }
}