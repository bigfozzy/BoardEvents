<?php

class WebHelper
{
    /**
     * Перейти по ссылке
     * @param string $url ссылка на WEB сайт
     * @param bool $useCache Использовать ли при навигации Веб-кэш(WEB cache)? Да/Нет
     * @param bool $useWait Ожидать окончания навигации в соответствии с параметрами ожидания? Да/Нет
     */
    function navigate(string $url, bool $useCache = true, bool $useWait = true): void
    {
        global $browser;
        TOOLS::$log->info("Navigate $url", __METHOD__);
        $browser->navigate($url, $useCache, $useWait);
    }

    /**
     * чистим всю информацию по браузеру
     */
    function clearBrowserInfo()
    {
        global $browser;

        $browser->close_all_tabs();
        $browser->navigate("about:blank");
        $browser->clear_cookies("");
        $browser->clear_cache();
    }
}