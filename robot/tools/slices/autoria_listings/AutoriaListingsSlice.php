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
 *  3. по каждому новому открывается страница объявления - там телефон и дата;
 *  4. дата сравнивается с датой создания задачи (настройка onlyNew);
 *  5. принятые объявления пишутся в файл и отмечаются как отправленные;
 *  6. состояние сохраняется - атомарно, в самом конце.
 */
class AutoriaListingsSlice
{
    /** Имя файла состояния рядом с данными */
    private const STATE_FILE = 'autoria_state.json';

    /**
     * @param string $listUrl Адрес выдачи с применёнными фильтрами
     * @param string $filePath Куда положить результат (.csv или .xlsx)
     * @param string $boardName Имя задачи, по нему ведётся дата создания
     * @param bool $onlyNew Только новее даты создания задачи
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
            "Слайс autonomia_listings: $listUrl -> $filePath (только новые: "
            . ($onlyNew ? 'да' : 'нет') . ')',
            __METHOD__
        );

        $state = new AutoriaListingsState($stateDir . '/' . self::STATE_FILE);
        $state->prune();

        $scraper = new AutoriaListingsScraper();
        $candidates = $scraper->harvestList($listUrl, $limit);

        if ($candidates === []) {
            TOOLS::$log->warn(
                'Новых данных нет: выдача пуста или локатор устарел. '
                . 'Проверьте адрес и разметку доски',
                __METHOD__
            );
            return 0;
        }

        $createdAt = $state->getTaskCreatedAt($boardName);

        $accepted = [];
        $skippedOld = 0;
        $skippedNoDate = 0;
        $alreadySeen = 0;

        foreach ($candidates as $item) {
            if ($state->isSeen($item->key())) {
                $alreadySeen++;
                continue;
            }

            // телефон и дата живут на странице объявления
            $scraper->fillDetails($item);

            if (!BoardRules::shouldAccept($item->postedDate, $createdAt, $onlyNew)) {
                $skippedOld++;
                // отмечаем: объявление старое, повторно открывать незачем
                $state->markSeen($item);
                continue;
            }

            if ($onlyNew && $item->postedDate === '') {
                $skippedNoDate++;
            }

            if ($item->normedPhone === '') {
                TOOLS::$log->warn(
                    'Телефон не распознан или звонить по нему нельзя: ' . $item->url,
                    __METHOD__
                );
            }

            $accepted[] = $item;
            $state->markSeen($item);
        }

        // файл пишется только когда есть что писать: пустой результат
        // затирал бы предыдущий файл и выглядел бы как потеря данных
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
            'Принято %d, уже приходивших %d, старых по дате %d, без даты %d. Файл: %s',
            count($accepted),
            $alreadySeen,
            $skippedOld,
            $skippedNoDate,
            $saved
        ), __METHOD__, true);

        return count($accepted);
    }
}