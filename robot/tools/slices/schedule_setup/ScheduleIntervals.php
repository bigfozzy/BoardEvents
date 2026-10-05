<?php

/**
 * Соответствие «человеческий интервал» -> «тип задачи планировщика XHE».
 *
 * Отдельный класс без обращений к XHE, потому что именно здесь в
 * C#-версии был баг: строки списка в интерфейсе и условия в
 * StartScheduling разошлись ("раз 10 часов" против "раз в
 * 10 часов"), и интервал молча не работал. Раз соответствие
 * проверяется тестом, разойтись ему больше не с чем.
 *
 * Типы задач WINDOW\scheduler (подписи взяты из исходников
 * Objects\Window\xhe_scheduler.php, а не из памяти):
 *
 *   1 - один раз;            2 - раз в час;      3 - раз в день;
 *   4 - раз в неделю;        5 - раз в месяц;    6 - раз в год;
 *   8 - раз в минуту;        9 - раз в 5 минут; 10 - раз в 10 минут;
 *  11 - раз в полчаса;      13 - бесконечно.
 *
 * Произвольных интервалов у планировщика нет: тип 14 «раз в N секунд»
 * требует значения N, а параметра для него в add() не предусмотрено,
 * поэтому длинные интервалы (2 часа, 6 часов, сутки понемногу) в
 * Studio не выразить. Их нельзя молча подменять - см. describe().
 */
class ScheduleIntervals
{
    public const TYPE_ONCE = 1;
    public const TYPE_HOURLY = 2;
    public const TYPE_DAILY = 3;
    public const TYPE_WEEKLY = 4;
    public const TYPE_MONTHLY = 5;
    public const TYPE_EVERY_MINUTE = 8;
    public const TYPE_EVERY_5_MIN = 9;
    public const TYPE_EVERY_10_MIN = 10;
    public const TYPE_HALF_HOUR = 11;
    public const TYPE_FOREVER = 13;

    /**
     * Интервалы из интерфейса прежней версии на C#.
     *
     * Список оставлен тем же, каким его видел покупатель: робот,
     * который вдруг отказывается от понятных интервалов, ломается
     * у того, кто уже настроился на старый список.
     *
     * @var string[]
     */
    public const PRESETS = [
        'раз в минуту',
        'раз в 3 минуты',
        'раз в 5 минут',
        'раз в 10 минут',
        'раз в 15 минут',
        'раз в 20 минут',
        'раз в 30 минут',
        'раз в час',
        'раз в 2 часа',
        'раз в 3 часа',
        'раз в 4 часа',
        'раз в 5 часов',
        'раз в 10 часов',
        'раз в 12 часов',
        'раз в сутки',
        'раз в неделю',
    ];

    /**
     * Минуты в интервале.
     *
     * @return int -1 если строка не из списка
     */
    public static function toMinutes(?string $interval): int
    {
        switch ($interval) {
            case 'раз в минуту':   return 1;
            case 'раз в 3 минуты': return 3;
            case 'раз в 5 минут':  return 5;
            case 'раз в 10 минут': return 10;
            case 'раз в 15 минут': return 15;
            case 'раз в 20 минут': return 20;
            case 'раз в 30 минут': return 30;
            case 'раз в час':      return 60;
            case 'раз в 2 часа':   return 120;
            case 'раз в 3 часа':   return 180;
            case 'раз в 4 часа':   return 240;
            case 'раз в 5 часов':  return 300;
            case 'раз в 10 часов': return 600;
            case 'раз в 12 часов': return 720;
            case 'раз в сутки':    return 1440;
            case 'раз в неделю':   return 10080;
        }

        return -1;
    }

    /**
     * Тип задачи планировщика для интервала.
     *
     * @return int тип из исходников XHE, либо 0 если интервал не выражается
     */
    public static function toSchedulerType(?string $interval): int
    {
        $minutes = self::toMinutes($interval);

        if ($minutes < 0) {
            return 0;
        }

        // у планировщика есть ровно эти фиксированные интервалы
        switch ($minutes) {
            case 1:    return self::TYPE_EVERY_MINUTE;
            case 5:    return self::TYPE_EVERY_5_MIN;
            case 10:   return self::TYPE_EVERY_10_MIN;
            case 30:   return self::TYPE_HALF_HOUR;
            case 60:   return self::TYPE_HOURLY;
            case 1440: return self::TYPE_DAILY;
            case 10080: return self::TYPE_WEEKLY;
        }

        // остальные в планировщике не выражаются - см. describe()
        return 0;
    }

    /**
     * Чем заменить интервал, который планировщик не умеет.
     *
     * Подменять молча нельзя: человек выбрал «раз в 2 часа», а робот
     * будет проверять раз в час - это вдвое больше нагрузки на доску
     * и вдвое больше писем, чем он просил. Поэтому возвращается пустая
     * строка и вызывающий обязан сказать об этом вслух.
     *
     * @return string '' если интервал выражается точно
     */
    public static function describe(?string $interval): string
    {
        $minutes = self::toMinutes($interval);

        if ($minutes < 0) {
            return "Интервал '$interval' не из списка.";
        }

        if (self::toSchedulerType($interval) !== 0) {
            return '';
        }

        $nearest = self::nearestSupported($minutes);

        return "Планировщик Studio не умеет '$interval' "
            . "(произвольные интервалы в нём не предусмотрены). "
            . "Ближайший поддерживаемый - $nearest.";
    }

    /**
     * Ближайший поддерживаемый интервал снизу.
     *
     * Берётся меньший, а не больший: лучше проверять до́реже, чем
     * навязывать лишние проверки доски.
     */
    private static function nearestSupported(int $minutes): string
    {
        $supported = [
            1 => 'раз в минуту',
            5 => 'раз в 5 минут',
            10 => 'раз в 10 минут',
            30 => 'раз в полчаса',
            60 => 'раз в час',
            1440 => 'раз в сутки',
            10080 => 'раз в неделю',
        ];

        $best = 0;
        foreach (array_keys($supported) as $candidate) {
            if ($candidate <= $minutes && $candidate > $best) {
                $best = $candidate;
            }
        }

        return $supported[$best] ?? 'раз в неделю';
    }

    /**
     * Ближайший поддерживаемый интервал снизу, строкой из списка.
     *
     * @return string '' если точный интервал поддерживается
     */
    public static function fallback(?string $interval): string
    {
        if ($interval === null || $interval === '') {
            return '';
        }

        if (self::toSchedulerType($interval) !== 0) {
            return '';
        }

        return self::describe($interval) === '' ? '' : self::nearestSupported(self::toMinutes($interval));
    }
}