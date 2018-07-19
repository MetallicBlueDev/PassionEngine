<?php

namespace TREngine\Engine\Core;

/**
 * Gestionnaire de réécriture du tampon de sortie.
 *
 * @author Sébastien Villemain
 */
class CoreUrlRewriting
{

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
    private function __construct()
    {
        if (CoreMain::getInstance()->getConfigs()->doUrlRewriting()) {
            $this->canUse = $this->testPassed();
        }
    }

    /**
     * Instance du gestionnaire de réécriture du tampon de sortie.
     *
     * @return CoreUrlRewriting
     */
    public static function &getInstance(): CoreUrlRewriting
    {
        if (self::$coreUrlRewriting === null) {
            self::$coreUrlRewriting = new CoreUrlRewriting();
        }
        return self::$coreUrlRewriting;
    }

    public function &rewriteLink(string $link): string
    {
        if ($this->canUse) {

        }
        return $link;
    }

    public function &rewriteBuffer(string $buffer): string
    {
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
    public static function &getLink(string $link,
                                    bool $layout = false): string
    {
        if ($link[0] !== "#") {
            // Configuration du layout
            if ($layout) {
                $layout = "&amp;" . CoreLayout::REQUEST_LAYOUT . "=";

                if (strpos($link,
                           CoreLayout::REQUEST_BLOCKID . "=") !== false || strpos($link,
                                                                                  CoreLayout::REQUEST_BLOCKTYPE . "=") !== false) {
                    $layout .= CoreLayout::BLOCK;
                } else if (strpos($link,
                                  CoreLayout::REQUEST_MODULE . "=") !== false) {
                    $layout .= CoreLayout::MODULE;
                } else {
                    $layout .= CoreLayout::DEFAULT_LAYOUT;
                }

                $link .= $layout;
            }

            // Recherche de la page principal
            if (strpos($link,
                       "index.php") === false) {
                $link = "index.php" . ($link[0] !== "?" ? "?" : "") . $link;
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
    private function &testPassed(): bool
    {
        $rslt = false;
        // TODO vérifie si fichier tmp de test est OK
        // si pas OK et pas de fichier tmp pour signaler la désactivation
        // on tente de mettre urlRewriting a 0 puis on créé le fichier tmp de désactivation
        // si déjà fichier tmp de décastivation ajouter erreur dans CoreLogger
        return $rslt;
    }
}