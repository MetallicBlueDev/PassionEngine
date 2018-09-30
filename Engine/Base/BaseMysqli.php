<?php

namespace TREngine\Engine\Base;

use mysqli;
use mysqli_sql_exception;
use mysqli_result;
use mysqli_driver;
use TREngine\Engine\Core\CoreLogger;

/**
 * MySql Improve.
 *
 * Gestionnaire de transaction utilisant l'extension MySqli (nouvelle version de MySql).
 * Ne supporte que les bases de données MySQL 4.1 et supérieur.
 *
 * @author Sébastien Villemain
 */
class BaseMysqli extends BaseModel
{

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool
    {
        $rslt = class_exists("mysqli");

        if (!$rslt) {
            CoreLogger::addException("MySqli driver not found");
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect(): void
    {
        try {
            // Permet de générer une exception à la place des avertissements
            $driver = new mysqli_driver();
            $driver->report_mode = MYSQLI_REPORT_STRICT;

            $this->connectionObject = new mysqli($this->getTransactionHost(),
                                                 $this->getTransactionUser(),
                                                 $this->getTransactionPass());

            // Utilisation du typage natif en base de données.
            $this->getMysqli()->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE,
                                        true);
        } catch (mysqli_sql_exception $ex) {
            CoreLogger::addException("MySqli connect_error: " . $ex->getMessage());
            unset($this->connectionObject);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function &netSelect(): bool
    {
        $rslt = false;

        if ($this->netConnected()) {
            $rslt = $this->getMysqli()->select_db($this->getDatabaseName());
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect(): void
    {
        if ($this->netConnected()) {
            $this->getMysqli()->close();
            unset($this->connectionObject);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &fetchArray(): array
    {
        $values = array();
        $rslt = $this->getMysqliResult();

        if ($rslt !== null) {
            $nbRows = $rslt->num_rows;

            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = $rslt->fetch_array(MYSQLI_ASSOC);
            }
        }
        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $className
     * @return array
     */
    public function &fetchObject(string $className = null): array
    {
        $values = array();
        $rslt = $this->getMysqliResult();

        if ($rslt !== null) {
            $nbRows = $rslt->num_rows;

            if (empty($className)) {
                $className = "stdClass";
            }
            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = $rslt->fetch_object($className);
            }
        }
        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool
    {
        $success = false;
        $query = (!empty($query)) ? $query : $this->lastQueryResult;
        $rslt = $query !== null ? $this->getMysqliResult($query) : null;

        if ($rslt !== null) {
            $rslt->free();
            $success = true;
        }
        return $success;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function &affectedRows(): int
    {
        $nbRows = $this->getMysqli()->affected_rows;

        if ($nbRows < 0) {
            $rslt = $this->getMysqliResult();

            if ($rslt !== null) {
                $nbRows = $rslt->num_rows;
            }
        }
        return $nbRows;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &insertId(): string
    {
        return $this->getMysqli()->insert_id;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &getLastError(): array
    {
        $error = parent::getLastError();
        $error[] = "<span class=\"text_bold\">MySqli response</span> : " . $this->getMysqli()->error;
        return $error;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getVersion(): string
    {
        // Exemple : 5.6.15-log
        $version = $this->getMysqli()->server_info;
        return $version;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeQuery(): void
    {
        $this->lastQueryResult = $this->getMysqli()->query($this->getSql());

        if ($this->lastQueryResult === false) {
            CoreLogger::addException("MySqli query: " . $this->getMysqli()->error);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function &getTablesListQuery(): string
    {
        $sql = "SHOW TABLES LIKE '" . $this->getDatabasePrefix() . "%'";
        return $sql;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $fullTableName
     * @return string
     */
    protected function &getColumnsListQuery(string $fullTableName): string
    {
        $sql = "SHOW FULL COLUMNS FROM '" . $fullTableName . "'";
        return $sql;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string
    {
        return $this->getMysqli()->escape_string($str);
    }

    /**
     * Retourne la connexion mysqli.
     *
     * @return mysqli
     */
    private function &getMysqli(): mysqli
    {
        return $this->connectionObject;
    }

    /**
     * Retourne le résultat de la dernière requête.
     *
     * @param resource $query
     * @return mysqli_result
     */
    private function &getMysqliResult($query = null): mysqli_result
    {
        $object = null;

        if ($query === null) {
            $query = $this->lastQueryResult;
        }

        if ($query instanceof mysqli_result) {
            $object = $query;
        }
        return $object;
    }
}