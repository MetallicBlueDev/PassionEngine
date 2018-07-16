<?php

use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Exec\ExecEmail;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreRequestType;

class Module_Management_Setting extends ModuleModel
{

    public function setting()
    {
        $localView = CoreRequest::getWord("localView",
                                          "",
                                          CoreRequestType::POST);

        switch ($localView) {
            case "sendGeneral":
                $this->sendGeneral();
                break;
            case "sendSystem":
                $this->sendSystem();
                break;
            default:
                return $this->tabHome();
        }
        return "";
    }

    private function tabHome()
    {
        $accountTabs = new LibTabs("settingtab");
        $accountTabs->addTab(SETTING_GENERAL_TAB,
                             $this->tabGeneral());
        $accountTabs->addTab(SETTING_SYSTEM_TAB,
                             $this->tabSystem());
        return $accountTabs->render();
    }

    /**
     * Supprime le cache
     */
    private function deleteCache()
    {
        CoreCache::getInstance(CoreCacheSection::SECTION_TMP)->removeCache("configs.php");
    }

    /**
     * Mise à jour des valeurs de la table de configuration
     *
     * @param $values array
     * @param $where array
     */
    private function updateTable($key,
                                 $value)
    {
        CoreSql::getInstance()->update(
            CoreTable::CONFIG_TABLE,
            array(
                "value" => $value),
            array(
                "name = '" . $key . "'")
        );
    }

    private function tabGeneral()
    {
        $form = new LibForm("management-setting-general");
        $form->setTitle(SETTING_GENERAL_SITE_SETTING_TITLE);
        $form->setDescription(SETTING_GENERAL_SITE_SETTING_DESCRIPTION);
        $form->addSpace();

        $coreMain = CoreMain::getInstance();

        $online = $coreMain->getConfigs()->doOpening();
        $form->addHtmlInFieldset(SETTING_GENERAL_SITE_SETTING_SITE_STATUT);
        $form->addInputRadio("defaultSiteStatut1",
                             "defaultSiteStatut",
                             SETTING_GENERAL_SITE_SETTING_SITE_ON,
                             $online,
                             "open");
        $form->addInputRadio("defaultSiteStatut2",
                             "defaultSiteStatut",
                             SETTING_GENERAL_SITE_SETTING_SITE_OFF,
                             !$online,
                             "close");
        $form->addSpace();
        $form->addTextarea("defaultSiteCloseReason",
                           SETTING_GENERAL_SITE_SETTING_SITE_OFF_REASON,
                           $coreMain->getConfigs()->getDefaultSiteCloseReason(),
                           "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();

        $form->addInputText("defaultSiteName",
                            SETTING_GENERAL_DEFAULT_SITE_NAME,
                            $coreMain->getConfigs()->getDefaultSiteName());
        $form->addInputText("defaultSiteSlogan",
                            SETTING_GENERAL_DEFAULT_SITE_SLOGAN,
                            $coreMain->getConfigs()->getDefaultSiteSlogan());
        $form->addInputText("defaultAdministratorEmail",
                            SETTING_GENERAL_DEFAULT_ADMIN_EMAIL,
                            $coreMain->getConfigs()->getDefaultAdministratorEmail());
        $form->addSpace();

        $form->addSelectOpenTag("defaultLanguage",
                                SETTING_GENERAL_DEFAULT_LANGUAGE);
        $langues = CoreTranslate::getLangList();
        $currentLanguage = $coreMain->getConfigs()->getDefaultLanguage();
        $form->addSelectItemTag($currentLanguage,
                                "",
                                true);
        foreach ($langues as $langue) {
            if ($langue == $currentLanguage)
                continue;
            $form->addSelectItemTag($langue);
        }
        $form->addSelectCloseTag();

        $form->addSelectOpenTag("defaultTemplate",
                                SETTING_GENERAL_DEFAULT_TEMPLATE);
        $templates = LibMakeStyle::getTemplateList();
        $currentTemplate = $coreMain->getConfigs()->getDefaultTemplate();
        $form->addSelectItemTag($currentTemplate,
                                "",
                                true);
        foreach ($templates as $template) {
            if ($template == $currentTemplate)
                continue;
            $form->addSelectItemTag($template);
        }
        $form->addSelectCloseTag();

        $form->addSelectOpenTag("defaultModule",
                                SETTING_GENERAL_DEFAULT_MODULE);
        $modules = LibModule::getModuleList();
        $currentModule = $coreMain->getConfigs()->getDefaultModule();
        $currentModuleName = "";
        foreach ($modules as $module) {
            if ($module['value'] == $currentModule) {
                $currentModuleName = $module['name'];
                continue;
            }
            $form->addSelectItemTag($module['value'],
                                    $module['name']);
        }
        $form->addSelectItemTag($currentModule,
                                $currentModuleName,
                                true);
        $form->addSelectCloseTag();
        $form->addSpace();

        $form->addFieldset(SETTING_GENERAL_METADATA_TITLE,
                           SETTING_GENERAL_METADATA_DESCRIPTION);
        $form->addSpace();

        $form->addTextarea("defaultDescription",
                           SETTING_GENERAL_METADATA_DEFAULT_DESCRIPTION,
                           $coreMain->getConfigs()->getDefaultDescription(),
                           "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();
        $form->addTextarea("defaultKeywords",
                           SETTING_GENERAL_METADATA_DEFAULT_KEYWORDS,
                           $coreMain->getConfigs()->getDefaultKeywords(),
                           "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();

        $rewriting = $coreMain->getConfigs()->doUrlRewriting();
        $form->addHtmlInFieldset(SETTING_GENERAL_METADATA_URLREWRITING);
        $form->addInputRadio("urlRewriting1",
                             "urlRewriting",
                             SETTING_GENERAL_METADATA_URLREWRITING_ON,
                             $rewriting,
                             "1");
        $form->addInputRadio("urlRewriting2",
                             "urlRewriting",
                             SETTING_GENERAL_METADATA_URLREWRITING_OFF,
                             !$rewriting,
                             "0");
        $form->addSpace();

        $form->addInputHidden("module",
                              "management");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputHidden("manage",
                              "setting");
        $form->addInputHidden("localView",
                              "sendGeneral");
        $form->addInputSubmit("submit",
                              VALID);
        CoreHtml::getInstance()->addJavascript("validGeneralSetting('#form-management-setting-general', '#form-management-setting-general-defaultSiteName-input', '#form-management-setting-general-defaultAdministratorEmail-input');");
        return $form->render();
    }

    private function sendGeneral()
    {
        $deleteCache = false;
        $coreMain = CoreMain::getInstance();

        // état du site
        $defaultSiteStatut = CoreRequest::getWord("defaultSiteStatut",
                                                  "",
                                                  CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultSiteStatut() != $defaultSiteStatut) {
            $defaultSiteStatut = ($defaultSiteStatut == "close") ? "close" : "open";
            $this->updateTable("defaultSiteStatut",
                               $defaultSiteStatut);
            $deleteCache = true;
        }
        // Raison de femeture
        $defaultSiteCloseReason = CoreRequest::getString("defaultSiteCloseReason",
                                                         "",
                                                         CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultSiteCloseReason() != $defaultSiteCloseReason) {
            $this->updateTable("defaultSiteCloseReason",
                               $defaultSiteCloseReason);
            $deleteCache = true;
        }
        // Nom du site
        $defaultSiteName = CoreRequest::getString("defaultSiteName",
                                                  "",
                                                  CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultSiteName() != $defaultSiteName) {
            if (!empty($defaultSiteName)) {
                $this->updateTable("defaultSiteName",
                                   $defaultSiteName);
                $deleteCache = true;
            } else {
                CoreLogger::addErrorMessage(SETTING_GENERAL_DEFAULT_SITE_NAME_INVALID);
            }
        }
        // Slogan du site
        $defaultSiteSlogan = CoreRequest::getString("defaultSiteSlogan",
                                                    "",
                                                    CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultSiteSlogan() != $defaultSiteSlogan) {
            $this->updateTable("defaultSiteSlogan",
                               $defaultSiteSlogan);
            $deleteCache = true;
        }
        // Email du site
        $defaultAdministratorEmail = CoreRequest::getString("defaultAdministratorEmail",
                                                            "",
                                                            CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultAdministratorEmail() != $defaultAdministratorEmail) {
            if (!empty($defaultAdministratorEmail) && ExecEmail::isValidEmail($defaultAdministratorEmail)) {
                $this->updateTable("defaultAdministratorEmail",
                                   $defaultAdministratorEmail);
                $deleteCache = true;
            } else {
                CoreLogger::addErrorMessage(SETTING_GENERAL_DEFAULT_ADMIN_EMAIL_INVALID);
            }
        }
        // Langue par défaut
        $defaultLanguage = CoreRequest::getString("defaultLanguage",
                                                  "",
                                                  CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultLanguage() != $defaultLanguage) {
            $langues = CoreTranslate::getLangList();
            if (!empty($defaultLanguage) && ExecUtils::inArray($defaultLanguage,
                                                               $langues)) {
                $this->updateTable("defaultLanguage",
                                   $defaultLanguage);
                $deleteCache = true;
            } else {
                CoreLogger::addErrorMessage(SETTING_GENERAL_DEFAULT_LANGUAGE_INVALID);
            }
        }
        // Template par défaut
        $defaultTemplate = CoreRequest::getString("defaultTemplate",
                                                  "",
                                                  CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultTemplate() != $defaultTemplate) {
            $templates = LibMakeStylegetTemplateListes();
            if (!empty($defaultTemplate) && ExecUtils::inArray($defaultTemplate,
                                                               $templates)) {
                $this->updateTable("defaultTemplate",
                                   $defaultTemplate);
                $deleteCache = true;
            } else {
                CoreLogger::addErrorMessage(SETTING_GENERAL_DEFAULT_TEMPLATE_INVALID);
            }
        }
        // Module par défaut
        $defaultModule = CoreRequest::getString("defaultModule",
                                                "",
                                                CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultModule() != $defaultModule) {
            if (!empty($defaultModule)) {
                $this->updateTable("defaultModule",
                                   $defaultModule);
                $deleteCache = true;
            } else {
                CoreLogger::addErrorMessage(SETTING_GENERAL_DEFAULT_MODULE_INVALID);
            }
        }
        // Description du site
        $defaultDescription = CoreRequest::getString("defaultDescription",
                                                     "",
                                                     CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultDescription() != $defaultDescription) {
            $this->updateTable("defaultDescription",
                               $defaultDescription);
            $deleteCache = true;
        }
        // Mot clès du site
        $defaultKeywords = CoreRequest::getString("defaultKeywords",
                                                  "",
                                                  CoreRequestType::POST);
        if ($coreMain->getConfigs()->getDefaultKeywords() != $defaultKeywords) {
            $this->updateTable("defaultKeywords",
                               $defaultKeywords);
            $deleteCache = true;
        }
        // état de la réécriture des URLs
        $urlRewriting = CoreRequest::getBoolean("urlRewriting",
                                                "",
                                                CoreRequestType::POST);
        if ($coreMain->getConfigs()->doUrlRewriting() != $urlRewriting) {
            $urlRewriting = $urlRewriting ? 1 : 0;
            $this->updateTable("urlRewriting",
                               $urlRewriting);
            $deleteCache = true;
        }

        // Suppression du cache
        if ($deleteCache) {
            $this->deleteCache();
            CoreLogger::addInformationMessage(DATA_SAVED);
        }

        if ($coreMain->isDefaultLayout()) {
            CoreHtml::getInstance()->redirect("index.php?module=management&manage=setting&selectedTab=settingtabidTab0",
                                              1);
        }
    }

    private function tabSystem()
    {// TODO a finir de coder
        $coreMain = CoreMain::getInstance();

        $form = new LibForm("management-setting-system");
        $form->setTitle(SETTING_SYSTEM_CACHE_SETTING_TITLE);
        $form->setDescription(SETTING_SYSTEM_CACHE_SETTING_DESCRIPTION);
        $form->addInputText("sessionTimeLimit",
                            SETTING_SYSTEM_CACHE_SETTING_CACHE_LIMIT,
                            $coreMain->getConfigs()->getSessionTimeLimit());
        $form->addInputText("cryptKey",
                            SETTING_SYSTEM_CACHE_SETTING_CRYPT_KEY,
                            $coreMain->getConfigs()->getCryptKey());
        $form->addSpace();

        $form->addFieldset(SETTING_SYSTEM_SESSION_SETTING_TITLE,
                           SETTING_SYSTEM_SESSION_SETTING_DESCRIPTION);
        $form->addInputText("cookiePrefix",
                            SETTING_SYSTEM_SESSION_SETTING_COOKIE_PREFIX,
                            $coreMain->getConfigs()->getCookiePrefix());
        $form->addSpace();

        // Configuration FTP
        $ftp = $coreMain->getConfigs()->getConfigCache();
        $form->addFieldset(SETTING_SYSTEM_FTP_SETTING_TITLE,
                           SETTING_SYSTEM_FTP_SETTING_DESCRIPTION);

        $form->addSelectOpenTag("ftpType",
                                SETTING_SYSTEM_FTP_SETTING_TYPE);

        $modeFtp = CoreCache::getCacheList();
        $currentMode = CoreCache::getInstance()->getTransactionType();
        foreach ($modeFtp as $mode) {
            $actived = ($currentMode === $mode);
            $form->addSelectItemTag($mode,
                                    "",
                                    $actived);
        }
        $form->addSelectCloseTag();

        $form->addInputText("ftpHost",
                            SETTING_SYSTEM_FTP_SETTING_HOST,
                            $ftp['host']);
        $form->addInputText("ftpPort",
                            SETTING_SYSTEM_FTP_SETTING_PORT,
                            $ftp['port']);
        $form->addInputText("ftpUser",
                            SETTING_SYSTEM_FTP_SETTING_USER,
                            $ftp['user']);
        $form->addInputPassword("ftpPass",
                                SETTING_SYSTEM_FTP_SETTING_PASSWORD);
        $form->addInputText("ftpRoot",
                            SETTING_SYSTEM_FTP_SETTING_ROOT,
                            $ftp['root']);
        $form->addSpace();

        $coreSql = CoreSql::getInstance();
        $form->addFieldset(SETTING_SYSTEM_DATABASE_SETTING_TITLE,
                           SETTING_SYSTEM_DATABASE_SETTING_DESCRIPTION);
        $form->addInputText("dbHost",
                            SETTING_SYSTEM_DATABASE_SETTING_HOST,
                            $coreSql->getTransactionHost());
        $form->addInputText("dbName",
                            SETTING_SYSTEM_DATABASE_SETTING_NAME,
                            $coreSql->getDatabaseName());
        $form->addInputText("dbPrefix",
                            SETTING_SYSTEM_DATABASE_SETTING_PREFIX,
                            $coreSql->getDatabasePrefix());
        $form->addInputText("dbUser",
                            SETTING_SYSTEM_DATABASE_SETTING_USER,
                            $coreSql->getTransactionUser());
        $form->addInputPassword("dbPass",
                                SETTING_SYSTEM_DATABASE_SETTING_PASSWORD);

        $form->addSelectOpenTag("dbType",
                                SETTING_SYSTEM_DATABASE_SETTING_TYPE);
        $bases = CoreSql::getBaseList();
        foreach ($bases as $base) {
            if ($base == $coreSql->getTransactionType())
                $form->addSelectItemTag($base,
                                        "",
                                        true);
            else
                $form->addSelectItemTag($base);
        }
        $form->addSelectCloseTag();

        $form->addSpace();
        $form->addInputHidden("module",
                              "management");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputHidden("manage",
                              "setting");
        $form->addInputHidden("localView",
                              "sendSystem");
        $form->addInputSubmit("submit",
                              VALID);
        //CoreHtml::getInstance()->addJavascript("validSystemSetting('#form-management-setting-system', '#form-management-setting-system-defaultSiteName-input', '#form-management-setting-system-defaultAdministratorEmail-input');");
        return $form->render();
    }

    private function sendSystem()
    {
        $updateConfigFile = false;
        $coreMain = CoreMain::getInstance();

        $sessionTimeLimit = CoreRequest::getInteger("sessionTimeLimit",
                                                    7,
                                                    CoreRequestType::POST);
        if ($sessionTimeLimit != $coreMain->getConfigs()->getSessionTimeLimit()) {
            if ($sessionTimeLimit >= 1)
                $updateConfigFile = true;
            else
                $sessionTimeLimit = $coreMain->getConfigs()->getSessionTimeLimit();
        }

        $cryptKey = CoreRequest::getString("cryptKey",
                                           $coreMain->getConfigs()->getCryptKey(),
                                           CoreRequestType::POST);
        if ($cryptKey != $coreMain->getConfigs()->getCryptKey()) {
            if (!empty($cryptKey))
                $updateConfigFile = true;
            else
                $cryptKey = $coreMain->getConfigs()->getCryptKey();
        }

        $cookiePrefix = CoreRequest::getString("cookiePrefix",
                                               $coreMain->getConfigs()->getCookiePrefix(),
                                               CoreRequestType::POST);
        if ($cookiePrefix != $coreMain->getConfigs()->getCookiePrefix()) {
            if (!empty($cookiePrefix))
                $updateConfigFile = true;
            else
                $cookiePrefix = $coreMain->getConfigs()->getCookiePrefix();
        }

        if ($updateConfigFile) {
            ExecFileBuilder::buildConfigFile($coreMain->getConfigs()->getDefaultAdministratorEmail(),
                                             TR_ENGINE_STATUT,
                                             $sessionTimeLimit,
                                             $cookiePrefix,
                                             $cryptKey);
        }

        $ftp = $coreMain->getConfigs()->getConfigCache();
        $updateFtpFile = false;

        $ftpType = CoreRequest::getWord("ftpType",
                                        $ftp['type'],
                                        CoreRequestType::POST);
        if ($ftpType != $ftp['type']) {
            if (!empty($ftpType))
                $updateFtpFile = true;
            else
                $ftpType = $ftp['type'];
        }

        $ftpHost = CoreRequest::getString("ftpHost",
                                          $ftp['host'],
                                          CoreRequestType::POST);
        if ($ftpHost != $ftp['host']) {
            if (!empty($ftpHost))
                $updateFtpFile = true;
            else
                $ftpHost = $ftp['host'];
        }

        $ftpPort = CoreRequest::getInteger("ftpPort",
                                           21,
                                           CoreRequestType::POST);
        if ($ftpPort != $ftp['port']) {
            if (is_int($ftpPort))
                $updateFtpFile = true;
            else
                $ftpPort = $ftp['port'];
        }

        $ftpUser = CoreRequest::getString("ftpUser",
                                          $ftp['user'],
                                          CoreRequestType::POST);
        if ($ftpUser != $ftp['user']) {
            if (!empty($ftpUser))
                $updateFtpFile = true;
            else
                $ftpUser = $ftp['user'];
        }

        $ftpPass = CoreRequest::getString("ftpPass",
                                          $ftp['pass'],
                                          CoreRequestType::POST);
        if ($ftpPass != $ftp['pass']) {
            if (!empty($ftpPass))
                $updateFtpFile = true;
            else
                $ftpPass = $ftp['pass'];
        }

        $ftpRoot = CoreRequest::getString("ftpRoot",
                                          $ftp['root'],
                                          CoreRequestType::POST);
        if ($ftpRoot != $ftp['root']) {
            if (!empty($ftpRoot))
                $updateFtpFile = true;
            else
                $ftpRoot = $ftp['root'];
        }

        if ($updateFtpFile) {
            ExecFileBuilder::buildFtpFile($ftpHost,
                                          $ftpPort,
                                          $ftpUser,
                                          $ftpPass,
                                          $ftpRoot,
                                          $ftpType);
        }

        $coreSql = CoreSql::getInstance();
        $updateDatabaseFile = false;

        $dbHost = CoreRequest::getString("dbHost",
                                         $coreSql->getTransactionHost(),
                                         CoreRequestType::POST);
        if ($dbHost != $coreSql->getTransactionHost()) {
            if (!empty($dbHost))
                $updateDatabaseFile = true;
            else
                $dbHost = $coreSql->getTransactionHost();
        }

        $dbName = CoreRequest::getString("dbName",
                                         $coreSql->getDatabaseName(),
                                         CoreRequestType::POST);
        if ($dbName != $coreSql->getDatabaseName()) {
            if (!empty($dbName))
                $updateDatabaseFile = true;
            else
                $dbName = $coreSql->getDatabaseName();
        }

        $dbPrefix = CoreRequest::getString("dbPrefix",
                                           $coreSql->getDatabasePrefix(),
                                           CoreRequestType::POST);
        if ($dbPrefix != $coreSql->getDatabasePrefix()) {
            if (!empty($dbPrefix))
                $updateDatabaseFile = true;
            else
                $dbPrefix = $coreSql->getDatabasePrefix();
        }

        $dbUser = CoreRequest::getString("dbUser",
                                         $coreSql->getTransactionUser(),
                                         CoreRequestType::POST);
        if ($dbUser != $coreSql->getTransactionUser()) {
            if (!empty($dbUser))
                $updateDatabaseFile = true;
            else
                $dbUser = $coreSql->getTransactionUser();
        }

        $dbPass = CoreRequest::getString("dbPass",
                                         $coreSql->getTransactionPass(),
                                         CoreRequestType::POST);
        if ($dbPass != $coreSql->getTransactionPass()) {
            if (!empty($dbPass))
                $updateDatabaseFile = true;
            else
                $dbPass = $coreSql->getTransactionPass();
        }

        $dbType = CoreRequest::getWord("dbType",
                                       $coreSql->getTransactionType(),
                                       CoreRequestType::POST);
        if ($dbType != $coreSql->getTransactionType()) {
            if (!empty($dbType))
                $updateDatabaseFile = true;
            else
                $dbType = $coreSql->getTransactionType();
        }

        if ($updateDatabaseFile) {
            ExecFileBuilder::buildDatabaseFile($dbHost,
                                               $dbUser,
                                               $dbPass,
                                               $dbName,
                                               $dbType,
                                               $dbPrefix);
        }

        if ($coreMain->isDefaultLayout()) {
            CoreHtml::getInstance()->redirect("index.php?module=management&manage=setting&selectedTab=settingtabidTab1",
                                              1);
        }
    }
}
?>