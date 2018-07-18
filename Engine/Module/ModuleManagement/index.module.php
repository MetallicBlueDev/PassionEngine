<?php

/**
 * Module d'interface entre le site et le client.
 *
 * @author Sébastien Villemain
 */
class Module_Management_Index extends ModuleModel
{

    /**
     * Tableau contenant les boutons de la barre d'outil.
     *
     * @var array
     */
    private static $toolbar = array();

    public function display()
    {
        // Ajout du CSS du template et du fichier javascript par défaut
        CoreHtml::getInstance()->addCssTemplateFile("module_management.css");
        CoreHtml::getInstance()->addJavascriptFile("management.js");

        // Nom de la page administable
        $managePage = CoreRequest::getString("manage");

        $pageList = self::getManagementList(); // Liste de pages de configuration
        $moduleList = LibModule::getModuleList(); // Liste des modules
        // Préparation de la mise en page
        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignArray("pageList",
                                   $pageList);
        $libMakeStyle->assignArray("moduleList",
                                   $moduleList);

        // Affichage de la page d'administration
        $managementScreen = "module_management_index";
        $pageSelected = "";
        if (!empty($managePage)) { // Affichage d'une page de configuration spécial
            $settingPage = ExecUtils::inMultiArray($managePage,
                                                   $pageList);
            $moduleSettingPage = ExecUtils::inMultiArray($managePage,
                                                         $moduleList);

            // Si c'est une page valide
            if ($settingPage || $moduleSettingPage) {
                $pageSelected = $managePage; // Page Sélectionnée
                $managementScreen = "module_management_setting"; // Nom du template
                // Récuperation du module
                $moduleClassPage = "";
                $moduleClassName = "";
                if ($settingPage) {
                    $moduleClassPage = "Module_Management_" . ucfirst($managePage) . ".setting";
                    $moduleClassName = "Module_Management_" . ucfirst($managePage);
                } else {
                    $moduleClassPage = $moduleClassName = "Module_" . ucfirst($managePage) . "_Index";
                }

                // Si aucune erreur, on lance le module
                if (CoreLoader::classLoader($moduleClassPage)) {
                    // Nom de la page courane
                    $currentPageName = self::getManagementPageName($pageSelected);
                    $libMakeStyle->assignString("currentPageName",
                                                $currentPageName);

                    // Ajout du repere au fil d'ariane
                    if (CoreMain::getInstance()->isDefaultLayout()) {
                        LibBreadcrumb::getInstance()->addTrail(
                                $currentPageName,
                                "?" . CoreLayout::REQUEST_MODULE . "=management&manage=" . $pageSelected
                        );
                    }

                    $ModuleClass = new $moduleClassName();
                    $content = "";
                    if (CoreLoader::isCallable($moduleClassPage,
                                               "setting")) {
                        $content = $ModuleClass->setting();
                    }

                    $libMakeStyle->assignString("content",
                                                $content);
                }
            }
        }
        $libMakeStyle->assignArray("toolbar",
                                   self::$toolbar);
        $libMakeStyle->assignString("pageSelected",
                                    $pageSelected);
        $libMakeStyle->display($managementScreen);
    }

    public function install()
    {
        // Aucune installation n'est permise
    }

    public function uninstall()
    {
        // Aucune désinstallation n'est permise
    }

    /**
     * Retourne un tableau contenant les pages d'administration disponibles.
     *
     * @return array => array("value" => valeur de la page, "name" => nom de la page).
     */
    public static function &getManagementList()
    {
        $pageList = array();
        $files = CoreCache::getInstance()->getFileList("modules/management",
                                                       ".setting.module");
        $currentAccessType = $this->getAccessType();

        foreach ($files as $page) {
            $currentAccessType->setPage("management/" . $page . ".setting");

            // Vérification des droits de l'utilisateur
            if (CoreAccess::autorize($currentAccessType)) {
                $name = self::getManagementPageName($page);
                $pageList[] = array(
                    "value" => $page,
                    "name" => $name);
            }
        }
        return $pageList;
    }

    /**
     * Retourne le nom littérale de la page.
     *
     * @param $page string
     * @return string
     */
    private static function &getManagementPageName($page)
    {
        $name = defined("SETTING_TITLE_" . strtoupper($page)) ? constant("SETTING_TITLE_" . strtoupper($page)) : ucfirst($page);
        return $name;
    }

    /**
     * Ajout d'un bouton a la barre d'outil.
     *
     * @param string $name
     * @param string $description
     * @param string $link
     */
    private static function addButtonInToolbar($name, $description, $link)
    {
        self::$toolbar[] = array(
            "name" => $name,
            "description" => $description,
            "link" => $link
        );
    }

    public static function addEditButtonInToolbar($link, $description = "")
    {
        if (empty($description))
            $description = EDIT;
        self::addButtonInToolbar("edit",
                                 $description,
                                 $link);
    }

    public static function addDeleteButtonInToolbar($link, $description = "")
    {
        if (empty($description))
            $description = DELETE;
        self::addButtonInToolbar("delete",
                                 $description,
                                 $link);
    }

    public static function addCopyButtonInToolbar($link, $description = "")
    {
        if (empty($description))
            $description = COPY;
        self::addButtonInToolbar("copy",
                                 $description,
                                 $link);
    }

    public static function addAddButtonInToolbar($link, $description = "")
    {
        if (empty($description))
            $description = ADD;
        self::addButtonInToolbar("add",
                                 $description,
                                 $link);
    }
}
?>