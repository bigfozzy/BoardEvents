<?php

/**
 * Class TOOLS Единый доступ к статическим экземплярам объектов модулей-хелперов.
 * Модули-хелперы это классы с наборами готовых решений специфических задач
 * Модули-хелперы подключаются отдельно; их список и установка — в официальной документации продукта
 * @example TOOLS::$log->info("Кукареку!", "main");
 *
 * Здесь объявлены только те свойства, которые реально инициализируются в __constructStatic():
 * типизированное static-свойство без значения доступно только после присваивания, иначе
 * обращение к нему — фатальная ошибка "must not be accessed before initialization".
 */
class TOOLS
{
    /**
     * @var DomHelper Функции для работы с DOM
     */
    public static DomHelper $dom;

    /**
     * @var Robot Функции ToDo: Надо сравнить с другими работами
     */
    public static Robot $robot;

    /**
     * @var LogHelper Функции для сообщений отладки
     */
    public static LogHelper $log;

    /**
     * @var Mailer Функции для отправки почты
     */
    public static Mailer $mailer;

    /**
     * @var MailerHub Функции для получения почты
     */
    public static MailerHub $mailerHub;

    /**
     * @var SystemHelper Функции для отчета о файлах в Excel
     */
    public static SystemHelper $system;

    /**
     * @var AppHelper Расширенная функциональность для $app с логом
     */
    public static AppHelper $app;

    /**
     * @var WebHelper Функции для браузера
     */
    public static WebHelper $web;

    /**
     * @var PassportHelper Функции для работы с паспортом Робота
     */
    public static PassportHelper $passport;

    /**
     * Инициация статических полей
     */
    public static function __constructStatic(): void
    {
        if (class_exists('DomHelper'))
            TOOLS::$dom = new DomHelper();
        if (class_exists('SystemHelper'))
            TOOLS::$system = new SystemHelper();
        if (class_exists('AppHelper'))
            TOOLS::$app = new AppHelper();
        if (class_exists('WebHelper'))
            TOOLS::$web = new WebHelper();
        if (class_exists('Mailer'))
            TOOLS::$mailer = new Mailer();
        if (class_exists('MailerHub'))
            TOOLS::$mailerHub = new MailerHub();
        if (class_exists('Robot'))
            TOOLS::$robot = new Robot();
        if (class_exists('PassportHelper'))
            TOOLS::$passport = new PassportHelper();
        TOOLS::$log = new LogHelper();
    }

    /**
     * Текущая ОС это Linux?
     * @return bool
     */
    public static function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }
}
