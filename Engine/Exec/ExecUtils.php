<?php

namespace TREngine\Engine\Exec;

/**
 * Utilitaire.
 *
 * @author Sébastien Villemain
 */
class ExecUtils
{

    /**
     * Le timestamp UNIX actuellemnt mémorisé.
     * Evite d'appeler plusieurs fois la fonction <code>timer()</code>.
     *
     * @var int
     */
    private static $memorizedTimestamp = 0;

    /**
     * Indique si la valeur appartient à au tableau.
     * La comparaison est fiable, cependant ;
     * - Les types doivent être identiques
     * - La comparaison est sensible à la case
     *
     * @param mixed $needle La valeur recherchée.
     * @param array $haystack Tableau du même type dans lequel il faut rechercher.
     * @return bool
     */
    public static function &inArrayStrictCaseSensitive($needle,
                                                       array $haystack): bool
    {
        $rslt = in_array($needle,
                         $haystack,
                         true);
        return $rslt;
    }

    /**
     * Indique si la valeur appartient à au tableau.
     * La comparaison est fiable, cependant ;
     * - Les types doivent être identiques (string)
     *
     * @param string $needle La valeur recherchée.
     * @param array $haystack Tableau de string dans lequel il faut rechercher.
     * @return bool
     */
    public static function &inArrayStrictCaseInSensitive(string $needle,
                                                         array $haystack): bool
    {
        $needle = strtolower($needle);
        $haystack = array_map('strtolower',
                              $haystack);
        $rslt = in_array($needle,
                         $haystack,
                         true);
        return $rslt;
    }

    /**
     * Retourne le jeu de configuration.
     *
     * @param array $configs
     * @return array
     */
    public static function &getArrayConfigs(array $configs): array
    {
        $newConfigs = array();
        foreach ($configs as $row) {
            $newConfigs[$row['name']] = ExecString::stripSlashes($row['value']);
        }
        return $newConfigs;
    }

    /**
     * Retourne le timestamp UNIX actuellement mémorisé.
     *
     * @return int
     */
    public static function getMemorizedTimestamp(): int
    {
        if (self::$memorizedTimestamp < 1) {
            self::$memorizedTimestamp = time();
        }
        return self::$memorizedTimestamp;
    }
}