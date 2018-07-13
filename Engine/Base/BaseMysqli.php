<?php

namespace TREngine\Engine\Base;

use mysqli;
use mysqli_sql_exception;
use mysqli_result;
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
        $rslt = function_exists("mysqli_connect");

        if (!$rslt) {
            CoreLogger::addException("MySqli function not found");
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect(): void
    {
        try {
            // Permet de générer une exception à la place des avertissements qui spam
            mysqli_report(MYSQLI_REPORT_STRICT);
            $this->connId = new mysqli($this->getTransactionHost(),
                                       $this->getTransactionUser(),
                                       $this->getTransactionPass());
        } catch (mysqli_sql_exception $ex) {
            CoreLogger::addException("MySqli connect_error: " . $ex->getMessage());
            $this->connId = null;
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
        }

        $this->connId = null;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $sql
     */
    public function query(string $sql = ""): void
    {
        $this->queries = $this->getMysqli()->query($sql);

        if ($this->queries === false) {
            CoreLogger::addException("MySqli query: " . $this->getMysqli()->error);
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
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update(string $table,
                           array $values,
                           array $where,
                           array $orderby = array(),
                           string $limit = ""): void
    {
        parent::update($table,
                       $values,
                       $where,
                       $orderby,
                       $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select(string $table,
                           array $values,
                           array $where = array(),
                           array $orderby = array(),
                           string $limit = ""): void
    {
        parent::select($table,
                       $values,
                       $where,
                       $orderby,
                       $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $keys
     * @param array $values
     */
    public function insert(string $table,
                           array $keys,
                           array $values): void
    {
        parent::insert($table,
                       $keys,
                       $values);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete(string $table,
                           array $where = array(),
                           array $like = array(),
                           string $limit = ""): void
    {
        parent::delete($table,
                       $where,
                       $like,
                       $limit);
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
        return $this->connId;
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
            $query = $this->queries;
        }

        if ($query instanceof mysqli_result) {
            $object = $query;
        }
        return $object;
    }
}