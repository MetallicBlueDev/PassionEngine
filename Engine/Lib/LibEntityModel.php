<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccessType;

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
     */
    abstract public function display(): void;

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
     * Retourne l'accès spécifique de ce module.
     *
     * @return CoreAccessType
     */
    public function &getAccessType(): CoreAccessType
    {
        return CoreAccessType::getTypeFromToken($this->getEntityData());
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
        return $this->entityData !== null;
    }
}