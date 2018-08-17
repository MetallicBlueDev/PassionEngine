<?php

namespace TREngine\Engine\Base;

use PDO;
use PDOException;
use PDOStatement;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Exec\ExecUtils;
use TREngine\Engine\Base\PdoSpecific\PdoPlatformSpecific;

/**
 * PDO: PHP Data Objects.
 * Data-access abstraction layer.
 *
 * Gestionnaire de transaction utilisant l'extension native PHP.
 *
 * Support de nombreux types base de données :
 * FIREBIRD : Firebird,
 * INFORMIX : IBM Informix Dynamic Server,
 * SQLSRV : Microsoft SQL Server / SQL Azure,
 * MYSQL : MySQL 3.x/4.x/5.x,
 * OCI : Oracle Call Interface,
 * ODBC : Open Database Connectivity v3 (IBM DB2, unixODBC and win32 ODBC),
 * PGSQL : PostgreSQL,
 * SQLITE : SQLite 2 / 3,
 * DBLIB : FreeTDS / Microsoft SQL Server / Sybase,
 * CUBRID : Cubrid.
 *
 * @author Sébastien Villemain
 */
class BasePdo extends BaseModel
{

    /**
     * Nom du pilote PDO.
     *
     * @var string
     */
    private $driverName = null;

    /**
     * Instance contenant du code spécifique à une configuration.
     *
     * @var PdoPlatformSpecific
     */
    private $platformSpecific = null;

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool
    {
        $rslt = false;
        $driverName = $this->getDriverName();

        if (!empty($driverName)) {
            $rslt = ExecUtils::inArrayStrictCaseInSensitive($driverName,
                                                            PDO::getAvailableDrivers());
        }

        if (!$rslt) {
            CoreLogger::addException("PDO driver not found: " . $driverName);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect(): void
    {
        try {
            // Host = mysql:host=127.0.0.1
            $this->connId = new PDO($this->getTransactionHost(),
                                    $this->getTransactionUser(),
                                    $this->getTransactionPass());

            // Utilisation du typage natif en base de données.
            $this->getPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES,
                                          false);
            $this->getPdo()->setAttribute(PDO::ATTR_STRINGIFY_FETCHES,
                                          false);
        } catch (PDOException $ex) {
            CoreLogger::addException("PDO exception: " . $ex->getMessage());
            unset($this->connId);
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
            $rslt = ($this->getPdo()->exec("USE " . $this->getDatabaseName()) !== false);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect(): void
    {
        if ($this->netConnected()) {
            unset($this->connId);
            unset($this->platformSpecific);
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
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $values = $rslt->fetchAll(PDO::FETCH_ASSOC);
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
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            if (empty($className)) {
                $values = $rslt->fetchAll(PDO::FETCH_CLASS);
            } else {
                $values = $rslt->fetchAll(PDO::FETCH_CLASS,
                                          $className);
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
        $query = (!empty($query)) ? $query : $this->lastQueryResult;
        if ($query !== null) {
            unset($query);
        }
        $success = true;
        return $success;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function &affectedRows(): int
    {
        $value = -1;
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $value = $rslt->rowCount();
        }
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &insertId(): string
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &getLastError(): array
    {
        $error = parent::getLastError();
        $error[] = "<span class=\"text_bold\">Pdo response</span> : " . $this->getPdoErrorMessage();
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
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        return $version;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeQuery(): void
    {
        $this->lastQueryResult = $this->getPdo()->query($this->getSql());

        if ($this->lastQueryResult === false) {
            CoreLogger::addException("PDO query: " . $this->getPdoErrorMessage());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function &getTablesListQuery(): string
    {
        return $this->getPlatformSpecific()->getTablesListQuery($this->getDatabasePrefix());
    }

    /**
     * {@inheritDoc}
     *
     * @param string $fullTableName
     * @return string
     */
    protected function &getColumnsListQuery(string $fullTableName): string
    {
        return $this->getPlatformSpecific()->getColumnsListQuery($fullTableName);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string
    {
        // Impossible d'utiliser $this->getPdo()->quote($str) car incompatible avec la méthode interne addQuote()
        return str_replace(array(
            '\\',
            "\0",
            "\n",
            "\r",
            "'",
            '"',
            "\x1a"),
                           array(
                    '\\\\',
                    '\\0',
                    '\\n',
                    '\\r',
                    "\\'",
                    '\\"',
                    '\\Z'),
                           $str);
    }

    /**
     * Retourne le nom du pilote PDO.
     *
     * @return string
     */
    private function &getDriverName(): string
    {
        if ($this->driverName === null) {
            $this->checkDriverName();
        }
        return $this->driverName;
    }

    /**
     * Retourne l'instance contenant le code spécifique à la configuration.
     *
     * @return PdoPlatformSpecific
     */
    private function &getPlatformSpecific(): PdoPlatformSpecific
    {
        if ($this->platformSpecific === null) {
            $this->checkPlatformSpecific();
        }
        return $this->platformSpecific;
    }

    /**
     * Recherche le nom du pilote PDO.
     */
    private function checkDriverName(): void
    {
        $dataSourceName = $this->getTransactionHost();
        $pos = strpos($dataSourceName,
                      ":");

        if ($pos !== false) {
            $this->driverName = substr($dataSourceName,
                                       0,
                                       $pos);
        }

        if ($this->driverName === null) {
            CoreLogger::addException("Invalid PDO driver syntax");
            $this->driverName = "";
        }
    }

    /**
     * Recherche l'nstance contenant le code spécifique à la configuration.
     */
    private function checkPlatformSpecific(): void
    {
        $fullClassName = "TREngine\Engine\Base\PdoSpecific\\" . ucfirst($this->getDriverName()) . "Specific";

        if (CoreLoader::classLoader($fullClassName)) {
            $this->platformSpecific = new $fullClassName();
        } else {
            CoreLogger::addException("PDO platform specific implementation not found: " . $this->getDriverName());
            $this->platformSpecific = new PdoPlatformSpecific();
        }
    }

    /**
     * Retourne la connexion PDO.
     *
     * @return PDO
     */
    private function &getPdo(): ?PDO
    {
        return $this->connId;
    }

    /**
     * Retourne le résultat de la dernière requête.
     *
     * @param mixed $query
     * @return PDOStatement
     */
    private function &getPdoResult($query = null): ?PDOStatement
    {
        $object = null;

        if ($query === null) {
            $query = $this->lastQueryResult;
        }

        if ($query instanceof PDOStatement) {
            $object = $query;
        }
        return $object;
    }

    /**
     * Retourne le dernier message d'erreur.
     *
     * @return string
     */
    private function &getPdoErrorMessage(): string
    {
        $error = $this->getPdo()->errorInfo();
        $message = implode(" // ",
                           $error);
        return $message;
    }
}