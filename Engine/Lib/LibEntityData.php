<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreLoader;
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
     * Les données compilées.
     *
     * @var string
     */
    private $buffer = "";

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
     * Retourne les données compilées.
     *
     * @return string
     */
    public function &getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Affecte les données compilées.
     *
     * @param string $buffer
     */
    public function setBuffer(string $buffer): void
    {
        $this->buffer = $buffer;
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
}