<?php

namespace TREngine\Engine\Exec;

/**
 * Outil de mesure de durée.
 *
 * @author Sébastien Villemain
 */
class ExecTimeMarker
{

    /**
     * Marqueur de début.
     *
     * @var array
     */
    private static $startTime = array();

    /**
     * Marqueur de fin.
     *
     * @var array
     */
    private static $finishTime = array();

    /**
     * Marque le temps de début de génération.
     *
     * @param string $name Nom de la mesure.
     */
    public static function startMeasurement(string $name): void
    {
        self::$startTime[$name] = self::getMaker();
    }

    /**
     * Marque le temps de fin de génération.
     *
     * @param string $name Nom de la mesure.
     */
    public static function stopMeasurement(string $name): void
    {
        self::$finishTime[$name] = self::getMaker();
    }

    /**
     * Retourne le temps courant en micro seconde.
     *
     * @return float
     */
    private static function getMaker(): float
    {
        return microtime(true);
    }

    /**
     * Retourne la durée mesurée en milliseconde.
     *
     * @return float le temps de génération en milliseconde.
     */
    public static function &getMeasurement(string $name): float
    {
        $rslt = 0;

        if (isset(self::$startTime[$name])) {
            if (!isset(self::$finishTime[$name])) {
                self::stopMeasurement($name);
            }

            $rslt = self::$finishTime[$name] - self::$startTime[$name];
            $rslt = round($rslt,
                          4) * 1000;
        }
        return $rslt;
    }
}