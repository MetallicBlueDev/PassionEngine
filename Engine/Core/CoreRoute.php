<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibBlockData;
use TREngine\Engine\Lib\LibModuleData;
use TREngine\Engine\Lib\LibModule;

/**
 *
 *
 * @author Sébastien Villemain
 */
class CoreRoute
{

    /**
     * Mode de mise en page.
     *
     * @var string
     */
    private $layout = CoreLayout::DEFAULT_LAYOUT;

    /**
     * Nom du module.
     *
     * @var string
     */
    private $module = "";

    /**
     * Nom de la page.
     *
     * @var string
     */
    private $page = "";

    /**
     * Nom de la méthode d'affichage.
     *
     * @var string
     */
    private $view = "";

    /**
     * Retourne le mode de mise en page.
     *
     * @return string
     */
    public function &getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Détermine si l'affichage se fait en écran complet (affichage classique).
     *
     * @return bool true c'est en plein écran.
     */
    public function isDefaultLayout(): bool
    {
        return ($this->layout === CoreLayout::DEFAULT_LAYOUT) ? true : false;
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé vers un module.
     *
     * @return bool true c'est un affichage de module uniquement.
     */
    public function isModuleLayout(): bool
    {
        return ($this->layout === CoreLayout::MODULE || $this->layout === CoreLayout::MODULE_PAGE) ? true : false;
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé vers un block.
     *
     * @return bool true c'est un affichage de block uniquement.
     */
    public function isBlockLayout(): bool
    {
        return ($this->layout === CoreLayout::BLOCK || $this->layout == CoreLayout::BLOCK_PAGE) ? true : false;
    }

    /**
     * Vérification et assignation de la mise en page demandée.
     */
    public function requestLayout(): void
    {
        $this->requestedOrDefaultLayout();
        $this->checkLayout();
    }

    /**
     * Vérification et assignation du module demandé.
     */
    public function requestModule(): void
    {
        $this->requestedOrDefaultModule();
        $this->checkModule();
    }

    /**
     * Retourne les informations du module demandé.
     *
     * @return LibModuleData Informations sur le module.
     */
    public function &getRequestedModuleData(): LibModuleData
    {
        return LibModule::getInstance()->getModuleData($this->module);
    }

    public function setModule(string $module): CoreRoute
    {
        $this->module = $module;

        if (!$this->isModuleLayout()) {
            $this->layout = CoreLayout::MODULE;
        }
        return $this;
    }

    public function setModuleData(LibModuleData $moduleData): CoreRoute
    {
        return $this->setModule($moduleData->getName());
    }

    public function setBlockId(int $blockId): CoreRoute
    {
        $this->module = $module;

        if (!$this->isModuleLayout()) {
            $this->layout = CoreLayout::MODULE;
        }
        return $this;
    }

    public function setBlockType(string $blockType): CoreRoute
    {
        return $this;
    }

    public function setBlockData(LibBlockData $blockData): CoreRoute
    {
        return $this;
    }

    public function setPage(string $page): CoreRoute
    {
        return $this;
    }

    public function setView(string $view): CoreRoute
    {
        return $this;
    }

    /**
     * Vérification de la configuration possible de la mise en page.
     */
    private function checkLayout(): void
    {
        if ($this->layout !== CoreLayout::DEFAULT_LAYOUT && $this->layout !== CoreLayout::MODULE_PAGE && $this->layout !== CoreLayout::BLOCK_PAGE && (($this->layout !== CoreLayout::MODULE && $this->layout !== CoreLayout::BLOCK) || (!CoreHtml::getInstance()->javascriptEnabled()))) {
            $this->layout = CoreLayout::DEFAULT_LAYOUT;
        }
    }

    /**
     * Vérification de la configuration du module.
     */
    private function checkModule(): void
    {
        $moduleData = $this->requestModuleData();

        if ($moduleData === null || !$moduleData->isValid()) {
            // Afficher une erreur 404
            if (!empty($this->module)) {
                CoreLogger::addInfo(ERROR_404);
            }

            $moduleData = $this->requestDefaultModuleData();
        }

        $this->module = ($moduleData !== null) ? $moduleData->getName() : "";
    }

    /**
     * Retourne les informations sur le module demandé.
     *
     * @return LibModuleData
     */
    private function &requestModuleData(): ?LibModuleData
    {
        $moduleData = null;

        if (!empty($this->module)) {
            $moduleData = $this->getRequestedModuleData();

            if ($moduleData != null) {
                $this->requestedOrDefaultPage();
                $this->requestedOrDefaultView();

                $moduleData->setPage($this->page);
                $moduleData->setView($this->view);
            }
        }
        return $moduleData;
    }

    /**
     * Retourne les informations sur le module par défaut.
     *
     * @return LibModuleData
     */
    private function &requestDefaultModuleData(): ?LibModuleData
    {
        $this->module = CoreMain::getInstance()->getConfigs()->getDefaultModule();
        $this->page = CoreLayout::DEFAULT_PAGE;
        $this->view = CoreLayout::DEFAULT_VIEW;

        $moduleData = $this->getRequestedModuleData();
        $moduleData->setPage($this->page);
        $moduleData->setView($this->view);
        return $moduleData;
    }

    /**
     * Assignation de la mise en page demandée.
     */
    public function requestedOrDefaultLayout(): void
    {
        $layout = strtolower(CoreRequest::getWord(CoreLayout::REQUEST_LAYOUT));

        if (empty($layout)) {
            $layout = CoreLayout::DEFAULT_LAYOUT;
        }

        $this->layout = $layout;
    }

    /**
     * Assignation du module demandé.
     */
    private function requestedOrDefaultModule(): void
    {
        $module = CoreRequest::getWord(CoreLayout::REQUEST_MODULE);
        $this->module = $module;
    }

    /**
     * Assignation la page demandée.
     */
    private function requestedOrDefaultPage(): void
    {
        $page = CoreRequest::getWord(CoreLayout::REQUEST_PAGE);

        if (empty($page)) {
            $page = CoreLayout::DEFAULT_PAGE;
        }

        $this->page = $page;
    }

    /**
     * Assignation de la méthode d'affichage.
     */
    private function requestedOrDefaultView(): void
    {
        $view = CoreRequest::getWord(CoreLayout::REQUEST_VIEW);

        if (empty($view)) {
            $view = CoreLayout::DEFAULT_VIEW;
        }

        $this->view = $view;
    }
}