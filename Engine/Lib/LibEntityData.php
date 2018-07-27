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
}