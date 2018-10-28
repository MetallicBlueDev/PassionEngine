<?php

namespace PassionEngine\Custom\Module\ModuleCustomProject;

use PassionEngine\Engine\Module\ModuleModel;
use PassionEngine\Engine\Core\CoreRequest;
use PassionEngine\Engine\Core\CoreRequestType;
use PassionEngine\Engine\Core\CoreHtml;
use PassionEngine\Engine\Core\CoreSql;
use PassionEngine\Engine\Core\CoreUrlRewriting;
use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Lib\LibForm;
use PassionEngine\Engine\Exec\ExecJQuery;

class ModuleIndex extends ModuleModel
{

    const PROJECT_TABLE = "project";

    public function display()
    {
        $this->displayProjectsList();
    }

    public function displayProjectsList()
    {
        $selectedBase = CoreSql::getInstance()->getSelectedBase();
        $selectedBase->select(self::PROJECT_TABLE,
                              array(
                    "projectid",
                    "name",
                    "date",
                    "language",
                    "progress"),
                              array(),
                              array(
                    "progress ASC",
                    "date DESC"))->query();

        CoreHtml::getInstance()->addCssTemplateFile("module_project.css");
        $libMakeStyle = new LibMakeStyle("module_project_list");
        $libMakeStyle->assignString("title",
                                    PROJECTS_LIST_TITLE);
        $libMakeStyle->assignString("description",
                                    PROJECTS_LIST_DESCRIPTION);

        if ($selectedBase->affectedRows() > 0) {
            $selectedBase->addArrayBuffer("projectList");
            $projects = $selectedBase->getBuffer("projectList");
            $selectedBase->freeBuffer();
            $libMakeStyle->assignString("projects",
                                        $projects);
            $libMakeStyle->assignString("nbProjects",
                                        count($projects) . " " . NB_PROJECT);
        } else {
            $libMakeStyle->assignString("nbProjects",
                                        NO_PROJECT);
        }
        $libMakeStyle->display();
    }

    public function displayProject()
    {
        // Identifiant du projet
        $projectId = CoreRequest::getInteger("projectId",
                                             -1,
                                             CoreRequestType::GET);

        if ($projectId >= 0) {
            $values = array(
                "projectid",
                "name",
                "date",
                "language",
                "sourcelink",
                "binairelink",
                "description",
                "img",
                "progress",
                "website");
            $selectedBase = CoreSql::getInstance()->getSelectedBase();
            $selectedBase->select(self::PROJECT_TABLE,
                                  $values,
                                  array(
                        "projectid = '" . $projectId . "'"))->query();

            if ($selectedBase->affectedRows() == 1) {
                $projectInfo = $selectedBase->fetchArray();

                // Préparation de l'entête
                ExecJQuery::checkSlimbox();
                CoreHtml::getInstance()->addJavascriptJquery("$('.project_description_img a').slimbox();");
                CoreHtml::getInstance()->addCssTemplateFile("module_project.css");

                // Création de la page
                $form = new LibForm(
                        "project_description",
                        CoreUrlRewriting::getLink("?" . CoreLayout::REQUEST_MODULE . "=project&" . CoreLayout::REQUEST_VIEW . "=download&&projectId=" . $projectInfo['projectid'])
                );
                $form->setTitle($projectInfo['name']);

                $libMakeStyle = new LibMakeStyle("module_project_description");
                $libMakeStyle->assignArray("projectInfo",
                                           $projectInfo);

                $form->addHtmlInFieldset($libMakeStyle->render());
                $form->addFieldset(PROJECT_DESCRIPTION);

                if (!empty($projectInfo['description'])) {
                    $form->addHtmlInFieldset($projectInfo['description']);
                } else {
                    $form->addHtmlInFieldset(NO_DESCRIPTION);
                }

                $form->addSpace();
                $form->addFieldset(DOWNLOAD_DETAILS);

                $form->addInputSubmit("download_binaire",
                                      DOWNLOAD_BINAIRE,
                                      !empty($projectInfo['binairelink']) ? "" : "disabled=\"disabled\"");
                $form->addInputSubmit("download_source",
                                      DOWNLOAD_SOURCE,
                                      !empty($projectInfo['sourcelink']) ? "" : "disabled=\"disabled\"");

                if (empty($projectInfo['binairelink']) && empty($projectInfo['sourcelink'])) {
                    $form->addHtmlInFieldset(NO_DOWNLOAD);
                } else {
                    $form->addHtmlInFieldset("");
                }
                echo $form->render();
            } else {
                $this->displayProjectList();
            }
        } else {
            $this->displayProjectsList();
        }
    }

    public function download()
    {
//        $projectId = CoreRequest::getInteger("projectId", -1, CoreRequestType::POST);
//        $type = CoreRequest::getInteger("type", -1, CoreRequestType::POST);
    }

    public function setting()
    {
        return "Pas de setting...";
    }
}