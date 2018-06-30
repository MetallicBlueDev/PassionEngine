<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Fail\FailEngine;

/**
 * Collecteur d'information sur une transaction.
 *
 * @author Sébastien Villemain
 */
abstract class CoreTransaction extends CoreDataStorage
{

    /**
     * Objet de connexion.
     *
     * @var mixed (resource / mysqli / etc.)
     */
    protected $connId = null;

    /**
     * Nouveau modèle de transaction.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Paramètre la connexion, test la connexion puis engage une connexion.
     *
     * @param array $transaction
     * @throws FailEngine
     */
    public function initialize(array &$transaction)
    {
        if (!$this->initialized()) {
            $this->newStorage($transaction);

            if ($this->canUse()) {
                // Connexion au serveur
                $this->netConnect();

                if (!$this->netConnected()) {
                    $this->throwException("connection error",
                                          20);
                }

                // Sélection d'une base de données
                if (!$this->netSelect()) {
                    $this->throwException("can not select a node",
                                          21);
                }
            } else {
                $this->throwException("transaction can not be used",
                                      22);
            }
        }
    }

    /**
     * Destruction de la communication.
     */
    public function __destruct()
    {
        $this->netDeconnect();
    }

    /**
     * Etablie une connexion au serveur.
     */
    public function netConnect()
    {

    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return bool
     */
    public function netConnected(): bool
    {
        return ($this->connId !== null) ? true : false;
    }

    /**
     * Déconnexion du serveur.
     */
    public function netDeconnect()
    {

    }

    /**
     * Sélectionne un noeud dans la transaction.
     *
     * @return bool true succès
     */
    public function &netSelect(): bool
    {
        $rslt = false;
        return $rslt;
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function &getTransactionHost(): string
    {
        return $this->getStringValue("host");
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function &getTransactionUser(): string
    {
        return $this->getStringValue("user");
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function &getTransactionPass(): string
    {
        return $this->getStringValue("pass");
    }

    /**
     * Retourne le type de transaction (exemple mysqli, php, ftp, etc.).
     *
     * @return string
     */
    public function &getTransactionType(): string
    {
        return $this->getStringValue("type");
    }

    /**
     * Détermine si le gestionnaire est utilisable.
     *
     * @return bool
     */
    protected function canUse(): bool
    {
        return false;
    }

    /**
     * Lance une exception gérant ce type de transaction.
     *
     * @param string $message
     * @param int $failCode
     * @param array $failArgs
     * @throws FailEngine
     */
    protected function throwException(string $message, int $failCode = 0, array $failArgs = array())
    {
        throw new FailEngine($message,
                             $failCode,
                             $failArgs);
    }
}