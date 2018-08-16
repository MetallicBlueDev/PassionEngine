<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Base\BaseModel;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Fail\FailSql;
use Throwable;

/**
 * Gestionnaire de la communication SQL.
 *
 * @author Sébastien Villemain
 */
class CoreSql extends BaseModel
{

    /**
     * Gestionnnaire SQL.
     *
     * @var CoreSql
     */
    private static $coreSql = null;

    /**
     * Modèle de base sélectionné.
     *
     * @var BaseModel
     */
    private $selectedBase = null;

    /**
     * Nouveau gestionnaire.
     *
     * @throws FailSql
     */
    protected function __construct()
    {
        parent::__construct();

        $fullClassName = "";
        $loaded = false;
        $databaseConfig = CoreMain::getInstance()->getConfigs()->getConfigDatabase();

        if (!empty($databaseConfig) && isset($databaseConfig['type'])) {
            // Chargement des drivers pour la base
            $fullClassName = CoreLoader::getFullQualifiedClassName(CoreLoader::BASE_FILE . ucfirst($databaseConfig['type']));
            $loaded = CoreLoader::classLoader($fullClassName);
        }

        if (!$loaded) {
            throw new FailSql("database driver not found",
                              FailBase::getErrorCodeName(13),
                                                         array($databaseConfig['type']));
        }

        if (!CoreLoader::isCallable($fullClassName,
                                    "initialize")) {
            throw new FailSql("unable to initialize database",
                              FailBase::getErrorCodeName(14),
                                                         array($fullClassName));
        }

        try {
            $this->selectedBase = new $fullClassName();
            $this->selectedBase->initialize($databaseConfig);
        } catch (Throwable $ex) {
            $this->selectedBase = null;
            throw $ex;
        }
    }

    /**
     * Aucune action réalisée dans ce contexte.
     *
     * @param array $database
     */
    public function initialize(array &$database): void
    {
        // NE RIEN FAIRE
        unset($database);
    }

    /**
     * Destruction du gestionnaire.
     */
    public function __destruct()
    {
        parent::__destruct();
        $this->selectedBase = null;
    }

    /**
     * Retourne l'instance du gestionnaire SQL.
     *
     * @return CoreSql
     */
    public static function &getInstance(): CoreSql
    {
        self::checkInstance();
        return self::$coreSql;
    }

    /**
     * Vérification de l'instance du gestionnaire SQL.
     */
    public static function checkInstance(): void
    {
        if (self::$coreSql === null) {
            self::$coreSql = new CoreSql();
        }
    }

    /**
     * Détermine si une connexion est disponible.
     *
     * @return bool
     */
    public static function hasConnection(): bool
    {
        return self::$coreSql !== null && self::$coreSql->selectedBase !== null;
    }

    /**
     * Retourne la liste des types de base supporté.
     *
     * @return array
     */
    public static function &getBaseList(): array
    {
        return CoreCache::getInstance()->getFileList(CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . CoreLoader::BASE_FILE,
                                                     CoreLoader::BASE_FILE);
    }

    /**
     * {@inheritdoc}
     */
    public function netConnect(): void
    {
        $this->selectedBase->netConnect();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function netConnected(): bool
    {
        return $this->selectedBase !== null && $this->selectedBase->netConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function netDeconnect(): void
    {
        if (self::hasConnection()) {
            $this->selectedBase->netDeconnect();
        }
    }

    /**
     * Sélectionne la base de données.
     *
     * @return bool
     */
    public function &netSelect(): bool
    {
        return $this->selectedBase->netSelect();
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function &affectedRows(): int
    {
        return $this->selectedBase->affectedRows();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionHost(): string
    {
        return $this->selectedBase->getTransactionHost();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionUser(): string
    {
        return $this->selectedBase->getTransactionUser();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionPass(): string
    {
        return $this->selectedBase->getTransactionPass();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getDatabaseName(): string
    {
        return $this->selectedBase->getDatabaseName();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getTransactionType(): string
    {
        return $this->selectedBase->getTransactionType();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getDatabasePrefix(): string
    {
        return $this->selectedBase->getDatabasePrefix();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     * @throws FailSql
     */
    public function delete(string $table,
                           array $where = array(),
                           array $like = array(),
                           string $limit = ""): void
    {
        $this->selectedBase->delete($table,
                                    $where,
                                    $like,
                                    $limit);
        $this->query();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function &fetchArray(): array
    {
        return $this->selectedBase->fetchArray();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $className
     * @return array
     */
    public function &fetchObject(string $className = null): array
    {
        return $this->selectedBase->fetchObject($className);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     * @throws FailSql
     */
    public function insert(string $table,
                           array $keys,
                           array $values): void
    {
        $this->selectedBase->insert($table,
                                    $keys,
                                    $values);
        $this->query();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &insertId(): string
    {
        return $this->selectedBase->insertId();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $sql
     * @throws FailSql
     */
    public function query(string $sql = ""): void
    {
        $sql = (!empty($sql)) ? $sql : $this->getSql();

        $this->selectedBase->query($sql);
        $this->selectedBase->resetQuoted();

        // Ajout la requête au log
        if (CoreSecure::debuggingMode()) {
            CoreLogger::addSqlRequest($sql);
        }

        // Création d'une exception si une réponse est négative (false)
        if ($this->getLastQueryResult() === false) {
            throw new FailSql("bad query",
                              FailBase::getErrorCodeName(19),
                                                         array($sql));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     * @throws FailSql
     */
    public function select(string $table,
                           array $values,
                           array $where = array(),
                           array $orderby = array(),
                           string $limit = ""): void
    {
        $this->selectedBase->select($table,
                                    $values,
                                    $where,
                                    $orderby,
                                    $limit);
        $this->query();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("keyName" => "newValue")
     * @param array $where
     * @param array $orderby
     * @param string $limit
     * @throws FailSql
     */
    public function update(string $table,
                           array $values,
                           array $where,
                           array $orderby = array(),
                           string $limit = ""): void
    {
        $this->selectedBase->update($table,
                                    $values,
                                    $where,
                                    $orderby,
                                    $limit);
        $this->query();
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function &getLastQueryResult()
    {
        return $this->selectedBase->getLastQueryResult();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getSql(): string
    {
        return $this->selectedBase->getSql();
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool
    {
        $query = (!empty($query)) ? $query : $this->getLastQueryResult();
        return $this->selectedBase->freeResult($query);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param string $key
     */
    public function addArrayBuffer(string $name,
                                   string $key = ""): void
    {
        $this->selectedBase->addArrayBuffer($name,
                                            $key);
        $this->freeResult();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer(string $name,
                                    string $key = ""): void
    {
        $this->selectedBase->addObjectBuffer($name,
                                             $key);
        $this->freeResult();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @return array
     */
    public function &fetchBuffer(string $name): array
    {
        return $this->selectedBase->fetchBuffer($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @return mixed
     */
    public function &getBuffer(string $name)
    {
        return $this->selectedBase->getBuffer($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function &getLastError(): array
    {
        return $this->selectedBase->getLastError();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     */
    public function addQuotedKey(string $key): void
    {
        $this->selectedBase->addQuotedKey($key);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $value
     */
    public function addQuotedValue(string $value): void
    {
        $this->selectedBase->addQuotedValue($value);
    }

    /**
     * Aucune action réalisée dans ce contexte.
     */
    public function resetQuoted(): void
    {
        // NE RIEN FAIRE
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getVersion(): string
    {
        return $this->selectedBase->getVersion();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function &getCollation(): string
    {
        return $this->selectedBase->getCollation();
    }
}