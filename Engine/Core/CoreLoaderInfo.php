<?php

namespace TREngine\Engine\Core;

/**
 * Information sur une classe à charger.
 *
 * @author Sébastien Villemain
 */
class CoreLoaderInfo
{

    /**
     * Clé représentant le fichier (chemin de dossier, nom de classe, clé pour inclure un fichier, etc.).
     *
     * @var string
     */
    public $keyName = '';

    /**
     * Type de fichier.
     *
     * @var string
     */
    public $fileType = '';

    /**
     * Préfixe de la classe si c'est un nom court qui nécessite plus de précision.
     *
     * @var string
     */
    public $prefixName = '';

    /**
     * Chemin absolu vers le fichier.
     *
     * @var string
     */
    public $path = '';

    /**
     * Représente la clé unique utilisé pour référencer le fichier dans le cache.
     *
     * @var string
     */
    public $uniqueFileKey = '';

    /**
     * Nouvelle information sur le fichier à charger.
     *
     * @param string $keyName Clé représentant le fichier.
     */
    public function __construct(string &$keyName)
    {
        $this->keyName = $keyName;
    }
}