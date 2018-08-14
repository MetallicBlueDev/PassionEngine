<?php

namespace TREngine\Engine\Exec;

/**
 * Fonction optimisée et utilitaire.
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
     * Indique si une valeur appartient à un tableau.
     * in_array() optimized function.
     *
     * @param string $needle
     * @param array $haystack
     * @param bool $strict
     * @return bool
     */
    public static function &inArray(string $needle,
                                    array $haystack,
                                    bool $strict = false): bool
    {
        $rslt = false;

        foreach ($haystack as $value) {
            if ((!$strict && $needle == $value) || ($strict && $needle === $value)) {
                $rslt = true;
                break;
            }
        }
        return $rslt;
    }

    /**
     * Indique si une valeur appartient à un tableau (tableau multiple).
     * Tableau à dimension multiple.
     * in_array() multi array function.
     *
     * @param string $needle
     * @param array $haystack
     * @param bool $strict
     * @return bool
     */
    public static function &inMultiArray(string $needle,
                                         array $haystack,
                                         bool $strict = false): bool
    {
        $rslt = false;

        foreach ($haystack as $value) {
            if (is_array($value)) {
                if (self::inMultiArray($needle,
                                       $value)) {
                    $rslt = true;
                    break;
                }
            } else {
                if ((!$strict && $needle == $value) || ($strict && $needle === $value)) {
                    $rslt = true;
                    break;
                }
            }
        }
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