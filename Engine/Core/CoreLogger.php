<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Exec\ExecTimeMarker;
use PassionEngine\Engine\Exec\ExecString;

/**
 * Gestionnaire de messages (journal d'information, d'avertissement et d'erreur).
 *
 * @author Sébastien Villemain
 */
class CoreLogger
{

    /**
     * Message important pour l'utilisateur.
     *
     * @var string
     */
    private const USER_ALERT = 'alert';

    /**
     * Message d'avertissement ou note d'information demandant une action à l'utilisateur.
     *
     * @var string
     */
    private const USER_NOTE = 'note';

    /**
     * Message d'information sans action attendue pour l'utilisateur.
     *
     * @var string
     */
    private const USER_INFORMATION = 'info';

    /**
     * Message destinées au développeur.
     *
     * @var array
     */
    private static $debugMessages = array();

    /**
     * Messages destinées à l'utilisateur.
     *
     * @var array
     */
    private static $userMessages = array();

    /**
     * Tableau contenant toutes les lignes SQL envoyées.
     *
     * @var array
     */
    private static $sqlRequest = array();

    /**
     * Ajoute un message pour le développeur.
     *
     * @param string $message Ce message doit être en anglais.
     */
    public static function addDebug(string $message): void
    {
        $message = strtolower($message);
        $message[0] = strtoupper($message[0]);
        self::$debugMessages[] = date('Y-m-d H:i:s') . ' : ' . $message . '.';
    }

    /**
     * Ajoute une requête SQL pour le développeur.
     *
     * @param string $query Requête SQL.
     */
    public static function addSqlRequest(string $query): void
    {
        self::$sqlRequest[] = $query;
    }

    /**
     * Ajoute un message important pour l'utilisateur.
     *
     * @param string $message Ce message doit être traduit.
     */
    public static function addUserAlert(string $message): void
    {
        self::addMessage($message,
                         self::USER_ALERT);
    }

    /**
     * Ajoute un message d'avertissement ou note d'information demandant une action à l'utilisateur.
     *
     * @param string $message Ce message doit être traduit.
     */
    public static function addUserWarning(string $message): void
    {
        self::addMessage($message,
                         self::USER_NOTE);
    }

    /**
     * Ajoute un message d'information sans action attendue pour l'utilisateur.
     *
     * @param string $message Ce message doit être traduit.
     */
    public static function addUserInformation(string $message): void
    {
        self::addMessage($message,
                         self::USER_INFORMATION);
    }

    /**
     * Retourne / affiche les messages pour l'utilisateur.
     */
    public static function displayUserMessages(): void
    {
        if (CoreLoader::isCallable('CoreMain')) {
            $hasMessages = !empty(self::$userMessages);
            $display = 'none';
            $rslt = '';

            if ($hasMessages) {
                $display = 'block';
                $rslt .= '<ul class="exception">';

                foreach (self::$userMessages as $type => $infos) {
                    foreach ($infos as $message) {
                        $rslt .= '<li class="' . $type . '"><div>' . $message . '</div></li>';
                    }
                }

                $rslt .= '</ul>';
            }

            // Réaction différente en fonction du type d'affichage demandée
            if (CoreMain::getInstance()->getRoute()->isDefaultLayout()) {
                echo '<div id="panel_message" style="display: ' . $display . ';">' . $rslt . '</div>';
            } else if ($hasMessages) {
                if (CoreLoader::isCallable('CoreHtml')) {
                    if (CoreHtml::getInstance()->javascriptEnabled()) {
                        CoreHtml::getInstance()->addJavascript('displayMessage(\'' . ExecString::addSlashes($rslt) . '\');');
                        $rslt = '';
                    }
                }

                if (!empty($rslt)) {
                    echo $rslt;
                }
            }
        }
    }

    /**
     * Affichage des informations de débogage pour le développeur.
     */
    public static function displayDebugInformations(): void
    {
        if (CoreLoader::isCallable('CoreMain') && CoreLoader::isCallable('CoreSession')) {
            if (CoreSession::getInstance()->getSessionData()->hasRegisteredRank()) {
                echo self::getSqlRequestPane()
                . self::getDebugMessagesPane()
                . self::getBenchmarkPane();
            }
        }
    }

    /**
     * Retourne les messages de débogage.
     *
     * @return array
     */
    public static function &getDebugMessage(): array
    {
        return self::$debugMessages;
    }

    /**
     * Enregistrement du rapport d'erreur dans un fichier.
     */
    public static function logException(): void
    {
        if (CoreLoader::isCallable('CoreCache')) {
            if (!empty(self::$debugMessages)) {
                // Ecriture à la suite du rapport
                CoreCache::getInstance(CoreCacheSection::LOGGER)->writeCacheAsString('exception_' . date('Y-m-d') . '.log.php',
                                                                                                         implode("\n",
                                                                                                                 self::$debugMessages),
                                                                                                                 false);
            }
        }
    }

    /**
     * Retourne les informations de débogage sur le SQL.
     *
     * @return string
     */
    private static function getSqlRequestPane(): string
    {
        $rslt = "";
        $nbQueries = count(self::$sqlRequest);

        $rslt .= '<div style="color: blue;"><br />'
            . '***********************SQL REQUESTS (' . $nbQueries . ') :<br />';

        if ($nbQueries > 0) {
            $rslt .= str_replace("\n",
                                 '<br />',
                                 implode("\n",
                                         self::$sqlRequest));
        } else {
            $rslt .= '<span style="color: #2EFE2E;">No sql request registred.</span>';
        }
        return $rslt;
    }

    /**
     * Retourne les messages de débogage.
     *
     * @return string
     */
    private static function getDebugMessagesPane(): string
    {
        $rslt = "";
        $nbMessages = count(self::$debugMessages);

        $rslt .= '<br /><br />***********************EXCEPTIONS (' . $nbMessages . ') :<br />';

        if ($nbMessages > 0) {
            $rslt .= '<span style="color: red;">'
                . str_replace("\n",
                              '<br />',
                              implode("\n",
                                      self::$debugMessages))
                . '</span>';
        } else {
            $rslt .= '<span style="color: #2EFE2E;">No exception registred.</span>';
        }
        return $rslt;
    }

    /**
     * Retourne les informations de timing du moteur.
     *
     * @return string
     */
    private static function getBenchmarkPane(): string
    {
        return '<br /><br />***********************BENCHMARKER :<br />'
            . 'Core : ' . ExecTimeMarker::getMeasurement('core') . ' ms'
            . '<br />Launcher : ' . ExecTimeMarker::getMeasurement('launcher') . ' ms'
            . '<br />Main : ' . ExecTimeMarker::getMeasurement('main') . ' ms'
            . '<br /><span class="text_bold">All : ' . ExecTimeMarker::getMeasurement('all') . ' ms</span>'
            . '</div>';
    }

    /**
     * Ajoute un nouveau message pour l'utilisateur.
     *
     * @param string $message
     * @param string $type Le type d'erreur (alerte / note / information).
     */
    private static function addMessage(string $message,
                                       string $type): void
    {
        switch ($type) {
            // no break
            case self::USER_ALERT:
            case self::USER_INFORMATION:
            case self::USER_NOTE:
                self::$userMessages[$type][] = $message;
                break;
            default:
                self::addMessage($message,
                                 self::USER_ALERT);
                break;
        }
    }
}