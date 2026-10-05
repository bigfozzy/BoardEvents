<?php

class AppHelper
{
    /**
     * Перезагрузить
     * @return void
     */
    public function restart(): void
    {
        global $scriptPath, $app;

        TOOLS::$log->info("Произвести рестарт Робота", __METHOD__, true);
        sleep(10);
        $app->restart($scriptPath, $app->get_port(), $app->get_port(), pause_before_start_s:10);
    }

    /**
     * Остановить и выйти из RPABot / Перезапустить / Остановить выполнение Робота:
     * в зависимости от значения $appQuitType: exitapp, restart_and_quit, quit
     */
    public function quit(): void
    {
        global $xhe_host, $appQuitType, $scriptPath, $app;

        TOOLS::$log->info("[{$xhe_host}] Робот закончил работу", __METHOD__, true);

        if ($appQuitType === 'exitapp')
        {
            TOOLS::$log->info('exitapp', __METHOD__);
            $app->exitapp();
        }
        elseif ($appQuitType === 'restart')
        {
            TOOLS::$log->info('RESTART', __METHOD__);
            $app->restart($scriptPath, '', $app->get_port(), pause_before_start_s:10);
        }
        elseif ($appQuitType === 'restart_and_quit')
        {
            TOOLS::$log->info('RESTART AND QUIT', __METHOD__);
            $app->restart();
        }
        TOOLS::$log->info('QUIT', __METHOD__);
        $app->quit();
    }

    /**
     * Проверить рестарт
     * @return bool
     * @throws Exception
     */
    function checkRbotRestart(): bool
    {
        $port = $this->extractCurrentRbotAppPort();

        if (!is_numeric($port)) {
            TOOLS::$log->error("checkRbotRestart: ошибка определения rbot app port", __METHOD__);
            return false;
        }
        if ($port <= 0) {
            TOOLS::$log->error("checkRbotRestart: ошибка определения rbot app port (2)", __METHOD__);
            return false;
        }

        $file = getcwd() . "/res/restart_{$port}.txt";

        if (file_exists($file)) {
            $tmp = "checkRbotRestart: рестарт робота";
            TOOLS::$log->info($tmp);
            @unlink($file);
            throw new Exception($tmp);
        }

        return true;
    }

    /**
     * Вычленить номер порта из адреса хоста
     * @return int
     */
    function extractCurrentRbotAppPort(): int
    {
        global $xhe_host;
        $tmp = explode(':', $xhe_host); // "127.0.0.1:7010" => ["127.0.0.1", "7010"]
        $tmp = $tmp[1]; // "7010"
        return intval($tmp);
    }

    /**
     * Проверить, находится ли Робот на паузе
     * @return bool
     */
    function checkPause(): bool
    {
        global $wt;

        $port = $this->extractCurrentRbotAppPort();

        if (!is_numeric($port)) {
            TOOLS::$log->error("checkPause: ошибка определения rbot app port", __METHOD__);
            return false;
        }
        if ($port <= 0) {
            TOOLS::$log->error("checkPause: ошибка определения rbot app port (2)", __METHOD__);
            return false;
        }

        $file = getcwd() . "/res/pause_{$port}.txt";

        while (file_exists($file)) {
            TOOLS::$log->debug("Пауза, ждём: {$file} ...");
            sleep($wt);
        }

        return true;
    }
}
