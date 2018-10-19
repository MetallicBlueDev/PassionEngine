<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Fail\FailCache;
use TREngine\Engine\Cache\CacheModel;
use TREngine\Engine\Exec\ExecString;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire de fichier cache.
 *
 * @author Sébastien Villemain
 */
class CoreCache extends CoreDriverSelector
{

    /**
     * Utilisation des méthodes PHP.
     *
     * @var string
     */
    const MODE_PHP = 'php';

    /**
     * Utilisation des transactions FTP.
     *
     * @var string
     */
    const MODE_FTP = 'ftp';

    /**
     * Utilisation des transactions FTP sécurisées.
     *
     * @var string
     */
    const MODE_SFTP = 'sftp';

    /**
     * @var string
     */
    private const CHECKER_FILENAME = 'checker.txt';

    /**
     * Gestionnaire de cache.
     *
     * @var CoreCache
     */
    private static $coreCache = null;

    /**
     * Chemin de la section actuelle.
     *
     * @var string
     */
    private $currentSection = CoreCacheSection::TMP;

    /**
     * Réécriture du complète du cache.
     *
     * @var array
     */
    private $overwriteCache = array();

    /**
     * Cache d'écriture.
     *
     * @var array
     */
    private $writeCache = array();

    /**
     * Cache de suppression.
     *
     * @var array
     */
    private $removeCache = array();

    /**
     * Cache de mise à jour de la date de modification.
     *
     * @var array
     */
    private $touchCache = array();

    /**
     * Nouveau gestionnaire de cache.
     *
     * @throws FailCache
     */
    public function __construct()
    {
        $this->createDriver();
    }

    /**
     * Destruction du gestionnaire.
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * Retourne l'instance du gestionnaire de cache.
     *
     * @param string $newSectionPath
     * @return CoreCache
     */
    public static function &getInstance(string $newSectionPath = null): CoreCache
    {
        self::checkInstance();

        if ($newSectionPath !== null) {
            self::$coreCache->changeCurrentSection($newSectionPath);
        }
        return self::$coreCache;
    }

    /**
     * Vérification de l'instance du gestionnaire de cache.
     */
    public static function checkInstance(): void
    {
        if (self::$coreCache === null) {
            self::$coreCache = new CoreCache();
        }
    }

    /**
     * Retourne l'instance du pilote de fichiers en cache.
     *
     * @return CacheModel
     */
    public function &getSelectedCache(): CacheModel
    {
        return $this->getSelectedDriver();
    }

    /**
     * Retourne la liste des types de cache supporté.
     *
     * @return array
     */
    public static function &getCacheList(): array
    {
        return self::getInstance()->getFileList(CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . CoreLoader::CACHE_FILE,
                                                CoreLoader::CACHE_FILE);
    }

    /**
     * Demande une écriture dans le cache d'une chaîne de caractères.
     * Prépare les données pour la soumission dans le cache et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param mixed $content
     * @param bool $overwrite
     * @return string
     */
    public function &writeCacheAsString(string $path,
                                        $content,
                                        bool $overwrite = true): string
    {
        if (is_array($content)) {
            $content = $this->serializeData($content);
        }

        $relativeSectionPath = $this->getRelativeSectionPath($path);

        // Ajout dans le cache
        if ($overwrite) {
            $this->overwriteCache[$relativeSectionPath] = $content;
        } else {
            $this->writeCache[$relativeSectionPath] = $content;
        }

        $variableName = $this->getVariableName();

        // Supprime la déclaration de la variable $tmp = 'myData'; = > myData
        $pos = strpos($content,
                      '$' . $variableName . ' = \'');

        if ($pos !== false && $pos === 0) {
            $content = str_replace('$' . $variableName . ' = \'',
                                   '',
                                   $content);
            $pos = strrpos($content,
                           '\';');

            if ($pos !== false && $pos > 0) {
                $content = substr($content,
                                  0,
                                  $pos);
            }
        }
        return $content;
    }

    /**
     * Demande une écriture dans le cache d'une chaîne contenant des variables à remplacer.
     * Prépare les données pour la soumission dans le cache en tenant compte des variables et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param string $content
     * @param bool $overwrite
     * @param string $cacheVariableName
     * @param array $cacheVariables
     * @return string
     */
    public function &writeCacheWithVariable(string $path,
                                            string $content,
                                            bool $overwrite = true,
                                            string $cacheVariableName = '',
                                            array $cacheVariables = array()): string
    {
        $content = $this->writeCacheAsString($path,
                                             $content,
                                             $overwrite);

        if (!empty($cacheVariableName)) {
            $matches = array();

            // Recherche la variable à remplacer
            if (preg_match_all('/[$]' . $cacheVariableName . '([[]([A-Za-z0-9]+)[]]|)/',
                               $content,
                               $matches,
                               PREG_PATTERN_ORDER) !== false) {
                $content = $this->replaceVariable($content,
                                                  $matches,
                                                  $cacheVariableName,
                                                  $cacheVariables);
            }
        }
        return $content;
    }

    /**
     * Demande d'écriture dans le cache un objet.
     * Prépare l'objet pour la soumission dans le cache et retourne les données telles qu’elles seront écrites.
     *
     * @param string $path
     * @param mixed $content
     * @return string
     */
    public function &writeCacheAsStringSerialize(string $path,
                                                 $content): string
    {
        $content = serialize($content);
        // Préserve les données (protection utilisateur et protection des quotes)
        $content = base64_encode($content);
        $content = $this->serializeData($content);
        return $this->writeCacheAsString($path,
                                         $content);
    }

    /**
     * Lecture et exécution du cache.
     *
     * @param string $path Chemin du cache.
     * @param string $dynamicVariableName Nom de la variable qui sera utilisé directment dans le cache.
     * @param array $dynamicVariableValue Pointeur vers la variable qui sera utilisé directment dans le cache.
     */
    public function readCache(string $path,
                              string $dynamicVariableName = '',
                              array $dynamicVariableValue = []): void
    {
        $this->readCacheAsString($path,
                                 $dynamicVariableName,
                                 $dynamicVariableValue);
    }

    /**
     * Lecture du cache puis retourne une chaîne de caractères.
     *
     * @param string $path Chemin du cache.
     * @param string $dynamicVariableName Nom de la variable qui sera utilisé directment dans le cache.
     * @param array $dynamicVariableValue Pointeur vers la variable qui sera utilisé directment dans le cache.
     * @return string
     */
    public function &readCacheAsString(string $path,
                                       string $dynamicVariableName = '',
                                       array $dynamicVariableValue = []): string
    {
        $cacheData = $this->readCacheAsMixed($path,
                                             '',
                                             $dynamicVariableName,
                                             $dynamicVariableValue);
        if (!is_string($cacheData)) {
            $cacheData = '';
        }
        return $cacheData;
    }

    /**
     * Lecture du cache puis retourne un tableau.
     *
     * @param string $path Chemin du cache.
     * @param string $dynamicVariableName Nom de la variable qui sera utilisé directment dans le cache.
     * @param array $dynamicVariableValue Pointeur vers la variable qui sera utilisé directment dans le cache.
     * @return array
     */
    public function &readCacheAsArray(string $path,
                                      string $dynamicVariableName = '',
                                      array $dynamicVariableValue = []): array
    {
        $cacheData = $this->readCacheAsMixed($path,
                                             array(),
                                             $dynamicVariableName,
                                             $dynamicVariableValue);
        if (!is_array($cacheData)) {
            $cacheData = array();
        }
        return $cacheData;
    }

    /**
     * Lecture du cache puis retourne un tableau contenant des objets.
     *
     * @param string $path
     * @return array
     */
    public function &readCacheAsArrayUnserialized(string $path): array
    {
        $content = $this->readCacheAsArray($path);
        $content = base64_decode($content);
        $content = unserialize($content);
        return $content;
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache(string $path,
                               int $updateTime = 0): void
    {
        if ($updateTime <= 0) {
            $updateTime = ExecUtils::getMemorizedTimestamp();
        }

        $this->touchCache[$this->getRelativeSectionPath($path)] = $updateTime;
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $path chemin vers le fichier ou le dossier
     * @param int $timeLimit limite de temps
     */
    public function removeCache(string $path,
                                int $timeLimit = 0): void
    {
        $this->removeCache[$this->getRelativeSectionPath($path)] = $timeLimit;
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     * Un filtre automatique est appliqué sur les éléments tel que '..', '.' ou encore 'index.html'...
     *
     * @param string $path
     * @return array
     */
    public function &getNameList(string $path): array
    {
        $dirList = array();

        $this->changeCurrentSection(CoreCacheSection::FILELISTER);
        $fileName = str_replace(DIRECTORY_SEPARATOR,
                                '_',
                                $path) . '.php';

        if ($this->cached($fileName)) {
            $dirList = $this->readCacheAsArray($fileName);
        } else {
            $dirList = $this->getSelectedCache()->getNameList($path);
            $this->writeCacheAsString($fileName,
                                      $dirList);
        }
        return $dirList;
    }

    /**
     * Retourne la liste des fichiers trouvés avec l'extension demandé.
     *
     * @param string $dirPath
     * @param string $extension
     * @return array
     */
    public function &getFileList(string $dirPath,
                                 string $extension = '.class'): array
    {
        $names = array();
        $files = $this->getNameList($dirPath);

        foreach ($files as $fileName) {
            $pos = strpos($fileName,
                          $extension);

            if ($pos !== false && $pos > 0) {
                $names[] = substr($fileName,
                                  0,
                                  $pos);
            }
        }
        return $names;
    }

    /**
     * Change le chemin de la section.
     *
     * @param string $newSectionPath
     */
    public function changeCurrentSection(string $newSectionPath = CoreCacheSection::TMP): void
    {
        $newSectionPath = empty($newSectionPath) ? CoreCacheSection::TMP : $newSectionPath;
        $newSectionPath = str_replace('/',
                                      DIRECTORY_SEPARATOR,
                                      $newSectionPath);

        if (substr($newSectionPath,
                   -1) === DIRECTORY_SEPARATOR) {
            $newSectionPath = substr($newSectionPath,
                                     0,
                                     -1);
        }

        if ($this->currentSection !== $newSectionPath) {
            $this->currentSection = $newSectionPath;
        }
    }

    /**
     * Sérialise la variable en chaîne de caractères pour une mise en cache.
     *
     * @param mixed $data ($data = 'data') or array $data ($data = array('name' => 'data'))
     * @param string $lastKey clé supplémentaire
     * @return string
     */
    public function &serializeData($data,
                                   string $lastKey = ''): string
    {
        $content = '';

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $newKey = $lastKey . '[' . ExecString::addQuotesForNonNumeric($key,
                                                                              true) . ']';

                if (is_array($value)) {
                    $content .= $this->serializeData($value,
                                                     $newKey);
                } else {
                    $content .= $this->serializeVariable($newKey,
                                                         $value);
                }
            }
        } else {
            if (!empty($lastKey)) {
                $lastKey = '[' . ExecString::addQuotesForNonNumeric($lastKey,
                                                                    true) . ']';
            }

            $content .= $this->serializeVariable($lastKey,
                                                 $data);
        }
        return $content;
    }

    /**
     * Détermine si le fichier est en cache.
     *
     * @param string $path Chemin vers le fichier cache.
     * @return bool Le fichier est en cache.
     */
    public function cached(string $path): bool
    {
        $fullPath = $this->getFullPath($path);
        return is_file($fullPath);
    }

    /**
     * Retourne la date de dernière modification du fichier.
     *
     * @param string $path
     * @return int
     */
    public function &getCacheMTime(string $path): int
    {
        $relativeSectionPath = $this->getRelativeSectionPath($path);
        return $this->getSelectedCache()->getCacheMTime($relativeSectionPath);
    }

    /**
     * Parcours récursivement le dossier du cache actuel afin de supprimer les fichiers trop vieux.
     * (Nettoie le dossier courant du cache).
     *
     * @param int $timeLimit la limite de temps
     */
    public function cleanCache(int $timeLimit): void
    {
        $exist = $this->cached(self::CHECKER_FILENAME);
        $valid = false;

        if ($exist) {
            // Vérification de la validité du checker
            if ($timeLimit > 0) {
                if ($timeLimit < $this->getCacheMTime(self::CHECKER_FILENAME)) {
                    $valid = true;
                }
            } else {
                $valid = true;
            }
        }

        if (!$valid) {
            // Mise à jour ou creation du fichier checker
            if (!$exist) {
                $this->writeCacheAsString(self::CHECKER_FILENAME,
                                          '1');
            } else {
                $this->touchCache(self::CHECKER_FILENAME);
            }

            // Suppression du cache périmé
            $this->removeCache('',
                               $timeLimit);
        }
    }

    /**
     * Exécute la routine du cache.
     */
    public function runJobs(): void
    {
        if (!empty($this->removeCache)) {
            // Suppression de cache demandée
            foreach ($this->removeCache as $path => $timeLimit) {
                $this->getSelectedCache()->removeCache($path,
                                                       $timeLimit);
            }
        }

        if (!empty($this->writeCache)) {
            // Ecriture de cache demandée
            foreach ($this->writeCache as $path => $content) {
                $this->getSelectedCache()->writeCache($path,
                                                      $content,
                                                      false);
            }
        }

        if (!empty($this->overwriteCache)) {
            // Ecriture à la suite de cache demandée
            foreach ($this->overwriteCache as $path => $content) {
                $this->getSelectedCache()->writeCache($path,
                                                      $content,
                                                      true);
            }
        }

        if (!empty($this->touchCache)) {
            // Mise à jour de cache demandée
            foreach ($this->touchCache as $path => $updateTime) {
                $this->getSelectedCache()->touchCache($path,
                                                      $updateTime);
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    protected function getConfiguration(): array
    {
        return CoreMain::getInstance()->getConfigs()->getConfigCache();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function getFilePrefix(): string
    {
        return CoreLoader::CACHE_FILE;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @throws FailCache
     */
    protected function throwException(string $message,
                                      string $failCode = '',
                                      array $failArgs = array()): void
    {
        throw new FailCache($message,
                            $failCode,
                            $failArgs);
    }

    /**
     * Retourne les données telles qu’elles seront écrites.
     *
     * @param string $content
     * @param array $matches
     * @param string $cacheVariableName
     * @param array $cacheVariables
     * @return string
     */
    private function &replaceVariable(string $content,
                                      array $matches,
                                      string $cacheVariableName,
                                      array $cacheVariables): string
    {
        // Suppression des caractères d'échappements
        $content = str_replace("\\\"",
                               "\"",
                               $content);

        // Utilisation des variables en cache
        ${$cacheVariableName} = $cacheVariables;
        $hasEmpty = false;

        // Remplacement de toutes les variables
        foreach ($matches[2] as $value) {
            if ($value === '') {
                $hasEmpty = true;
                continue;
            }

            // Remplace la variable par sa valeur
            $content = str_replace('\$' . $cacheVariableName . '[' . $value . ']',
                                   ${$cacheVariableName}[$value],
                                   $content);
        }

        if ($hasEmpty) {
            $content = str_replace($cacheVariableName,
                                   ${$cacheVariableName},
                                   $content);
        }
        return $content;
    }

    /**
     * Retourne le chemin avec la section courante.
     *
     * @param string $path
     * @return string
     */
    private function &getRelativeSectionPath(string $path): string
    {
        $path = $this->currentSection . DIRECTORY_SEPARATOR . $path;
        return $path;
    }

    /**
     * Retourne le chemin complet avec la racine et la section courante.
     *
     * @param string $path
     * @return string
     */
    private function &getFullPath(string $path): string
    {
        $path = TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . $this->getRelativeSectionPath($path);
        return $path;
    }

    /**
     * Sérialise la variable en chaîne de caractères pour une mise en cache
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function &serializeVariable(string $key,
                                        $value): string
    {
        $content = '$' . $this->getVariableName($key) . ' = ' . ExecString::addQuotesForNonNumeric($value,
                                                                                                   false) . '; ';
        return $content;
    }

    /**
     * Retourne le nom de la variable de cache.
     *
     * @param string $key
     * @return string
     */
    private function &getVariableName(string $key = ''): string
    {
        if (empty($this->currentSection)) {
            $this->changeCurrentSection();
        }

        $variableName = str_replace(DIRECTORY_SEPARATOR,
                                    '_',
                                    $this->currentSection) . $key;
        return $variableName;
    }

    /**
     * Lecture du cache demandé puis retourne les données.
     *
     * @param string $path Chemin du cache.
     * @param mixed $initialValue Donnée d'initialisation.
     * @param string $dynamicVariableName Nom de la variable qui sera utilisé directement dans le cache.
     * @param array $dynamicVariableValue Pointeur vers la variable qui sera utilisé directement dans le cache.
     * @return mixed
     */
    private function &readCacheAsMixed(string $path,
                                       $initialValue,
                                       string $dynamicVariableName,
                                       array $dynamicVariableValue)
    {
        // Ajout des valeurs pour utilisation dans le cache
        if (!empty($dynamicVariableName)) {
            ${$dynamicVariableName} = &$dynamicVariableValue;
        }

        // Rend la variable global à la fonction pour retourner les données
        $variableName = $this->getVariableName();
        ${$variableName} = &$initialValue;

        // Capture du fichier
        if ($this->cached($path)) {
            require $this->getFullPath($path);
        }
        return ${$variableName};
    }
}