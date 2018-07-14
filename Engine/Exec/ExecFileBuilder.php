<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreSql;

/**
 * Outil de création des fichiers du moteur.
 *
 * @author Sébastien Villemain
 */
class ExecFileBuilder
{

    /**
     * Génére un nouveau fichier de configuration du moteur.
     *
     * @param string $email
     * @param string $statut
     * @param int $sessionTimeLimit
     * @param string $cookiePrefix
     * @param string $cryptKey
     */
    public static function buildConfigFile(string $email,
                                           string $statut,
                                           int $sessionTimeLimit,
                                           string $cookiePrefix,
                                           string $cryptKey): void
    {
        if (empty($email) && defined("TR_ENGINE_EMAIL")) {
            $email = TR_ENGINE_EMAIL;
        }

        $statut = ($statut === "close") ? "close" : "open";

        if (!is_int($sessionTimeLimit) || $sessionTimeLimit < 0) {
            $sessionTimeLimit = 7;
        }

        if (empty($cookiePrefix)) {
            $cookiePrefix = "tr";
        }

        if (empty($cryptKey)) {
            $cryptKey = ExecCrypt::makeIdentifier(8);
        }

        $content = "<?php \n"
            . "// -------------------------------------------------------------------------//\n"
            . "// Engine settings\n"
            . "//\n"
            . "// Webmaster email address\n"
            . "$" . "inc['TR_ENGINE_EMAIL'] = \"" . $email . "\";\n"
            . "//\n"
            . "// Status of the site (open | close)\n"
            . "$" . "inc['TR_ENGINE_STATUT'] = \"" . $statut . "\";\n"
            . "// -------------------------------------------------------------------------//\n"
            . "// Data sessions\n"
            . "//\n"
            . "// Duration in days of the validity of sessions files cached.\n"
            . "$" . "inc['sessionTimeLimit'] = " . $sessionTimeLimit . ";\n"
            . "//\n"
            . "// Cookies names prefix\n"
            . "$" . "inc['cookiePrefix'] = \"" . $cookiePrefix . "\";\n"
            . "//\n"
            . "// Unique decryption key (generated randomly during installation)\n"
            . "$" . "inc['cryptKey'] = \"" . $cryptKey . "\";\n"
            . "// -------------------------------------------------------------------------//\n"
            . "?>\n";

        CoreCache::getInstance(CoreCacheSection::CONFIGS)->writeCache("config.inc.php",
                                                                      $content);
    }

    /**
     * Génére un nouveau fichier de configuration du cache.
     *
     * @param string $type
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $root
     */
    public static function buildCacheFile(string $type,
                                          string $host,
                                          int $port,
                                          string $user,
                                          string $pass,
                                          string $root): void
    {
        $coreCache = CoreCache::getInstance(CoreCacheSection::CONFIGS);

        if (empty($type) || !ExecUtils::inArray($type,
                                                CoreCache::getCacheList())) {
            $type = $coreCache->getTransactionType();
        }

        if (empty($host)) {
            $host = "127.0.0.1";
        }

        if (!is_int($port)) {
            $port = 21;
        }

        if (empty($user)) {
            $user = "root";
        }

        $content = "<?php \n"
            . "// -------------------------------------------------------------------------//\n"
            . "// Cache settings\n"
            . "//\n"
            . "// Transaction type to use (php | ftp | sftp | socket)\n"
            . "$" . "inc['type'] = \"" . $type . "\";\n"
            . "//\n"
            . "// Host address\n"
            . "$" . "inc['host'] = \"" . $host . "\";\n"
            . "//\n"
            . "// Listening port number\n"
            . "$" . "inc['port'] = " . $port . ";\n"
            . "//\n"
            . "// Username FTP\n"
            . "$" . "inc['user'] = \"" . $user . "\";\n"
            . "//\n"
            . "// Password\n"
            . "$" . "inc['pass'] = \"" . $pass . "\";\n"
            . "//\n"
            . "// Root Path\n"
            . "$" . "inc['root'] = \"" . $root . "\";\n"
            . "// -------------------------------------------------------------------------//\n"
            . "?>\n";

        $coreCache->writeCache("cache.inc.php",
                               $content);
    }

    /**
     * Génére un nouveau fichier de configuration pour la base de données.
     *
     * @param string $type
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $name
     * @param string $prefix
     */
    public static function buildDatabaseFile(string $type,
                                             string $host,
                                             string $user,
                                             string $pass,
                                             string $name,
                                             string $prefix): void
    {
        if (empty($type) || !ExecUtils::inArray($type,
                                                CoreSql::getBaseList())) {
            $type = CoreSql::getInstance()->getTransactionType();
        }

        if (empty($host)) {
            $host = "mysql:host=127.0.0.1";
        }

        if (empty($user)) {
            $user = "root";
        }

        if (empty($prefix)) {
            $prefix = "tr";
        }

        $content = "<?php \n"
            . "// -------------------------------------------------------------------------//\n"
            . "// Database settings\n"
            . "//\n"
            . "// Transaction type to use (mysql / mysqli / pdo)\n"
            . "$" . "inc['type'] = \"" . $type . "\";\n"
            . "//\n"
            . "// Host address of the base or the complete chain of DSN (Data Source Name)\n"
            . "// Example for PDO DSN : mysql:host=127.0.0.1\n"
            . "$" . "inc['host'] = \"" . $host . "\";\n"
            . "//\n"
            . "// Username\n"
            . "$" . "inc['user'] = \"" . $user . "\";\n"
            . "//\n"
            . "// Password\n"
            . "$" . "inc['pass'] = \"" . $pass . "\";\n"
            . "//\n"
            . "// Database name\n"
            . "$" . "inc['name'] = \"" . $name . "\";\n"
            . "//\n"
            . "// Table prefix\n"
            . "$" . "inc['prefix'] = \"" . $prefix . "\";\n"
            . "// -------------------------------------------------------------------------//\n"
            . "?>\n";

        CoreCache::getInstance(CoreCacheSection::CONFIGS)->writeCache("database.inc.php",
                                                                      $content);
    }
}