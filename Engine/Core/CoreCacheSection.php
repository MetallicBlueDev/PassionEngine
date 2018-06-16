<?php

namespace TREngine\Engine\Core;

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
    const SECTION_CONFIGS = "configs";

    /**
     * Section temporaire (branche principal).
     *
     * @var string
     */
    const SECTION_TMP = "tmp";

    /**
     * Section temporaire des fichiers de journaux.
     *
     * @var string
     */
    const SECTION_LOGGER = self::SECTION_TMP . "/logger";

    /**
     * Section temporaire pour le cache des sessions.
     *
     * @var string
     */
    const SECTION_SESSIONS = self::SECTION_TMP . "/sessions";

    /**
     * Section temporaire pour le cache de traduction.
     *
     * @var string
     */
    const SECTION_TRANSLATE = self::SECTION_TMP . "/translate";

    /**
     * Section temporaire pour le cache des menus.
     *
     * @var string
     */
    const SECTION_MENUS = self::SECTION_TMP . "/menus";

    /**
     * Section temporaire pour le cache des modules.
     *
     * @var string
     */
    const SECTION_MODULES = self::SECTION_TMP . "/modules";

    /**
     * Section temporaire pour le cache des listes de fichiers.
     *
     * @var string
     */
    const SECTION_FILELISTER = self::SECTION_TMP . "/fileLister";

    /**
     * Section temporaire pour le cache des formulaires.
     *
     * @var string
     */
    const SECTION_FORMS = self::SECTION_TMP . "/forms";

    /**
     * Section temporaire pour le cache des blocks.
     *
     * @var string
     */
    const SECTION_BLOCKS = self::SECTION_TMP . "/blocks";

}