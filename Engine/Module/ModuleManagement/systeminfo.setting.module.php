<?php

use PassionEngine\Engine\Core\CoreCache;

class Module_Management_Systeminfo extends ModuleModel
{

    public function setting()
    {
        $accountTabs = new LibTabs("systeminfotab");
        $accountTabs->addTab(SYSTEMINFO_SYSTEM_INFO_TAB,
                             $this->tabSystemInfo());
        $accountTabs->addTab(SYSTEMINFO_PHP_INFO_TAB,
                             $this->tabPhpInfo());

        return $accountTabs->render();
    }

    private function tabSystemInfo()
    {
        $firstLine = array(
            array(
                30,
                SYSTEMINFO_SYSTEM_INFO_SETTING),
            array(
                70,
                SYSTEMINFO_SYSTEM_INFO_VALUE)
        );
        $rack = new LibRack($firstLine);

        $modeActivedContent = "";
        $modeActived = CoreCache::getCacheList();
        $currentMode = CoreCache::getInstance()->getSelectedCache()->getTransactionType();
        foreach ($modeActived as $mode) {
            $actived = ($currentMode === $mode);
            $modeActivedContent .= " " . $mode . "="
                . (($actived) ? "yes" : "no");
        }

        $coreMain = CoreMain::getInstance();
        $infos = array(
            "PASSION ENGINE VERSION" => PASSION_ENGINE_VERSION,
            "PASSION ENGINE PHP VERSION" => PASSION_ENGINE_PHP_VERSION,
            "PASSION ENGINE PHP OS" => PASSION_ENGINE_PHP_OS,
            "PASSION ENGINE actived cache" => $modeActivedContent,
            "PASSION ENGINE DIR" => PASSION_ENGINE_ROOT_DIRECTORY,
            "PASSION ENGINE URL" => PASSION_ENGINE_URL,
            "PASSION ENGINE EMAIL" => PASSION_ENGINE_EMAIL,
            "PASSION ENGINE valid session cache time" => $coreMain->getConfigs()->getSessionTimeLimit() . " days",
            "PASSION ENGINE UrlRewriting" => ($coreMain->getConfigs()->doUrlRewriting() ? "on" : "off"),
            "PHP built on" => php_uname(),
            "PHP version" => phpversion(),
            "WebServer to PHP interface" => php_sapi_name(),
            "Database version" => CoreSql::getInstance()->getSelectedBase()->getVersion(),
            "Database collation" => CoreSql::getInstance()->getSelectedBase()->getCollation()
        );

        foreach ($infos as $key => $value) {
            $rack->addLine(array(
                $key,
                $value));
        }
        return $rack->render();
    }

    private function tabPhpInfo()
    {
        $output = "";

        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION);
        $phpinfo = ob_get_contents();
        ob_end_clean();

        preg_match_all('#<body[^>]*>(.*)</body>#siU',
                       $phpinfo,
                       $output);
        $output = preg_replace('#<table#',
                               '<table class="table"',
                               $output[1][0]);
        $output = preg_replace('#(\w),(\w)#',
                               '\1, \2',
                               $output);
        // Incompatible HTML5
//        $output = preg_replace('#border="0" cellpadding="3" width="600"#', 'border="0" cellspacing="1" cellpadding="4" width="95%"', $output);
        $output = preg_replace('#<hr />#',
                               '',
                               $output);
        $output = str_replace('<div class="center">',
                              '',
                              $output);
        $output = str_replace('</div>',
                              '',
                              $output);
        $output = str_replace('class="e"',
                              '',
                              $output);
        $output = str_replace('class="v"',
                              '',
                              $output);

        return $output;
    }
}
?>