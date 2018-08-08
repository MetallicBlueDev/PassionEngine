<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLayout;
use TREngine\Engine\Core\CoreDataStorage;
use TREngine\Engine\Core\CoreAccessToken;

/**
 * Information de base sur une entité de bibliothèque.
 *
 * @author Sébastien Villemain
 */
abstract class LibEntityData extends CoreDataStorage implements CoreAccessToken
{

    private $fullQualifiedClassName = null;

    /**
     * La page sélectionnée.
     *
     * @var string
     */
    private $page = CoreLayout::DEFAULT_PAGE;

    /**
     * La sous page.
     *
     * @var string
     */
    private $view = CoreLayout::DEFAULT_VIEW;

    /**
     * Les données compilées.
     *
     * @var string
     */
    private $temporyOutputBuffer = "";

    /**
     * Nouvelle information d'une entité.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Retourne le nom du dossier contenant l'entité.
     *
     * @return string
     */
    abstract public function getFolderName(): string;

    /**
     * Retourne le nom de classe représentant l'entité.
     *
     * @return string
     */
    abstract public function getClassName(): string;

    /**
     * Détermine si l'entité est installée.
     *
     * @return bool
     */
    abstract public function installed(): bool;

    /**
     * Compilation de données de sortie.
     */
    abstract public function buildFinalOutput(): void;

    /**
     * Retourne les données de sortie compilées.
     *
     * @return string
     */
    abstract public function &getFinalOutput(): string;

    /**
     * Retourne les données temporaires de sortie compilées.
     *
     * @return string
     */
    public function &getTemporyOutputBuffer(): string
    {
        return $this->temporyOutputBuffer;
    }

    /**
     * Affecte les données temporaires de sortie compilées.
     *
     * @param string $temporyOutputBuffer
     */
    public function setTemporyOutputBuffer(string $temporyOutputBuffer): void
    {
        $this->temporyOutputBuffer = $temporyOutputBuffer;
    }

    /**
     * Détermine si l'entité est valide.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $qualifiedClassName = CoreLoader::getFullQualifiedClassName($this->getClassName(),
                                                                    $this->getFolderName());
        return is_file(TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . CoreLoader::getFilePathFromNamespace($qualifiedClassName) . ".php");
    }

    /**
     * Retourne la page sélectionnée.
     *
     * @return string
     */
    public function &getPage(): string
    {
        return $this->page;
    }

    /**
     * Affecte la page sélectionnée.
     *
     * @param string $page
     */
    public function setPage(string $page): void
    {
        $this->page = ucfirst($page);
    }

    /**
     * Retourne la méthode d'affichage sélectionnée.
     *
     * @return string
     */
    public function &getView(): string
    {
        return $this->view;
    }

    /**
     * Affecte la méthode d'affichage sélectionnée.
     *
     * @param string $view
     */
    public function setView(string $view): void
    {
        $this->view = $view;
    }

    /**
     * Retourne le nom complet de la classe.
     *
     * @return string
     */
    public function &getFullQualifiedClassName(): string
    {
        if ($this->fullQualifiedClassName === null) {
            $this->fullQualifiedClassName = CoreLoader::getFullQualifiedClassName($this->getClassName(), $this->getFolderName());
        }
        return $this->fullQualifiedClassName;
    }

    /**
     * Détermine si l'appel à la méthode d'affichage semble valide.
     *
     * @return bool
     */
    public function isCallableView(): bool
    {
        return !empty($this->getView()) && $this->isCallable($this->getView());
    }

    /**
     * Détermine si l'appel semble valide.
     *
     * @param string $methodName Nom de la méthode.
     * @return bool
     */
    public function &isCallable(string $methodName): bool
    {
        $valid = false;

        if (CoreLoader::isCallable($this->getFullQualifiedClassName(),
                                   $methodName)) {
            if ($methodName === "install" || $methodName === "uninstall" || $methodName === "setting") {
                $sessionData = CoreSession::getInstance()->getSessionData();

                if ($sessionData->hasAdminRank()) {
                    if ($methodName === "install" && !$this->installed()) {
                        $valid = true;
                    } else if ($methodName === "uninstall" && $this->installed()) {
                        $valid = true;
                    } else if ($methodName === "setting" && $this->installed()) {
                        $valid = true;
                    }
                }
            } else {
                $valid = true;
            }
        }
        return $valid;
    }

    /**
     * Retourne l'entité correspondant aux informations.
     *
     * @return LibEntityModel
     */
    public function &getNewEntityModel(): LibEntityModel
    {
        $fullClassName = $this->getFullQualifiedClassName();

        /**
         * @var LibEntityModel
         */
        $entityClass = new $fullClassName();
        $entityClass->setEntityData($this);
        return $entityClass;
    }
}