<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Lib\LibBlockData;
use PassionEngine\Engine\Lib\LibModuleData;
use PassionEngine\Engine\Lib\LibBlock;
use PassionEngine\Engine\Lib\LibModule;
use PassionEngine\Engine\Fail\FailEngine;

/**
 * Gestionnaire de chemin.
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
    private $module = '';

    /**
     * Nom de la page.
     *
     * @var string
     */
    private $page = '';

    /**
     * Nom du paramètre d'affichage.
     *
     * @var string
     */
    private $view = '';

    /**
     * Identifiant du block demandé.
     *
     * @var int
     */
    private $blockId = -1;

    /**
     * Type de block demandé.
     *
     * @var string
     */
    private $blockType = '';

    /**
     * Détermine si le chemin supporte le javaScript.
     *
     * @var bool
     */
    private $jsMode = false;

    /**
     * Nom complet de l'identifiant la division du contenu permettant d'afficher la page.
     *
     * @var string
     */
    private $jsDivisionId = '';

    /**
     * Action spécifique sur l'événement de clique d'un lien.
     *
     * @var string
     */
    private $onClickForLink = '';

    /**
     * Code HTML supplémentaire d'un lien.
     *
     * @var string
     */
    private $addonsForLink = '';

    private function __construct()
    {

    }

    /**
     * Demande la création d'un nouveau router.
     *
     * @return CoreRoute
     */
    public static function getNewRoute()
    {
        return new CoreRoute();
    }

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
     * Retourne le nom de la page.
     *
     * @return string
     */
    public function &getPage(): string
    {
        return $this->page;
    }

    /**
     * Retourne le paramètre d'affichage.
     *
     * @return string
     */
    public function &getView(): string
    {
        return $this->view;
    }

    /**
     * Détermine si l'affichage se fait en écran complet (affichage classique).
     *
     * @return bool C'est en plein écran.
     */
    public function isDefaultLayout(): bool
    {
        return ($this->layout === CoreLayout::DEFAULT_LAYOUT) ? true : false;
    }

    /**
     * Détermine si l'affichage se fait en écran minimal avec un module.
     *
     * @return bool C'est un affichage de module uniquement.
     */
    public function isModuleLayout(): bool
    {
        return ($this->layout === CoreLayout::MODULE || $this->layout === CoreLayout::MODULE_PAGE) ? true : false;
    }

    /**
     * Détermine si l'affichage se fait en écran minimal avec un block.
     *
     * @return bool C'est un affichage de block uniquement.
     */
    public function isBlockLayout(): bool
    {
        return ($this->layout === CoreLayout::BLOCK || $this->layout == CoreLayout::BLOCK_PAGE) ? true : false;
    }

    /**
     * Détermine si le chemin supporte le javaScript.
     *
     * @return bool
     */
    public function jsMode(): bool
    {
        return $this->jsMode;
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
     * Vérification et assignation du block demandé.
     */
    public function requestBlock(): void
    {
        $this->requestedOrDefaultBlock();
        $this->checkBlock();
    }

    /**
     * Retourne les informations du module demandé.
     *
     * @return LibModuleData Informations sur le module.
     */
    public function &getRequestedModuleData(): LibModuleData
    {
        return LibModule::getInstance()->getEntityDataByFolderName($this->module);
    }

    /**
     * Retourne les informations du block demandé.
     *
     * @return LibBlockData Informations sur le block.
     */
    public function &getRequestedBlockDataById(): LibBlockData
    {
        return LibBlock::getInstance()->getEntityData($this->blockId);
    }

    /**
     * Retourne les informations du block demandé.
     *
     * @return LibBlockData Informations sur le block.
     */
    public function &getRequestedBlockDataByType(): LibBlockData
    {
        return LibBlock::getInstance()->getEntityDataByFolderName($this->blockType);
    }

    /**
     * Aller vers le module demandé.
     *
     * @param string $module Nom du module.
     * @return CoreRoute
     */
    public function setModule(string $module): CoreRoute
    {
        $this->module = $module;
        $this->checkModuleAffectation();
        return $this;
    }

    /**
     * Aller vers le module demandé.
     *
     * @param LibModuleData $moduleData
     * @return CoreRoute
     */
    public function setModuleData(LibModuleData $moduleData): CoreRoute
    {
        return $this->setModule($moduleData->getName());
    }

    /**
     * Aller vers le block demandé.
     *
     * @param int $blockId
     * @return CoreRoute
     */
    public function setBlockId(int $blockId): CoreRoute
    {
        $this->blockId = $blockId;
        $this->checkBlockAffection();
        return $this;
    }

    /**
     * Aller vers le block demandé.
     *
     * @param string $blockType
     * @return CoreRoute
     */
    public function setBlockType(string $blockType): CoreRoute
    {
        $this->blockType = $blockType;
        $this->checkBlockAffection();
        return $this;
    }

    /**
     * Aller vers le block demandé.
     *
     * @param LibBlockData $blockData
     * @return CoreRoute
     */
    public function setBlockData(LibBlockData $blockData): CoreRoute
    {
        return $this->setBlockId($blockData->getId());
    }

    /**
     * Aller vers la page demandé.
     *
     * @param string $page
     * @return CoreRoute
     */
    public function setPage(string $page): CoreRoute
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Aller vers le paramètre d'affichage demandé.
     *
     * @param string $view
     * @return CoreRoute
     */
    public function setView(string $view): CoreRoute
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Active support du javaScript.
     *
     * @param bool $enable
     * @param bool $jsDivisionId
     * @return CoreRoute
     * @throws FailEngine
     */
    public function setJsMode(bool $enable,
                              string $jsDivisionId = ''): CoreRoute
    {
        if ($enable) {
            if (empty($jsDivisionId)) {
                throw new FailEngine('Division identifier is empty');
            }

            CoreHtml::getInstance()->addJavascriptFile('jquery.js');
        }


        $this->jsMode = $enable;
        $this->jsDivisionId = $enable ? $jsDivisionId : '';
        return $this;
    }

    /**
     * Action spécifique sur l'événement de clique d'un lien.
     *
     * @param string $value
     * @return CoreRoute
     */
    public function setOnClickAction(string $value): CoreRoute
    {
        $this->onClickForLink = $value;
        return $this;
    }

    /**
     * Code HTML supplémentaire d'un lien.
     *
     * @param string $value
     * @return CoreRoute
     */
    public function setAddonsAction(string $value): CoreRoute
    {
        $this->addonsForLink = $value;
        return $this;
    }

    /**
     * Retourne le lien complet HTML pour le chemin demandé.
     *
     * @param string $displayContent Données à afficher (texte simple ou code HTML).
     * @return string
     */
    public function getLink(string $displayContent): string
    {
        $normalLink = $this->getRawLink();

        if ($this->jsMode) {
            $jsLink = CoreUrlRewriting::getLink($this->getBlockLink(),
                                                true);
            $this->setOnClickAction('validLink(\'' . $this->jsDivisionId . '\', \'' . $jsLink . '\');return false;');
        }

        return CoreHtml::getLink($normalLink,
                                 $displayContent,
                                 $this->onClickForLink,
                                 $this->addonsForLink);
    }

    /**
     * Retourne le lien brut pour le chemin demandé.
     *
     * @return string
     */
    private function getRawLink(): string
    {
        $link = '';

        if ($this->isModuleLayout()) {
            $link = $this->getModuleLink();
        } else if ($this->isBlockLayout()) {
            $link = $this->getBlockLink();
        } else {
            $link = $this->getModuleLink() . $this->getBlockLink();
        }
        return $link;
    }

    /**
     * Vérifie si la mise à page est un module.
     */
    private function checkModuleAffectation(): void
    {
        if (!$this->isModuleLayout()) {
            $this->layout = CoreLayout::MODULE;
        }

        // Nettoyage du chemin.
        $this->setPage('');
        $this->setView('');
    }

    /**
     * Vérifie si la mise à page est un block.
     */
    private function checkBlockAffection(): void
    {
        if (!$this->jsMode && !$this->isBlockLayout()) {
            $this->layout = CoreLayout::BLOCK;
        }
    }

    /**
     * Retourne le lien pour un chemin vers un module.
     *
     * @return string
     */
    private function getModuleLink(): string
    {
        return CoreLayout::REQUEST_MODULE . '=' . $this->module
            . (!empty($this->page) ? '&amp;' . CoreLayout::REQUEST_PAGE . '=' . $this->page : '')
            . (!empty($this->view) ? '&amp;' . CoreLayout::REQUEST_VIEW . '=' . $this->view : '');
    }

    /**
     * Retourne le lien pour un chemin vers un block.
     *
     * @return string
     */
    private function getBlockLink(): string
    {
        return (!empty($this->blockId) ?
            CoreLayout::REQUEST_BLOCKID . '=' . $this->blockId :
            (!empty($this->blockType) ?
            CoreLayout::REQUEST_BLOCKTYPE . '=' . $this->blockType : ''))
            . (!empty($this->page) ? '&amp;' . CoreLayout::REQUEST_PAGE . '=' . $this->page : '')
            . (!empty($this->view) ? '&amp;' . CoreLayout::REQUEST_VIEW . '=' . $this->view : '');
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
                CoreLogger::addUserWarning(ERROR_404);
            }

            $moduleData = $this->requestDefaultModuleData();
        }

        $this->module = ($moduleData !== null) ? $moduleData->getName() : '';
    }

    /**
     * Vérification du block.
     */
    private function checkBlock(): void
    {
        $blockData = $this->requestBlockData();

        if ($blockData !== null && !$blockData->isValid()) {
            $blockData = null;
        }

        $this->blockId = ($blockData !== null) ? $blockData->getId() : -1;

        if (!empty($this->blockType)) {
            $this->blockType = ($blockData !== null) ? $blockData->getType() : '';
        }
    }

    /**
     * Retourne les informations sur le block demandé.
     *
     * @return LibBlockData
     */
    private function &requestBlockData(): ?LibBlockData
    {
        $blockData = null;

        if ($this->blockId >= 0) {
            $blockData = $this->getRequestedBlockDataById();
        } else if (!empty($this->blockType)) {
            $blockData = $this->getRequestedBlockDataByType();
        }

        if ($blockData !== null && $blockData->getId() < 0) {
            $blockData = null;
        }

        if ($blockData !== null) {
            $this->requestedOrDefaultPage();
            $this->requestedOrDefaultView();

            $blockData->setPage($this->page);
            $blockData->setView($this->view);
        }
        return $blockData;
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

            if ($moduleData->getId() >= 0) {
                $this->requestedOrDefaultPage();
                $this->requestedOrDefaultView();

                $moduleData->setPage($this->page);
                $moduleData->setView($this->view);
            } else {
                $moduleData = null;
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
        $this->view = '';

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
     * Assignation du paramètre d'affichage.
     */
    private function requestedOrDefaultView(): void
    {
        $view = CoreRequest::getWord(CoreLayout::REQUEST_VIEW);
        $this->view = $view;
    }

    /**
     * Assignation du block (par son identifiant ou par son type).
     */
    private function requestedOrDefaultBlock(): void
    {
        $this->blockId = CoreRequest::getInteger(CoreLayout::REQUEST_BLOCKID,
                                                 -1);

        if ($this->blockId < 0) {
            $this->blockType = CoreRequest::getString(CoreLayout::REQUEST_BLOCKTYPE);
        }
    }
}