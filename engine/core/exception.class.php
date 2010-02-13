<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire des exceptions
 * 
 * @author S�bastien Villemain
 *
 */
class Core_Exception {
	
	/**
	 * Tableau contenant toutes les exceptions internes rencontr�es
	 * Destin� au developpeur
	 * 
	 * @var array
	 */
	private static $exception = array();
	
	/**
	 * Tableau contenant toutes les erreurs mineurs de type warning / alerte rencontr�es
	 * Destin� au client
	 * 
	 * @var array
	 */
	private static $alertError = array();
	
	/**
	 * Tableau contenant toutes les erreurs mineurs de type informative rencontr�es
	 * Destin� au client
	 * 
	 * @var array
	 */
	private static $noteError = array();
	
	/**
	 * Tableau contenant toutes les erreurs de type informative rencontr�es
	 * Destin� au client
	 * 
	 * @var array
	 */
	private static $infoError = array();
	
	/**
	 * Activer l'�crire dans une fichier log
	 * 
	 * @var boolean
	 */
	private static $writeLog = true;
	
	/**
	 * Nombre de requ�te Sql execut�es
	 * 
	 * @var int
	 */
	public static $numberOfRequest = 0;
	
	/**
	 * Tableau contenant toutes les lignes sql envoy�es
	 * 
	 * @var array
	 */
	private static $sqlRequest = array();
	
	/**
	 * Activer ou d�sactiver le rapport d'erreur dans un log
	 * 
	 * @param boolean $active
	 */
	public static function setWriteLog($active) {
		self::$writeLog = ($active) ? true : false;
	}
	
	/**
	 * Ajoute une nouvelle exception
	 * 
	 * @param $msg
	 */
	public static function setException($msg) {
		$msg = strtolower($msg);
		$msg[0] = strtoupper($msg[0]);
		self::$exception[] = date('Y-m-d H:i:s') . " : " . $msg . ".";
	}
	
	/**
	 * Ajoute une requ�te sql
	 * 
	 * @param $sql String
	 */
	public static function setSqlRequest($sql) {
		self::$sqlRequest[] = $sql;
	}
	
	/**
	 * Ajouter une erreur mineur de type alerte
	 * 
	 * @param $msg String
	 */
	public static function addAlertError($msg) {
		self::addMinorError($msg, "alert");
	}
	
	/**
	 * Ajouter une erreur mineur de type note
	 * 
	 * @param $msg String
	 */
	public static function addNoteError($msg) {
		self::addMinorError($msg, "note");
	}
	
	/**
	 * Ajouter une erreur informative
	 * 
	 * @param $msg String
	 */
	public static function addInfoError($msg) {
		self::addMinorError($msg, "info");
	}
	
	/**
	 * Ajoute une nouvelle erreur mineur
	 * 
	 * @param $msg String
	 * @param $type String le type d'erreur (alert / note / info)
	 */
	public static function addMinorError($msg, $type = "alert") {
		switch ($type) {
			case 'alert':
				self::$alertError[] = $msg;
				break;
			case 'note':
				self::$noteError[] = $msg;
				break;
			case 'info':
				self::$infoError[] = $msg;
				break;
			default:
				self::addMinorError($msg);
				break;
		}		
	}
	
	/**
	 * Retourne une liste contenant les erreurs mineurs
	 * 
	 * @return String
	 */
	public static function &getMinorError() {
		$display = "none";
		$rslt = "";
		$error = self::minorErrorDetected();
		if ($error) {
			$display = "block";
			$rslt .= "<ul class=\"exception\">";
			foreach(self::$alertError as $alertError) {
				$rslt .= "<li class=\"alert\"><div>" . $alertError . "</div></li>";
			}
			foreach(self::$noteError as $noteError) {
				$rslt .= "<li class=\"note\"><div>" . $noteError . "</div></li>";
			}
			foreach(self::$infoError as $infoError) {
				$rslt .= "<li class=\"info\"><div>" . $infoError . "</div></li>";
			}
			$rslt .= "</ul>";
		}
		
		// R�action diff�rente en fonction du type d'affichage demand�e
		if (Core_Main::isFullScreen()) {
			$rslt = "<div id=\"block_message\" style=\"display: " . $display . ";\">" . $rslt . "</div>";
		} else if ($error) {
			if (Core_Html::getInstance()->isJavascriptEnabled()) {
				Core_Html::getInstance()->addJavascript("displayMessage('" . addslashes($rslt) . "');");
				return null;
			}
		}
		return $rslt;
	}
	
	/**
	 * Retourne le tableau d'exception
	 * 
	 * @return array string
	 */
	public static function getException() {
		return self::$exception;
	}
	
	/**
	 * V�rifie si une exception est d�tect�
	 * 
	 * @return boolean
	 */
	public static function exceptionDetected() {
		return (!empty(self::$exception));
	}
	
	/**
	 * V�rifie si une erreur mineur est d�tect�
	 * 
	 * @return boolean
	 */
	public static function minorErrorDetected() {
		return (!empty(self::$alertError) || !empty(self::$noteError) || !empty(self::$infoError));
	}
	
	/**
	 * Capture les exceptions et les retournes en chaine de caract�re
	 * 
	 * @param $var array
	 * @return String
	 */
	private static function &linearize($var) {
		$content = "";
		foreach ($var as $msg) {
			$content .= $msg . "\n";
		}
		return $content;
	}
	
	/**
	 * Affichage des exceptions courante
	 */
	public static function displayException() {
		if (Core_Main::isFullScreen()) {
			$error = "";
			$color = "#008000";
			// V�rification de la pr�sence des exceptions
			if (self::exceptionDetected()) {
				$color = "#800000";
				$error .= "************************"
				. count(self::$exception) . " EXCEPTIONS"
				. "************************"
				. "<br />" . str_replace("\n", "<br />", self::linearize(self::$exception));
			} else {
				$error .= "No exception detected.";
			}
			
			$sql = "***********************" 
			. self::$numberOfRequest 
			. " SQL REQUESTS***********************<br />";
			if (!empty(self::$sqlRequest)) {
				$sql .= str_replace("\n", "<br />", self::linearize(self::$sqlRequest));
			} else {
				$sql .= "No sql request registred.";
			}
			
			echo "<div style=\"color: blue;\"><br />" . $sql . "</div>\n"
			. "<div style=\"color: " . $color . ";\"><br />" . $error . "</div>\n"
			. "<div style=\"color: blue;\"><br />BenchMaker :<br />\n"
			. "Core : " . Exec_Marker::getTime("core") . " seconde\n"
			. "<br />Launcher : " . Exec_Marker::getTime("launcher") . " seconde\n"
			. "<br />All : " . Exec_Marker::getTime("all") . " seconde\n"
			. "<br />Number of Sql request : " . self::$numberOfRequest . "\n"
			. "<br />Appreciation : <span style=\"color: " . (((0.4000 - Exec_Marker::getTime("all")) > 0.3) ? "green;\">OK" : "red;\">FAILED") . "</span>"
			. "</div>";
		}
	}
	
	/**
	 * Ecriture du rapport dans un fichier log
	 */
	public static function logException() {
		if (self::$writeLog && self::exceptionDetected()) {			
			// Positionne dans le cache
			Core_CacheBuffer::setSectionName("log");
			// Ecriture a la suite du cache
			Core_CacheBuffer::writingCache("exception_" . date('Y-m-d') . ".log.php", self::linearize(self::$exception), false);
		}
	}
}
?>