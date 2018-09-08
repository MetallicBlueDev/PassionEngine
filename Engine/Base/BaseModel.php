<?php

namespace TREngine\Engine\Base;

use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Core\CoreTransaction;
use TREngine\Engine\Exec\ExecUtils;
use TREngine\Engine\Fail\FailSql;

/**
 * Modèle de base de la communication SQL.
 *
 * @author Sébastien Villemain
 */
abstract class BaseModel extends CoreTransaction
{

    /**
     * Dernier résultat de la dernière requête exécutée.
     *
     * @var mixed
     */
    protected $lastQueryResult = "";

    /**
     * Quote pour les colonnes.
     *
     * @var string
     */
    protected $quoteKey = "`";

    /**
     * Quote pour les valeurs.
     *
     * @var string
     */
    protected $quoteValue = "'";

    /**
     * Dernière requête SQL.
     *
     * @var string
     */
    private $sql = "";

    /**
     * Mémoire tampon: tableau contenant des objets standards.
     *
     * @var array
     */
    private $buffer = array();

    /**
     * Tableau contenant les clés et valeurs déjà protégées.
     *
     * @var array
     */
    private $quoted = array();

    /**
     * Retourne le nom de la base de données.
     *
     * @return string
     */
    public function &getDatabaseName(): string
    {
        return $this->getString("name");
    }

    /**
     * Retourne le préfixe à utiliser sur les tables.
     *
     * @return string
     */
    public function &getDatabasePrefix(): string
    {
        return $this->getString("prefix");
    }

    /**
     * Active ou désactive le nettoyage automatique de la mémoire.
     *
     * @param bool $enabled
     */
    public function setAutoFreeResult(bool $enabled)
    {
        $this->setDataValue("autofreeresult",
                            $enabled);
    }

    /**
     * Etat du nettoyage automatique de la mémoire.
     *
     * @return bool
     */
    public function autoFreeResult(): bool
    {
        return $this->getBool("autofreeresult");
    }

    /**
     * Retourne le nombre de lignes affectées à la dernière requête.
     *
     * @return int
     */
    abstract public function &affectedRows(): int;

    /**
     * Retourne un tableau contenant toutes les lignes demandées.
     *
     * @return array
     */
    abstract public function &fetchArray(): array;

    /**
     * Retourne un tableau contenant tous les objets demandés.
     *
     * @param string $className Nom de la classe
     * @return array
     */
    abstract public function &fetchObject(string $className = null): array;

    /**
     * Sélection d'informations d'une table.
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderBy
     * @param string $limit
     * @return BaseModel
     */
    public function &select(string $table,
                            array $values,
                            array $where = array(),
                            array $orderBy = array(),
                            string $limit = ""): BaseModel
    {
        $this->setSqlCommand("SELECT");
        $this->setSqlAfterCommand(implode(", ",
                                          $this->converFieldWithTableName($values)));
        $this->setSqlFrom("FROM " . $this->getTableName($table));

        if (!empty($where)) {
            $this->where($where);
        }
        if (!empty($orderBy)) {
            $this->orderBy($orderBy);
        }
        if (!empty($limit)) {
            $this->setSqlLimit("LIMIT " . $limit);
        }
        $this->setQueryBuilded(false);
        return $this;
    }

    /**
     * Insère une ou plusieurs valeurs dans une table.
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     * @return BaseModel
     */
    public function &insert(string $table,
                            array $keys,
                            array $values): BaseModel
    {
        $this->setSqlCommand("INSERT INTO");
        $this->setSqlFrom($this->getTableName($table));
        $this->setSqlAfterFrom("(" . implode(", ",
                                             $this->converKey($keys))
                . ") VALUES (" . implode(", ",
                                         $this->converValue($values))
                . ")");
        $this->setQueryBuilded(false);
        return $this;
    }

    /**
     * Mise à jour des informations d'une table.
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("ColumnName" => "Value")
     * @param array $where
     * @param array $orderBy
     * @param string $limit
     * @return BaseModel
     */
    public function &update(string $table,
                            array $values,
                            array $where,
                            array $orderBy = array(),
                            string $limit = ""): BaseModel
    {
        $this->setSqlCommand("UPDATE");
        $this->setSqlFrom($this->getTableName($table));

        // Affectation des clès à leurs valeurs
        $valuesString = array();
        foreach ($values as $key => $value) {
            $valuesString[] = $this->converKey($key) . " = " . $this->converValue($value);
        }
        $this->setSqlAfterFrom("SET "
                . implode(", ",
                          $valuesString));
        if (!empty($where)) {
            $this->where($where);
        }
        if (!empty($orderBy)) {
            $this->orderBy($orderBy);
        }
        if (!empty($limit)) {
            $this->setSqlLimit("LIMIT " . $limit);
        }
        $this->setQueryBuilded(false);
        return $this;
    }

    /**
     * Supprime des informations contenues dans une table.
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param string $limit
     * @return BaseModel
     */
    public function &delete(string $table,
                            array $where = array(),
                            string $limit = ""): BaseModel
    {
        $this->setSqlCommand("DELETE");
        $this->setSqlFrom("FROM " . $this->getTableName($table));
        if (!empty($where)) {
            $this->where($where);
        }
        if (!empty($limit)) {
            $this->setSqlLimit("LIMIT " . $limit);
        }
        $this->setQueryBuilded(false);
        return $this;
    }

    /**
     * Ajout dans la condition WHERE.
     *
     * @param array $where
     * @return BaseModel
     */
    public function &where(array $where): BaseModel
    {
        $this->setSqlWhere(implode(" ",
                                   $this->converFieldWithTableName($where)));
        return $this;
    }

    /**
     * Ajout dans la condition ORDER BY.
     *
     * @param array $orderBy
     * @return BaseModel
     */
    public function &orderBy(array $orderBy): BaseModel
    {
        $this->setSqlOrderBy(implode(", ",
                                     $this->converFieldWithTableName($orderBy)
        ));
        return $this;
    }

    /**
     * Active la condition DISTINCT.
     *
     * @return BaseModel
     */
    public function &distinct(): BaseModel
    {
        $this->setSqlDistinct(true);
        return $this;
    }

    /**
     * Jointure forte entre tables.
     *
     * @param string $joinTable Table à joindre.
     * @param string $secondTable Seconde table.
     * @param string $joinTableField Champ permettant de faire la jointure.
     * @param string $condition Condition de jointure (=).
     * @param string $secondTableField Champ de la seconde table permettant de faire la jointure (si différent uniquement).
     * @return BaseModel
     */
    public function &innerJoin(string $joinTable,
                               string $secondTable,
                               string $joinTableField,
                               string $condition,
                               string $secondTableField = ""): BaseModel
    {
        return $this->join($joinTable,
                           $secondTable,
                           $joinTableField,
                           $condition,
                           $secondTableField,
                           "INNER");
    }

    /**
     * Jointure de table partie gauche.
     *
     * @param string $joinTable Table à joindre.
     * @param string $secondTable Seconde table.
     * @param string $joinTableField Champ permettant de faire la jointure.
     * @param string $condition Condition de jointure (=).
     * @param string $secondTableField Champ de la seconde table permettant de faire la jointure (si différent uniquement).
     * @return BaseModel
     */
    public function &leftJoin(string $joinTable,
                              string $secondTable,
                              string $joinTableField,
                              string $condition,
                              string $secondTableField = ""): BaseModel
    {
        return $this->join($joinTable,
                           $secondTable,
                           $joinTableField,
                           $condition,
                           $secondTableField,
                           "LEFT");
    }

    /**
     * Jointure de table partie droite.
     *
     * @param string $joinTable Table à joindre.
     * @param string $secondTable Seconde table.
     * @param string $joinTableField Champ permettant de faire la jointure.
     * @param string $condition Condition de jointure (=).
     * @param string $secondTableField Champ de la seconde table permettant de faire la jointure (si différent uniquement).
     * @return BaseModel
     */
    public function &rightJoin(string $joinTable,
                               string $secondTable,
                               string $joinTableField,
                               string $condition,
                               string $secondTableField = ""): BaseModel
    {
        return $this->join($joinTable,
                           $secondTable,
                           $joinTableField,
                           $condition,
                           $secondTableField,
                           "RIGHT");
    }

    /**
     * Jointure de table en produit cartésien.
     *
     * @param string $joinTable Table à joindre.
     * @param string $secondTable Seconde table.
     * @param string $joinTableField Champ permettant de faire la jointure.
     * @param string $condition Condition de jointure (=).
     * @param string $secondTableField Champ de la seconde table permettant de faire la jointure (si différent uniquement).
     * @return BaseModel
     */
    public function &crossJoin(string $joinTable,
                               string $secondTable,
                               string $joinTableField,
                               string $condition,
                               string $secondTableField = ""): BaseModel
    {
        return $this->join($joinTable,
                           $secondTable,
                           $joinTableField,
                           $condition,
                           $secondTableField,
                           "CROSS");
    }

    /**
     * Retourne l'identifiant de la dernière ligne insérée.
     * Le typage natif n'est pas supporté ici.
     *
     * @return string
     */
    abstract public function &insertId(): string;

    /**
     * Envoi une requête SQL.
     *
     * @param string $sql
     * @throws FailSql
     */
    public function query(string $sql = ""): void
    {
        $this->beginExecuteQuery($sql);
        $this->executeQuery();
        $this->endExecuteQuery();
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public function &getSql(): string
    {
        return $this->sql;
    }

    /**
     * Libère la mémoire du résultat.
     *
     * @param mixed $query
     * @return bool
     */
    abstract public function &freeResult($query = null): bool;

    /**
     * Libère la mémoire tampon.
     *
     * @param string $name
     */
    public function freeBuffer(string $name = ""): void
    {
        if (empty($name)) {
            unset($this->buffer);
        } else {
            unset($this->buffer[$name]);
        }
    }

    /**
     * Ajoute le dernier résultat dans la mémoire tampon typé en tableau.
     *
     * @param string $name
     * @param string $columnName clé à utiliser
     */
    public function addArrayBuffer(string $name,
                                   string $columnName = ""): void
    {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchArray() as $row) {
                if (!empty($columnName)) {
                    $this->buffer[$name][$row[$columnName]] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }
            reset($this->buffer[$name]);
        }

        if ($this->autoFreeResult()) {
            $this->freeResult();
        }
    }

    /**
     * Ajoute le dernier résultat dans la mémoire tampon typé en object.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer(string $name,
                                    string $key = ""): void
    {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchObject() as $row) {
                if (!empty($key)) {
                    $this->buffer[$name][$row->$key] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }
            reset($this->buffer[$name]);
        }

        if ($this->autoFreeResult()) {
            $this->freeResult();
        }
    }

    /**
     * Retourne la mémoire tampon courant puis incrémente le pointeur interne.
     *
     * @param string $name
     * @return array
     */
    public function &fetchBuffer(string $name): array
    {
        $buffer = array(
            current($this->buffer[$name])
        );
        next($this->buffer[$name]);
        return $buffer;
    }

    /**
     * Retourne la mémoire tampon complète.
     *
     * @param string $name
     * @return mixed
     */
    public function &getBuffer(string $name)
    {
        return $this->buffer[$name];
    }

    /**
     * Retourne les dernières erreurs.
     *
     * @return array
     */
    public function &getLastError(): array
    {
        $rslt = array(
            "<span class=\"text_bold\">Last Sql query</span> : " . $this->sql
        );
        return $rslt;
    }

    /**
     * Marquer une clé comme déjà protégée.
     *
     * @param string $key
     */
    public function addQuotedKey(string $key): void
    {
        if (!empty($key)) {
            $this->quoted[$key] = true;
        }
    }

    /**
     * Marquer une valeur comme déjà protégée.
     *
     * @param string $value
     */
    public function addQuotedValue(string $value): void
    {
        if (!empty($value)) {
            $this->quoted[] = $value;
        }
    }

    /**
     * Remise à zéro du tableau de clés déjà quoté.
     */
    public function resetQuoted(): void
    {
        $this->quoted = array();
    }

    /**
     * Retourne la version de la base.
     *
     * @return string
     */
    abstract public function &getVersion(): string;

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function &getCollation(): string
    {
        $this->showColumns($this->getTableName(CoreTable::CONFIG));
        $info = $this->fetchArray();
        return isset($info['Collation']) ? $info['Collation'] : "?";
    }

    /**
     * Exécution la commande permettant d'obtenir la liste de tables.
     */
    public function &showTables(): void
    {
        $sql = $this->getTablesListQuery();

        if (empty($sql) || strpos($sql,
                                  $this->getDatabasePrefix()) === false) {
            $this->throwException("invalid tables list sql command.");
        }

        $this->query($sql);
    }

    /**
     * Exécution la commande permettant d'obtenir la liste de colonnes.
     */
    public function &showColumns(string $fullTableName): void
    {
        $sql = $this->getColumnsListQuery($fullTableName);

        if (empty($sql) || strpos($sql,
                                  $fullTableName) === false) {
            $this->throwException("invalid columns list sql command.");
        }

        $this->query($sql);
    }

    /**
     * Exécution d'une requête SQL.
     *
     * @param string $sql
     */
    abstract protected function executeQuery(): void;

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @throws FailSql
     */
    protected function throwException(string $message,
                                      string $failCode = "",
                                      array $failArgs = array()): void
    {
        throw new FailSql($message,
                          $failCode,
                          $failArgs);
    }

    /**
     * Jointure de table.
     *
     * @param string $joinTable
     * @param string $secondTable
     * @param string $joinTableField
     * @param string $condition
     * @param string $secondTableField
     * @param string $type
     * @return BaseModel
     */
    protected function &join(string $joinTable,
                             string $secondTable,
                             string $joinTableField,
                             string $condition,
                             string $secondTableField,
                             string $type): BaseModel
    {
        if (empty($secondTableField)) {
            $secondTableField = $joinTableField;
        }
        $joinTableName = $this->getTableName($joinTable);
        $secondTableName = $this->getTableName($secondTable);
        $this->setSqlAfterFrom($type . " JOIN " . $joinTableName
                . " ON " . $secondTableName . "." . $joinTableField
                . " " . $condition . " "
                . $joinTableName . "." . $secondTableField);
        return $this;
    }

    /**
     * Retourne la commande à exécuter pour obtenir la liste des tables.
     *
     * @return string
     */
    abstract protected function &getTablesListQuery(): string;

    /**
     * Retourne la commande à exécuter pour obtenir la liste des colonnes.
     *
     * @param string $fullTableName
     * @return string
     */
    abstract protected function &getColumnsListQuery(string $fullTableName): string;

    /**
     * Quote les identifiants et les valeurs.
     *
     * @param string $s
     * @param bool $isValue
     * @return string
     */
    protected function &addQuote(string $s,
                                 bool $isValue = false): string
    {
        // Ne pas quoter les champs avec la notation avec les point
        if (($isValue && !ExecUtils::inArrayStrictCaseSensitive($s,
                                                                $this->quoted)) || (!$isValue && strpos($s,
                                                                                                        ".") === false && !isset($this->quoted[$s]))) {
            if ($isValue) {
                $q = $this->quoteValue;
            } else {
                $q = $this->quoteKey;
            }
            $s = $q . $s . $q;
        }
        return $s;
    }

    /**
     * Conversion des valeurs dite PHP en valeurs semblable SQL.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function &converValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $realKey => $realValue) {
                $value[$realKey] = $this->converValue($realValue);
            }
        }
        if (is_bool($value)) {
            $value = ($value === true) ? 1 : 0;
        } else if (is_null($value)) {
            $value = "NULL";
        } else if (is_string($value)) {
            $value = $this->converEscapeString($value);
        }
        if (!is_array($value)) {
            $value = $this->addQuote($value,
                                     true);
        }
        return $value;
    }

    /**
     * Conversion des clés.
     *
     * @param mixed $key
     * @return mixed
     */
    protected function &converKey($key)
    {
        if (is_array($key)) {
            foreach ($key as $realKey => $keyValue) {
                $key[$realKey] = $this->converKey($keyValue);
            }
        } else {
            $key = $this->addQuote($key);
        }
        // Converti les multiples espaces (tabulation, espace en trop) en espace simple
        $key = preg_replace("/[\s]+/",
                            " ",
                            $key);
        return $key;
    }

    /**
     * Protège les caractères spéciaux SQL.
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string
    {
        return addslashes($str);
    }

    /**
     * Retourne le nom de la table avec le préfixage.
     *
     * @param string $table
     */
    protected function getTableName(string $table): string
    {
        return $this->getDatabasePrefix() . "_" . $table;
    }

    /**
     * Vérifie et complète le nom des tables.
     *
     * @param array $values
     * @return array
     */
    private function &converFieldWithTableName(array &$values)
    {
        foreach ($values as &$value) {
            $pos = strpos($value,
                          ".");
            if ($pos !== false) {
                $value = $this->getTableName(substr($value,
                                                    0,
                                                    $pos))
                        . substr($value,
                                 $pos);
            }
        }
        return $values;
    }

    /**
     * Début d'exécution de la requête.
     *
     * @param string $sql
     */
    private function beginExecuteQuery(string $sql): void
    {
        if (!empty($sql)) {
            $this->sql = $sql;
        } else {
            $this->buildQuery();
        }
    }

    /**
     * Traitement après l'exécution d'une requête.
     */
    private function endExecuteQuery(): void
    {
        $this->resetQuoted();
        $this->resetSql();

        // Ajout la requête au log
        if (CoreSecure::debuggingMode()) {
            CoreLogger::addSqlRequest($this->sql);
        }

        // Création d'une exception si une réponse est négative (false)
        if ($this->lastQueryResult === false) {
            $this->throwException("bad query",
                                  FailBase::getErrorCodeName(19),
                                                             array($this->sql));
        }
    }

    /**
     * Compile si nécessaire la requête SQL.
     */
    private function buildQuery(): void
    {
        if (!$this->queryBuilded()) {
            $this->fireBuildQuery();
            $this->setQueryBuilded(true);
        }
    }

    /**
     * Compile la requête SQL.
     */
    private function fireBuildQuery(): void
    {
        $this->checkQueryCondition();

        $this->sql = $this->getSqlCommand()
                . " " . $this->getSqlAfterCommand()
                . " " . $this->getSqlFrom()
                . " " . $this->getSqlAfterFrom()
                . " " . $this->getSqlWhere()
                . " " . $this->getSqlOrderBy()
                . " " . $this->getSqlLimit();
    }

    /**
     * Vérification supplémentaire pour la construction de la requête SQL.
     */
    private function checkQueryCondition(): void
    {
        $this->checkDistinctCondition();
        $this->checkWhereCondition();
        $this->checkOrderByCondition();
    }

    /**
     * Vérification de la condition DISTINCT.
     */
    private function checkDistinctCondition(): void
    {
        if ($this->hasSqlDistinct()) {
            $this->setSqlCommand($this->getSqlCommand() . " DISTINCT");
        }
    }

    /**
     * Vérification de la conditon WHERE.
     */
    private function checkWhereCondition(): void
    {
        $sqlWhere = $this->getSqlWhere();

        if (!empty($sqlWhere)) {
            if (strpos($sqlWhere,
                       "WHERE") === false) {
                $this->setSqlWhere("WHERE " . $sqlWhere);
            }
        }
    }

    /**
     * Vérification de la condition ORDER BY.
     */
    private function checkOrderByCondition(): void
    {
        $sqlOrderBy = $this->getSqlOrderBy();

        if (!empty($sqlOrderBy)) {
            if (strpos($sqlOrderBy,
                       "ORDER BY") === false) {
                $this->setSqlOrderBy("ORDER BY " . $sqlOrderBy);
            }
        }
    }

    /**
     * Change la commande SQL (SELECT, UPDATE, DELETE, ...).
     *
     * @param string $command
     */
    private function setSqlCommand(string $command): void
    {
        $this->setDataValue("sqlcommand",
                            $command);
    }

    /**
     * Retourne la commande SQL (SELECT, UPDATE, DELETE, ...).
     *
     * @return string
     */
    private function &getSqlCommand(): string
    {
        return $this->getString("sqlcommand");
    }

    /**
     * Change la portion après la commande SQL (valeur, etc.).
     *
     * @param string $command
     */
    private function setSqlAfterCommand(string $command): void
    {
        $this->setDataValue("sqlaftercommand",
                            $command);
    }

    /**
     * Retourne la portion après la commande SQL (valeur, etc.).
     *
     * @return string
     */
    private function &getSqlAfterCommand(): string
    {
        return $this->getString("sqlaftercommand");
    }

    /**
     * Change la condition FROM (la ou les tables).
     *
     * @param string $from
     */
    private function setSqlFrom(string $from): void
    {
        $this->setDataValue("sqlfrom",
                            $from);
    }

    /**
     * Retourne la condition FROM (la ou les tables).
     *
     * @return string
     */
    private function &getSqlFrom(): string
    {
        return $this->getString("sqlfrom");
    }

    /**
     * Change la portion après la condition FROM (valeur, etc.).
     *
     * @param string $command
     */
    private function setSqlAfterFrom(string $command): void
    {
        $this->setDataValue("sqlafterfrom",
                            $command);
    }

    /**
     * Retourne la portion après la condition FROM (valeur, etc.).
     *
     * @return string
     */
    private function &getSqlAfterFrom(): string
    {
        return $this->getString("sqlafterfrom");
    }

    /**
     * Change la condition WHERE.
     *
     * @param string $where
     */
    private function setSqlWhere(string $where): void
    {
        $this->setDataValue("sqlwhere",
                            $where);
    }

    /**
     * Retourne la condition WHERE.
     *
     * @return string
     */
    private function &getSqlWhere(): string
    {
        return $this->getString("sqlwhere");
    }

    /**
     * Change la condition LIMIT.
     *
     * @param string $limit
     */
    private function setSqlLimit(string $limit): void
    {
        $this->setDataValue("sqllimit",
                            $limit);
    }

    /**
     * Retourne la condition LIMIT.
     *
     * @return string
     */
    private function &getSqlLimit(): string
    {
        return $this->getString("sqllimit");
    }

    /**
     * Change la condition ORDER BY.
     *
     * @param string $orderBy
     */
    private function setSqlOrderBy(string $orderBy): void
    {
        $this->setDataValue("sqlorderby",
                            $orderBy);
    }

    /**
     * Retourne la condition ORDER BY.
     *
     * @return string
     */
    private function &getSqlOrderBy(): string
    {
        return $this->getString("sqlorderby");
    }

    /**
     * Active ou désactive la condition DISTINCT.
     *
     * @param bool $enabled
     */
    private function setSqlDistinct(bool $enabled): void
    {
        $this->setDataValue("sqldistinct",
                            $enabled);
    }

    /**
     * Etat de la condition DISTINCT.
     *
     * @return bool
     */
    private function &hasSqlDistinct(): bool
    {
        return $this->getBool("sqldistinct");
    }

    /**
     * Active ou désactive la compilation de la requête.
     *
     * @param bool $builded
     */
    private function setQueryBuilded(bool $builded): void
    {
        $this->setDataValue("querybuilded",
                            $builded);
    }

    /**
     * Etat de la compilation de la requête.
     *
     * @return bool
     */
    private function &queryBuilded(): bool
    {
        return $this->getBool("querybuilded");
    }

    /**
     * Nettoyage des portions SQL.
     */
    private function resetSql(): void
    {
        $this->unsetDataValues("sql");
    }
}