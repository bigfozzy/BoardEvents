<?php

/**
 * Регистрация робота в планировщике Studio (WINDOW\scheduler).
 *
 * ScheduleIntervals лежит рядом, в этой же папке, и подхватывается
 * автозагрузчиком tools/robotInit.php - свой require_onece здесь
 * запрещён правилами XHE (см. robot/AGENTS.md, Hard rules).
 *
 * Подписи методов сверены с Objects\Window\xhe_scheduler.php:
 *   add(path, type, date, time, count, active, comments)
 *   get(num_task, &$path, &$type, &$date, &$time, &$count, &$active, &$comments)
 *   get_count(), edit(...), delete(num), delete_all(), activate(num)
 *
 * Ключевое отличие от наивной реализации: add() не идемпотентен.
 * Если просто вызывать его на каждом запуске, задачи будут копиться -
 * через неделю их будет десятки, и робот станет проверять доску
 * в десять раз чаще, чем человек просил. Поэтому сначала ищем свою
 * задачу по пути и обновляем её, а добавляем, только если её нет.
 *
 * Сравнение путей регистронезависимое и по нормализованному пути:
 * Windows не различает C:\Robot и c:\robot, а планировщик отдаёт
 * путь так, как его отдал Studio.
 */
class RobotSchedule
{
    /** Подпись своей задачи в списке планировщика */
    private const COMMENT = 'BoardEvents robot';

    /**
     * Зарегистрировать или обновить задачу запуска.
     *
     * @param string $scriptPath Путь к run.php
     * @param string $interval Интервал из ScheduleIntervals::PRESETS
     * @param string $firstRunTime Время первого запуска HH:MM
     * @return string текст для лога: что сделано и что не получилось
     */
    public static function register(string $scriptPath, string $interval, string $firstRunTime): string
    {
        $type = ScheduleIntervals::toSchedulerType($interval);

        if ($type === 0) {
            // молча ставить ближайший интервал нельзя - человек
            // попросил одно, получит другое. Говорим и выходим.
            return ScheduleIntervals::describe($interval);
        }

        $normalised = self::normalisePath($scriptPath);
        $count = self::get_count();
        $found = self::findTask($normalised, $count);

        if ($found > 0) {
            $ok = WINDOW::$scheduler->edit(
                $found,
                $scriptPath,
                $type,
                '',
                self::validateTime($firstRunTime),
                -1,
                true,
                self::COMMENT
            );

            return $ok
                ? "Задача #$found в планировщике обновлена: $interval"
                : "Не удалось обновить задачу #$found в планировщике";
        }

        $ok = WINDOW::$scheduler->add(
            $scriptPath,
            $type,
            '',
            self::validateTime($firstRunTime),
            -1,
            true,
            self::COMMENT
        );

        return $ok
            ? "Задача добавлена в планировщик: $interval (время первого запуска $firstRunTime)"
            : 'Не удалось добавить задачу в планировщик';
    }

    /**
     * Найти номер своей задачи по пути.
     *
     * @return int номер задачи, либо -1 если её нет
     */
    private static function findTask(string $normalisedPath, int $count): int
    {
        for ($num = 1; $num <= $count; $num++) {
            $path = null;
            $type = null;
            $date = null;
            $time = null;
            $runs = null;
            $active = null;
            $comments = null;
            $addParams = null;

            if (!WINDOW::$scheduler->get(
                $num,
                $path,
                $type,
                $date,
                $time,
                $runs,
                $active,
                $comments,
                $addParams
            )) {
                continue;
            }

            if (self::normalisePath((string)$path) === $normalisedPath) {
                return $num;
            }
        }

        return -1;
    }

    /**
     * Сколько всего задач в планировщике.
     */
    private static function get_count(): int
    {
        $count = WINDOW::$scheduler->get_count();

        return $count > 0 ? $count : 0;
    }

    /**
     * Привести путь к сравнимому виду.
     */
    private static function normalisePath(string $path): string
    {
        return strtolower(str_replace('/', '\\', trim($path)));
    }

    /**
     * Проверить время вида HH:MM.
     *
     * add() внутри делает date_parse() от строки "дата время" и
     * проверяет только сам факт разбора, а не ошибки в нём: мусор
     * пройдёт молча и превратится в нули. Поэтому проверяем здесь.
     */
    public static function validateTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            return $time . ':00';
        }

        return '09:00:00';
    }
}