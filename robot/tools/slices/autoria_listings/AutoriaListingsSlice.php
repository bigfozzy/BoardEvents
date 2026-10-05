<?php

/**
 * Сбор новых объявлений auto.ria.com с выгрузкой в файл.
 *
 * Одна ответственность: «что нового на доске». Про формат файла
 * этот класс знает ровно настолько, чтобы попросить фабрику писать.
 *
 * Порядок работы:
 *  1. страница выдачи -> адреса объявлений;
 *  2. уже приходившие отбрасываются по файлу состояния;
 *  3. по каждому новому открывается страница объявления - там JSON-LD;
 *  4. результат пишется в файл;
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
     * @return int сколько объявлений попало в файл
     */
    public static function run(
        string $listUrl,
        string $filePath,
        string $boardName,
        bool $onlyNew = true,
        int $limit = 100,
        string $stateDir = ''
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
        $candidates = $scraper->harvestList($listUrl, $limit);

        if ($candidates === []) {
            TOOLS::$log->warn(
                'Выдача пуста: либо локатор устарел, либо доска не отдаёт '
                . 'объявления по этому адресу. Проверьте адрес и разметку',
                __METHOD__
            );
            return 0;
        }

        $createdAt = $state->getTaskCreatedAt($boardName);

        $accepted = [];
        $alreadySeen = 0;
        $skippedOld = 0;
        $skippedNoDate = 0;
        $noCallablePhone = 0;

        foreach ($candidates as $candidate) {
            // новизна определяется состоянием, а не датой доски
            if ($state->isSeen($candidate->key())) {
                $alreadySeen++;
                continue;
            }

            $item = $scraper->fillDetails($candidate);

            // если доска всё-таки отдала дату - уважаем фильтр
            if ($item->postedDate !== ''
                && !BoardRules::shouldAccept($item->postedDate, $createdAt, $onlyNew)) {
                $skippedOld++;
                $state->markSeen($item);
                continue;
            }

            if ($onlyNew && $item->postedDate === '') {
                $skippedNoDate++;
            }

            if (!$item->hasCallablePhone()) {
                $noCallablePhone++;
            }

            $accepted[] = $item;
            $state->markSeen($item);
        }

        if ($accepted === []) {
            $state->save();

            TOOLS::$log->info(sprintf(
                'Принято 0. Уже приходивших: %d, старых по дате: %d, без даты: %d',
                $alreadySeen,
                $skippedOld,
                $skippedNoDate
            ), __METHOD__);

            return 0;
        }

        $writer = SpreadsheetWriters::forFile($filePath, AutoriaListingsSink::headers());
        $saved = (new AutoriaListingsSink($writer))->write($accepted);

        // состояние сохраняем только после успешной записи результата:
        // наоборот - потеряли бы и файл, и память о проверке
        $state->save();

        TOOLS::$log->info(sprintf(
            'Принято %d, уже приходивших %d, старых по дате %d, без даты %d, '
            . 'без доступного телефона %d. Файл: %s',
            count($accepted),
            $alreadySeen,
            $skippedOld,
            $skippedNoDate,
            $noCallablePhone,
            $saved
        ), __METHOD__, true);

        if ($noCallablePhone > 0) {
            TOOLS::$log->warn(
                "У $noCallablePhone объявлений нет телефона, по которому можно "
                . 'позвонить: auto.ria отдаёт номер после клика по кнопке. '
                . 'В колонке phone_masked лежит то, что видно до клика',
                __METHOD__,
                true
            );
        }

        return count($accepted);
    }
}