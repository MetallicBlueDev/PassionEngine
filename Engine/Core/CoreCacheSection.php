<?php

namespace TREngine\Engine\Core;

/**
 * Référence le nom des sections de cache utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreCacheSection
{

    /**
     * Section de configuration.
     *
     * @var string
     */
    public const CONFIGS = "configs";

    /**
     * Section temporaire (branche principal).
     *
     * @var string
     */
    public const TMP = "tmp";

    /**
     * Section temporaire des fichiers de journaux.
     *
     * @var string
     */
    public const LOGGER = self::TMP . "/logger";

    /**
     * Section temporaire pour le cache des sessions.
     *
     * @var string
     */
    public const SESSIONS = self::TMP . "/sessions";

    /**
     * Section temporaire pour le cache de traduction.
     *
     * @var string
     */
    public const TRANSLATE = self::TMP . "/translate";

    /**
     * Section temporaire pour le cache des menus.
     *
     * @var string
     */
    public const MENUS = self::TMP . "/menus";

    /**
     * Section temporaire pour le cache des modules.
     *
     * @var string
     */
    public const MODULES = self::TMP . "/modules";

    /**
     * Section temporaire pour le cache des listes de fichiers.
     *
     * @var string
     */
    public const FILELISTER = self::TMP . "/fileLister";

    /**
     * Section temporaire pour le cache des formulaires.
     *
     * @var string
     */
    public const FORMS = self::TMP . "/forms";

    /**
     * Section temporaire pour le cache des blocks.
     *
     * @var string
     */
    public const BLOCKS = self::TMP . "/blocks";

}