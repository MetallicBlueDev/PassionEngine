<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de réécriture du tampon de sortie.
 *
 * @author Sébastien Villemain
 */
class CoreUrlRewriting {

    /**
     * Gestionnnaire de réécriture.
     *
     * @var CoreUrlRewriting
     */
    private static $coreUrlRewriting = null;

    /**
     * Vérifie si l'url rewriting a été activée.
     *
     * @var bool
     */
    private $canUse = false;

    /**
     * Nouveau gestionnaire.
     */
    private function __construct() {
        if (CoreMain::getInstance()->getConfigs()->doUrlRewriting()) {
            $this->canUse = $this->testPassed();
        }
    }

    /**
     * Instance du gestionnaire de réécriture du tampon de sortie.
     *
     * @return CoreUrlRewriting
     */
    public static function &getInstance(): CoreUrlRewriting {
        if (self::$coreUrlRewriting === null) {
            self::$coreUrlRewriting = new CoreUrlRewriting();
        }
        return self::$coreUrlRewriting;
    }

    public function &rewriteLink(string $link): string {
        if ($this->canUse) {

        }
        return $link;
    }

    public function &rewriteBuffer(string $buffer): string {
        if ($this->canUse) {

        }
        return $buffer;
    }

    /**
     * Obtention d'une adresse URL complète.
     *
     * @param string $link Adresse URL à réécrire.
     * @param bool $layout true ajouter le layout.
     * @return string
     */
    public static function &getLink(string $link, bool $layout = false): string {
        if ($link[0] !== "#") {
            // Configuration du layout
            if ($layout) {
                $layout = "&amp;layout=";

                if (strpos($link, "blockId=") !== false || strpos($link, "blockType=") !== false) {
                    $layout .= "block";
                } else if (strpos($link, "module=") !== false) {
                    $layout .= "module";
                } else {
                    $layout .= "default";
                }

                $link .= $layout;
            }

            // Recherche de la page principal
            if (strpos($link, "index.php") === false) {
                if ($link[0] === "?") {
                    $link = "index.php" . $link;
                } else {
                    $link = "index.php?" . $link;
                }
            }
        }
        // Finalise la réécriture du lien
        return self::getInstance()->rewriteLink($link);
    }

    /**
     * Vérifie si les tests ont été passés avec succès.
     *
     * @return bool
     */
    private function &testPassed(): bool {
        $rslt = false;
        // TODO vérifie si fichier tmp de test est OK
        // si pas OK et pas de fichier tmp pour signaler la désactivation
        // on tente de mettre urlRewriting a 0 puis on créé le fichier tmp de désactivation
        // si déjà fichier tmp de décastivation ajouter erreur dans CoreLogger
        return $rslt;
    }
}