<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Référence le nom des sections de cache utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreCacheSection {

    /**
     * Section de configuration.
     *
     * @var string
     */
    const CONFIGS = "configs";

    /**
     * Section temporaire (branche principal).
     *
     * @var string
     */
    const TMP = "tmp";

    /**
     * Section temporaire des fichiers de journaux.
     *
     * @var string
     */
    const LOGGER = self::TMP . "/logger";

    /**
     * Section temporaire pour le cache des sessions.
     *
     * @var string
     */
    const SESSIONS = self::TMP . "/sessions";

    /**
     * Section temporaire pour le cache de traduction.
     *
     * @var string
     */
    const TRANSLATE = self::TMP . "/translate";

    /**
     * Section temporaire pour le cache des menus.
     *
     * @var string
     */
    const MENUS = self::TMP . "/menus";

    /**
     * Section temporaire pour le cache des modules.
     *
     * @var string
     */
    const MODULES = self::TMP . "/modules";

    /**
     * Section temporaire pour le cache des listes de fichiers.
     *
     * @var string
     */
    const FILELISTER = self::TMP . "/fileLister";

    /**
     * Section temporaire pour le cache des formulaires.
     *
     * @var string
     */
    const FORMS = self::TMP . "/forms";

    /**
     * Section temporaire pour le cache des blocks.
     *
     * @var string
     */
    const BLOCKS = self::TMP . "/blocks";

}