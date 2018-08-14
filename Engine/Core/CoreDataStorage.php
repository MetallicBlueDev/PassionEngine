<?php

namespace TREngine\Engine\Core;

use DateTime;

/**
 * Collecteur de données pour mise en mémoire tampon.
 *
 * @author Sébastien Villemain
 */
abstract class CoreDataStorage
{

    /**
     * Données en cache.
     *
     * @var array
     */
    private $data = array();

    /**
     * Nouveau modèle de données.
     */
    protected function __construct()
    {
        $this->data = null;
    }

    /**
     * Nettoyage à la destruction.
     */
    public function __destruct()
    {
        unset($this->data);
    }

    /**
     * Initialise les données.
     *
     * @param array $data
     */
    protected function newStorage(array &$data): void
    {
        $this->data = $data;
    }

    /**
     * Retourne toutes les information en mémoire.
     *
     * @return array
     */
    protected function &getStorage(): array
    {
        return $this->data;
    }

    /**
     * Détermine si instance a été réalisée.
     *
     * @return bool
     */
    protected function initialized(): bool
    {
        return $this->data !== null;
    }

    /**
     * Retourne la valeur de la clé sous forme de tableau.
     *
     * @param string $keyName
     * @param array $defaultValue
     * @param bool $testIfEmpty
     * @return array
     */
    protected function &getArray(string $keyName,
                                 array $defaultValue = null,
                                 bool $testIfEmpty = false): array
    {
        $value = (array) $this->getMixed($keyName,
                                         $defaultValue);

        if ($testIfEmpty && empty($value)) {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * Retourne la valeur de la clé de configuration du module.
     *
     * @param string $key
     * @param string $subKey
     * @param string $defaultValue
     * @return string
     */
    public function &getSubString(string $key,
                                  string $subKey,
                                  string $defaultValue = ""): string
    {
        $value = $this->getArray($key);

        if ($value !== null && isset($value[$subKey])) {
            $value = $value[$subKey];
        }

        if (empty($value)) {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * Retourne la valeur de la clé sous forme booléenne.
     *
     * @param string $keyName
     * @param bool $defaultValue
     * @return bool
     */
    protected function &getBool(string $keyName,
                                bool $defaultValue = false): bool
    {
        $value = (bool) $this->getMixed($keyName,
                                        $defaultValue);
        return $value;
    }

    /**
     * Retourne la valeur de la clé sous forme de chaine de caractères.
     *
     * @param string $keyName
     * @param string $defaultValue
     * @return string
     */
    protected function &getString(string $keyName,
                                  string $defaultValue = null): string
    {
        $value = (string) $this->getMixed($keyName,
                                          $defaultValue);
        return $value;
    }

    /**
     * Retourne la valeur de la clé sous forme d'entier.
     *
     * @param string $keyName
     * @param int $defaultValue
     * @return int
     */
    protected function &getInt(string $keyName,
                               int $defaultValue = null): int
    {
        $value = (int) $this->getMixed($keyName,
                                       $defaultValue);
        return $value;
    }

    /**
     * Retourne la valeur de la clé sous forme de date/heure.
     *
     * @param string $keyName
     * @param DateTime $defaultValue
     * @return DateTime
     */
    protected function &getDatetime(string $keyName,
                                    ?DateTime $defaultValue = null): ?DateTime
    {
        $value = $this->getMixed($keyName,
                                 $defaultValue);
        return $value;
    }

    /**
     * Détermine si la clé existe.
     *
     * @param string $keyName
     * @return bool
     */
    protected function exist(string $keyName): bool
    {
        return isset($this->data[$keyName]);
    }

    /**
     * Détermine si une valeur a été affectée.
     *
     * @param string $keyName
     * @return bool
     */
    protected function hasValue(string $keyName): bool
    {
        return $this->exist($keyName) && !empty($this->data[$keyName]);
    }

    /**
     * Change la valeur de clé.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function setDataValue(string $keyName,
                                    $value): void
    {
        $this->data[$keyName] = $value;
    }

    /**
     * Change la valeur de clé uniquement si elle existe déjà.
     *
     * @param string $keyName
     * @param mixed $value
     */
    protected function updateDataValue(string $keyName,
                                       $value): void
    {
        if ($this->exist($keyName)) {
            $this->setDataValue($keyName,
                                $value);
        }
    }

    /**
     * Nettoyage de la variable.
     *
     * @param string $keyName
     */
    protected function unsetValue(string $keyName): void
    {
        if ($this->exist($keyName)) {
            unset($this->data[$keyName]);
        }
    }

    /**
     * Retourne la valeur de la clé.
     *
     * @return mixed
     */
    private function &getMixed($keyName,
                               $defaultValue = null)
    {
        $value = null;

        if ($this->exist($keyName)) {
            $value = $this->data[$keyName];
        } else {
            $value = $defaultValue;
        }
        return $value;
    }
}