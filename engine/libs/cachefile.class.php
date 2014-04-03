<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

Core_Loader::classLoader("Libs_CacheModel");

/**
 * Gestionnaire de fichier.
 *
 * @author Sébastien Villemain
 */
class Libs_CacheFile extends Libs_CacheModel {

    /**
     * Ecriture du ficher cache.
     *
     * @param string $path chemin vers le fichier cache
     * @param string $content contenu du fichier cache
     * @param boolean $overWrite écrasement du fichier
     */
    public function writingCache($path, $content, $overWrite = true) {
        if (!is_file(TR_ENGINE_DIR . "/" . $path)) {
            // Soit le fichier n'exite pas soit tout le dossier n'existe pas
            // On commence par vérifier et si besoin écrire le dossier
            $this->writingDirectory($path);
        }

        // Réécriture rapide sur un fichier
        $this->writingFile($path, $content, $overWrite);
    }

    /**
     * Supprime un fichier ou supprime tout fichier trop vieux.
     * Suprime aussi un dossier.
     *
     * @param string $dir chemin vers le fichier ou le dossier
     * @param int $timeLimit limite de temps
     */
    public function removeCache($dir = "", $timeLimit = 0) {
        if (!empty($dir) && is_file(TR_ENGINE_DIR . "/" . $dir)) {
            // C'est un fichier a supprimer
            $this->removeFile($dir, $timeLimit);
        } else if (is_dir(TR_ENGINE_DIR . "/" . $dir)) {
            // C'est un dossier a nettoyer
            $this->removeDirectory($dir, $timeLimit);
        }
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache($path, $updateTime = 0) {
        if ($updateTime < 1) {
            $updateTime = time();
        }

        if (!touch(TR_ENGINE_DIR . "/" . $path, $updateTime)) {
            Core_Logger::addException("touch error on " . $path);
        }
    }

    /**
     * Retourne le listing avec uniquement les fichiers et dossiers présent.
     *
     * @param string $dirPath
     * @return array
     */
    public function &listNames($dirPath = "") {
        $dirList = array();

        // Si le dossier est vide, on prend le dossier par défaut
        $dirPath = !empty($dirPath) ? TR_ENGINE_DIR . "/" . $dirPath : TR_ENGINE_DIR;

        // Ouverture du dossier
        $handle = opendir($dirPath);

        // Boucle sur les fichiers
        do {
            $file = readdir($handle);

            if (!empty($file)) {
                // Si c'est un fichier valide
                if ($file != ".." && $file != "." && $file != "index.html" && $file != "index.htm" && $file != "index.php" && $file != ".htaccess" && $file != ".svn" && $file != "checker.txt") {
                    $dirList[] = $file;
                }
            }
        } while (false !== $file);

        // Fermeture du dossier
        closedir($handle);

        // Rangement et mise à zéro du tableau
        sort($dirList);
        reset($dirList);
        return $dirList;
    }

    /**
     * Etat du gestionnaire.
     *
     * @return boolean
     */
    public function canUse() {
        return true;
    }

    /**
     * Ecriture du fichier cache.
     *
     * @param string $pathFile chemin vers le fichier cache
     * @param string $content contenu du fichier cache
     * @param boolean $overWrite écrasement du fichier
     */
    private function writingFile($pathFile, $content, $overWrite = true) {
        $content = ($overWrite) ? Core_CacheBuffer::getHeader($pathFile, $content) : $content;

        // Tentative d'écriture du fichier
        // Des problèmes on été constaté avec l'utilisation du chemin absolu TR_ENGINE_DIR
        $fp = fopen($pathFile, 'a');

        if ($fp) {
            // Verrouiller le fichier destination
            flock($fp, LOCK_EX);

            if ($overWrite) {
                // Tronque pour une réécriture complete
                ftruncate($fp, 0);
            }

            // Ecriture du fichier cache
            $nbBytesFile = strlen($content);
            $nbBytesCmd = fwrite($fp, $content, $nbBytesFile);

            // Vérification des bytes écris
            if ($nbBytesCmd != $nbBytesFile) {
                @unlink(TR_ENGINE_DIR . "/" . $pathFile);

                Core_Logger::addException("bad response for fwrite command. Path : " . $pathFile . ". "
                . "Server response : " . $nbBytesCmd . " bytes writed, " . $nbBytesFile . " bytes readed");
            }

            // Libere le verrou
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            // Recherche d'un fichier htaccess
            $strlen = strlen($pathFile);
            $isHtaccessFile = (substr($pathFile, -9, $strlen) == ".htaccess");

            // Si c'est un htaccess, on essai de corriger le problème
            if ($isHtaccessFile) {
                // On créée le même fichier en HTML
                $htaccessPath = substr($pathFile, 0, $strlen - 9);
                $this->writingFile($htaccessPath . "index.html", $content, $overWrite);

                // Puis on renomme
                rename($htaccessPath . "index.html", $htaccessPath . ".htaccess");
            }

            Core_Logger::addException("bad response for fopen command. Path : " . $pathFile);
        }
    }

    /**
     * Ecriture des dossiers, reconstitution des dossiers.
     *
     * @param string $path chemin voulu
     */
    private function writingDirectory($path) {
        // Savoir si le path est un dossier ou un fichier
        $pathIsDir = Core_CacheBuffer::isDir($path);

        // Information sur les dossiers
        $dirs = explode("/", TR_ENGINE_DIR . "/" . $path);
        $nbDir = count($dirs);
        $currentPath = "";
        $count = 0;

        if ($nbDir > 0) {
            foreach ($dirs as $dir) {
                $count++;

                // Si le dernier élèment est un fichier ou simplement vide
                if (($count == $nbDir && !$pathIsDir) || empty($dir)) {
                    // Il vaut mieux continuer, plutot que de faire un arret avec break
                    continue; // on passe a la suite...
                }

                // Mise à jour du dossier courant
                $currentPath = ($count == 1) ? $dir : $currentPath . "/" . $dir;

                if (!is_dir($currentPath)) {
                    // Création du dossier
                    mkdir($currentPath, $this->chmod);
                    chmod($currentPath, $this->chmod);

                    // Vérification de l'existence du fichier
                    if (!is_dir($currentPath)) {
                        Core_Logger::addException("bad response for mkdir|chmod command. Path : " . $currentPath);
                    }

                    // Des petites fichiers bonus...
                    if ($dir == "tmp") {
                        $this->writingFile($currentPath . "/index.php", "header(\"Location: ../index.php\");");
                    } else {
                        $this->writingFile($currentPath . "/.htaccess", "deny from all");
                    }
                }
            }
        }
    }

    /**
     * Supprime le fichier cache.
     *
     * @param string $path
     * @param int $timeLimit
     */
    private function removeFile($path, $timeLimit) {
        // Vérification de la date d'expiration
        $deleteFile = false;

        // Vérification de la date
        if ($timeLimit > 0) {
            // Vérification de la date d'expiration
            if ($timeLimit > filemtime(TR_ENGINE_DIR . "/" . $path)) {
                // Fichier périmé, suppression
                $deleteFile = true;
            }
        } else {
            // Suppression du fichier directement
            $deleteFile = true;
        }

        if ($deleteFile) {
            $fp = @fopen(TR_ENGINE_DIR . "/" . $path, 'a');

            if ($fp) {
                // Verrouiller le fichier destination
                flock($fp, LOCK_EX);

                // Libere le verrou
                flock($fp, LOCK_UN);
                fclose($fp);

                // Suppression
                unlink(TR_ENGINE_DIR . "/" . $path);
            }

            if (is_file(TR_ENGINE_DIR . "/" . $path)) {
                Core_Logger::addException("bad response for fopen|unlink command. Path : " . $path);
            }
        }
    }

    /**
     * Supprime le dossier.
     *
     * @param string $dirPath
     * @param int $timeLimit
     */
    private function removeDirectory($dirPath, $timeLimit) {
        // Ouverture du dossier
        $handle = opendir(TR_ENGINE_DIR . "/" . $dirPath);

        // Boucle sur les fichiers
        do {
            $file = readdir($handle);

            if (!empty($file)) {
                // Si c'est un fichier valide
                if ($file != ".." && $file != "." && $file != ".svn") {
                    // Vérification avant suppression
                    if ($timeLimit > 0) {
                        if (is_file($dirPath . "/" . $file)) {
                            // Si le fichier n'est pas périmé, on passe au suivant
                            if ($timeLimit < filemtime(TR_ENGINE_DIR . "/" . $dirPath . "/" . $file)) {
                                continue;
                            }
                        } else {
                            // C'est un dossier,
                            // on ne souhaite pas le supprimer dans ce mode de fonctionnement
                            continue;
                        }
                    }

                    // Suppression
                    if (is_file(TR_ENGINE_DIR . "/" . $dirPath . "/" . $file)) {
                        // Suppression du fichier
                        $this->removeFile($dirPath . "/" . $file, $timeLimit);
                    } else {
                        // Suppression du dossier
                        $this->removeDirectory($dirPath . "/" . $file, 0);
                    }
                }
            }
        } while ($file !== false);

        // Fermeture du dossier
        closedir($handle);

        // Suppression du dernière dossier
        if ($timeLimit == 0) {
            rmdir(TR_ENGINE_DIR . "/" . $dirPath);

            if (is_dir(TR_ENGINE_DIR . "/" . $dirPath)) {
                Core_Logger::addException("bad response for rmdir command. Path : " . $dirPath);
            }
        }
    }

}