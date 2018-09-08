<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Entité de bibliothèque de base, hérité par toutes les autres entités.
 * Modèle pour le contenu d'une entité.
 *
 * @author Sébastien Villemain
 */
abstract class LibEntityModel
{

    /**
     * Informations sur l'entité.
     *
     * @var LibEntityData
     */
    private $entityData = null;

    /**
     * Affichage par défaut de l'entité.
     *
     * @param string $view Paramètre d'affichage.
     */
    abstract public function display(string $view): void;

    /**
     * Procédure de configuration de l'entité.
     */
    abstract public function setting(): void;

    /**
     * Procédure d'installation de l'entité.
     */
    abstract public function install(): void;

    /**
     * Procédure de désinstallation de l'entité.
     */
    abstract public function uninstall(): void;

    /**
     * Retourne la liste des méthodes d'affichages possibles.
     */
    abstract public function getViewList(): array;

    /**
     * Retourne l'accès spécifique de cette entité.
     *
     * @return CoreAccessType
     */
    public function &getAccessType(): CoreAccessType
    {
        return CoreAccessType::getTypeFromToken($this->getEntityData());
    }

    /**
     * Détermine si le paramètre d'affichage est disponible.
     *
     * @return CoreAccessType
     */
    public function &isInViewList(string $view): bool
    {
        $inArray = empty($view) || ExecUtils::inArrayStrictCaseInSensitive($view,
                                                                           $this->getViewList());
        return $inArray;
    }

    /**
     * Affecte les données de l'entité.
     *
     * @param LibEntityData $data
     */
    public function setEntityData(LibEntityData &$data): void
    {
        $this->entityData = $data;
    }

    /**
     * Retourne le données de l'entité.
     *
     * @return LibEntityData
     */
    protected function &getEntityData(): LibEntityData
    {
        return $this->entityData;
    }

    /**
     * Détermine si des informations sur l'entité sont disponibles.
     *
     * @return bool
     */
    protected function hasEntityData(): bool
    {
        $hasData = $this->entityData !== null;
        return $hasData;
    }
}