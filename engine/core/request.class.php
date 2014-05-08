<?php

/**
 * Gestionnaire de requ�tes URL
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Request {
	
	/**
	 * Tableau buffer des requ�tes
	 * 
	 * @var array
	 */
	private static $buffer = array();
	
	/**
	 * R�cup�re, analyse et v�rifie une variable URL
	 * 
	 * @param $name String Nom de la variable
	 * @param $type String Type de donn�e
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return mixed
	 */
	public static function &getVars($name, $type, $default = "", $hash = "default") {
		$var = $name;
		if (isset(self::$buffer[$var])) {
			return self::$buffer[$var];
		} else {
			// Recherche de la m�thode courante
			$input = self::getRequest($hash);
			
			if (isset($input[$name]) && $input[$name] !== null) {
				$rslt = self::protect($input[$name], $type);
				self::$buffer[$var] = $rslt;
				return $rslt;
			}
			return $default;
		}
	}
	
	/**
	 * Retourne la variable demand�e de type int
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return int
	 */
	public static function &getInt($name, $default = 0, $hash = "default") {
		return self::getVars($name, "INT", $default, $hash);
	}
	
	/**
	 * Retourne la variable demand�e de type float
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return float
	 */
	public static function &getFloat($name, $default = 0.0, $hash = "default") {
		return self::getVars($name, "FLOAT", $default, $hash);
	}
	
	/**
	 * Retourne la variable demand�e de type double
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return double
	 */
	public static function &getDouble($name, $default = 0.0, $hash = "default") {
		return self::getVars($name, "DOUBLE", $default, $hash);
	}
	
	/**
	 * Retourne la variable demand�e de type String valide base64
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return String
	 */
	public static function &getBase64($name, $default = "", $hash = "default") {
		return self::getVars($name, "BASE64", $default, $hash);
	}
	
	/**
	 * Retourne la variable demand�e de type String
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return String
	 */
	public static function &getWord($name, $default = "", $hash = "default") {
		return self::getVars($name, "WORD", $default, $hash);
	}
	
	/**
	 * Retourne la variable demand�e de type String
	 * 
	 * @param $name String Nom de la variable
	 * @param $default mixed Donn�e par d�faut
	 * @param $hash String Provenance de la variable
	 * @return String
	 */
	public static function &getString($name, $default = "", $hash = "default") {
		return self::getVars($name, "STRING", $default, $hash);
	}
	
	/**
	 * Retourne le contenu de la requ�te demand�e
	 * 
	 * @param $hash String
	 * @return array
	 */
	public static function &getRequest($hash = "default") {
		$hash = strtoupper($hash);
		$input = "";
		switch($hash) {
			case 'GET': $input = &$_GET; break;
			case 'POST': $input = &$_POST; break;
			case 'FILES': $input = &$_FILES; break;
			case 'COOKIE': $input = &$_COOKIE; break;
			case 'ENV': $input = &$_ENV; break;
			case 'SERVER': $input = &$_SERVER; break;
			default: $input = &self::getRequest($_SERVER['REQUEST_METHOD']);
		}
		return $input;
	}
	
	/**
	 * V�rifie le contenu des donn�es import�es
	 * 
	 * @param $content mixed
	 * @param $type String
	 * @return mixed
	 */
	public static function &protect($content, $type) {
		$type = strtoupper($type);
		
		switch($type) {
			case 'INT':
			case 'INTEGER':
				preg_match('/-?[0-9]+/', (string) $content, $matches);
				$content = @(int) $matches[0];
				break;
				
			case 'FLOAT':
			case 'DOUBLE':
				preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $content, $matches);
				$content = @(float) $matches[0];
				break;
			
			case 'BOOL':
			case 'BOOLEAN':
				$content = (string) $content;
				$content = ($content == "1" || $content == "true") ? "1" : "0";
				$content = (bool) $content;
				break;
			
			case 'BASE64':
				$content = (string) preg_replace( '/[^A-Z0-9\/+=]/i', '', $content);
				break;
				
			case 'WORD':
				$content = (string) preg_replace( '/[^A-Z_]/i', '', $content);
				$content = self::protect($content, "STRING");
				break;
				
			case 'STRING':
				$content = trim($content);
				if (preg_match('/(\.\.|http:|ftp:)/', $content)) {
					$content = "";
				}
				break;
			default:
				Core_Exception::setException("Core_Request : data type unknown");
				$content = self::protect($content, "STRING");				
				break;			
		}
		return $content;
	}
}


?>