<?php

namespace PassionEngine\Engine\Exec;

use PassionEngine\Engine\Core\CoreHtml;

/**
 * Outil de manipulation de JQuery.
 *
 * @author Sébastien Villemain
 */
class ExecJQuery
{

    public static function checkTreeView(string $identifier): void
    {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addCssResourceFile("jquery.treeview.css");
        $coreHtml->addJavascriptFile("jquery.treeview.js");
        $coreHtml->addJavascriptJquery("$('" . $identifier . "').treeview({animated: 'fast', collapsed: true, unique: true, persist: 'location'});");
    }

    public static function checkJdMenu(): void
    {
        $coreHtml = CoreHtml::getInstance();
        // Ajout du fichier de style
        $coreHtml->addCssResourceFile("jquery.jdMenu.css");

        // Ajout des fichier javascript
        $coreHtml->addJavascriptFile("jquery.dimensions.js");
        $coreHtml->addJavascriptFile("jquery.positionBy.js");
        $coreHtml->addJavascriptFile("jquery.bgiframe.js");
        $coreHtml->addJavascriptFile("jquery.jdMenu.js");

        // Ajout du code d'execution
        $coreHtml->addJavascriptJquery("$('ul.jd_menu').jdMenu();");
    }

    public static function checkSlimbox(): void
    {
        $coreHtml = CoreHtml::getInstance();
        $coreHtml->addJavascriptFile("jquery.slimbox.js");
        $coreHtml->addCssResourceFile("jquery.slimbox.css");
    }
}