<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Fail\FailBase;

/**
 * Collecteur d'information sur une transaction.
 *
 * @author Sébastien Villemain
 */
abstract class CoreTransaction extends CoreDataStorage
{

    use CoreTraitException;

    /**
     * Objet de connexion.
     *
     * @var mixed
     */
    private $connectionObject = null;

    /**
     * Nouveau modèle de transaction.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Paramètre la connexion, test la connexion puis engage une connexion.
     *
     * @param array $transaction
     * @throws FailBase
     */
    public function initialize(array &$transaction): void
    {
        if (!$this->initialized()) {
            $this->newStorage($transaction);

            if ($this->canUse()) {
                $this->netConnect();

                if (!$this->netConnected()) {
                    $this->throwException('connection error',
                                          FailBase::getErrorCodeName(20));
                }

                if (!$this->netSelect()) {
                    $this->throwException('can not select a node',
                                          FailBase::getErrorCodeName(21));
                }
            } else {
                $this->throwException('transaction can not be used',
                                      FailBase::getErrorCodeName(22));
            }
        }
    }

    /**
     * Destruction de la communication.
     */
    public function __destruct()
    {
        $this->netDeconnect();
        parent::__destruct();
    }

    /**
     * Lance une connexion au serveur.
     */
    abstract public function netConnect(): void;

    /**
     * Retourne l'état de la connexion.
     *
     * @return bool
     */
    public function netConnected(): bool
    {
        return (isset($this->connectionObject) && $this->connectionObject !== null) ? true : false;
    }

    /**
     * Déconnexion du serveur.
     */
    abstract public function netDeconnect(): void;

    /**
     * Sélectionne un noeud dans la transaction.
     *
     * @return bool Succès
     */
    abstract public function &netSelect(): bool;

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost(): string
    {
        return $this->getString('host');
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser(): string
    {
        return $this->getString('user');
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass(): string
    {
        return $this->getString('pass');
    }

    /**
     * Retourne le type de transaction (exemple MSSQL, PHP, ftp, etc.).
     *
     * @return string
     */
    public function &getTransactionType(): string
    {
        return $this->getString('type');
    }

    /**
     * Détermine si le gestionnaire est utilisable.
     *
     * @return bool
     */
    abstract protected function canUse(): bool;

    /**
     * Retourne l'objet de connexion.
     *
     * @return mixed
     * @throws FailBase
     */
    protected function &getConnectionObject()
    {
        if (!$this->netConnected()) {
            $this->throwException('connection object unavailable',
                                  FailBase::getErrorCodeName(13));
        }
        return $this->connectionObject;
    }

    /**
     * Affecte l'objet de connexion.
     *
     * @param mixed $connectionObject
     */
    protected function setConnectionObject($connectionObject): void
    {
        $this->connectionObject = $connectionObject;
    }

    /**
     * Nettoyage en mémoire de l'objet de connexion.
     */
    protected function unsetConnectionObject(): void
    {
        unset($this->connectionObject);
    }
}