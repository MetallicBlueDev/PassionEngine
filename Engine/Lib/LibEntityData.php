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

    /**
     * @var string Le nom complet de la classe.
     */
    private $fullQualifiedClassName = null;

    /**
     * La page sélectionnée.
     *
     * @var string
     */
    private $page = CoreLayout::DEFAULT_PAGE;

    /**
     * Le paramètre d'affichage.
     *
     * @var string
     */
    private $view = "";

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
     * Détermine si l'entité peut être utilisée.
     *
     * @return bool true L'entité peut être utilisée.
     */
    abstract public function &canUse(): bool;

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
        $qualifiedClassName = $this->getFullQualifiedClassName();
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
     * Retourne le paramètre d'affichage sélectionné.
     *
     * @return string
     */
    public function &getView(): string
    {
        return $this->view;
    }

    /**
     * Affecte le paramètre d'affichage sélectionné.
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
            $this->fullQualifiedClassName = CoreLoader::getFullQualifiedClassName($this->getClassName(),
                                                                                  $this->getFolderName());
        }
        return $this->fullQualifiedClassName;
    }

    /**
     * Détermine si l'appel à la méthode d'affichage semble valide.
     *
     * @return bool
     */
    public function isCallableViewMethod(): bool
    {
        return $this->isCallable(CoreLayout::DEFAULT_VIEW_DISPLAY);
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
            if ($methodName === CoreLayout::DEFAULT_VIEW_INSTALL || $methodName === CoreLayout::DEFAULT_VIEW_UNINSTALL || $methodName === CoreLayout::DEFAULT_VIEW_SETTING) {
                $sessionData = CoreSession::getInstance()->getSessionData();

                if ($sessionData->hasAdminRank()) {
                    if ($methodName === CoreLayout::DEFAULT_VIEW_INSTALL && !$this->installed()) {
                        $valid = true;
                    } else if ($methodName === CoreLayout::DEFAULT_VIEW_UNINSTALL && $this->installed()) {
                        $valid = true;
                    } else if ($methodName === CoreLayout::DEFAULT_VIEW_SETTING && $this->installed()) {
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