<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Constructeur de fichier
 * 
 * @author Sebastien Villemain
 *
 */
class Exec_FileBuilder {
	
	/**
	 * G�n�re un nouveau fichier de configuration g�n�rale
	 * 
	 * @param $TR_ENGINE_MAIL String
	 * @param $TR_ENGINE_STATUT String
	 * @param $cacheTimeLimit int
	 * @param $cookiePrefix String
	 * @param $cryptKey String
	 */
	public static function buildConfigFile($TR_ENGINE_MAIL, $TR_ENGINE_STATUT, $cacheTimeLimit, $cookiePrefix, $cryptKey) {
		// V�rification du mail
		if (empty($TR_ENGINE_MAIL) && define(TR_ENGINE_MAIL)) $TR_ENGINE_MAIL = TR_ENGINE_MAIL;
		
		// V�rification de la dur�e du cache
		if (!is_int($cacheTimeLimit) || $cacheTimeLimit < 0) $cacheTimeLimit = 7;
		
		$content = "<?php \n"
		. "// ----------------------------------------------------------------------- //\n"
		. "// Generals informations\n"
		. "//\n"
		. "// Webmaster mail\n"
		. "$" . "config['TR_ENGINE_MAIL'] = \"" . $TR_ENGINE_MAIL . "\";\n"
		. "//\n"
		. "// Site states (open | close)\n"
		. "$" . "config['TR_ENGINE_STATUT'] = \"" . (($TR_ENGINE_STATUT == "close") ? "close" : "open") . "\";\n"
		. "// ----------------------------------------------------------------------- //\n"
		. "// Sessions settings\n"
		. "// Duration in days of validity of session files caching\n"
		. "$" . "config['cacheTimeLimit'] = " . $cacheTimeLimit . ";\n"
		. "//\n"
		. "// Prefix the names of cookies\n"
		. "$" . "config['cookiePrefix'] = \"" . $cookiePrefix . "\";\n"
		. "//\n"
		. "// Unique decryption key (generated randomly during installation)\n"
		. "$" . "config['cryptKey'] = \"" . $cryptKey . "\";\n"
		. "// -------------------------------------------------------------------------//\n"
		. "?>\n";
		
		if (Core_Loader::isCallable("Core_CacheBuffer")) {
			Core_CacheBuffer::setSectionName("configs");
			Core_CacheBuffer::writingCache("configs.inc.php", $content, true);
		}
	}
	
	/**
	 * G�n�re un nouveau fichier de configuration FTP 
	 * 
	 * @param $host String
	 * @param $port int
	 * @param $user String
	 * @param $pass String
	 * @param $root String
	 * @param $type String
	 */
	public static function buildFtpFile($host, $port, $user, $pass, $root, $type) {
		// V�rification du port
		if (!is_int($port)) $port = 21;
		
		// V�rification du mode de ftp
		if (Core_Loader::isCallable("Core_CacheBuffer")) {
			$mode = Core_CacheBuffer::getModeActived();
			$type = (isset($mode[$type])) ? $type : "";
		}
		
		$content = "<?php \n"
		. "// -------------------------------------------------------------------------//\n"
		. "// FTP settings\n"
		. "//\n"
		. "// Address of the FTP host\n"
		. "$" . "ftp['host'] = \"" . $host . "\";\n"
		. "//\n"
		. "// Port number of host\n"
		. "$" . "ftp['port'] = " . $port . ";\n"
		. "//\n"
		. "// Username FTP\n"
		. "$" . "ftp['user'] = \"" . $user . "\";\n"
		. "//\n"
		. "// Password User FTP\n"
		. "$" . "ftp['pass'] = \"" . $pass . "\";\n"
		. "//\n"
		. "// FTP Root Path\n"
		. "$" . "ftp['root'] = \"" . $root . "\";\n"
		. "//\n"
		. "// FTP mode (native = php | ftp | sftp)\n"
		. "$" . "ftp['type'] = \"" . $type . "\";\n"
		. "// -------------------------------------------------------------------------//\n"
		. "?>\n";
		
		if (Core_Loader::isCallable("Core_CacheBuffer")) {
			Core_CacheBuffer::setSectionName("configs");
			Core_CacheBuffer::writingCache("ftp.inc.php", $content, true);
		}
	}
	
	/**
	 * G�n�re un nouveau fichier de configuration pour la base de donn�e 
	 * 
	 * @param $host String
	 * @param $user String
	 * @param $pass String
	 * @param $name String
	 * @param $type String
	 * @param $prefix String
	 */
	public static function buildDatabaseFile($host, $user, $pass, $name, $type, $prefix) {
		// V�rification du type de base de donn�e
		if (Core_Loader::isCallable("Core_Sql")) {
			$bases = Core_Sql::listBases();
			$type = (isset($bases[$type])) ? $type : "mysql";
		}
		
		$content = "<?php \n"
		. "// -------------------------------------------------------------------------//\n"
		. "// Database settings\n"
		. "//\n"
		. "// Address of the host base\n"
		. "$" . "db['host'] = \"" . $host . "\";\n"
		. "//\n"
		. "// Name of database user\n"
		. "$" . "db['user'] = \"" . $user . "\";\n"
		. "//\n"
		. "// Password of the database\n"
		. "$" . "db['pass'] = \"" . $pass . "\";\n"
		. "//\n"
		. "// Database name\n"
		. "$" . "db['name'] = \"" . $name . "\";\n"
		. "//\n"
		. "// Database type\n"
		. "$" . "db['type'] = \"" . $type . "\";\n"
		. "//\n"
		. "// Prefix tables\n"
		. "$" . "db['prefix'] = \"" . $prefix . "\";\n"
		. "// -------------------------------------------------------------------------//\n"
		. "?>\n";
		
		if (Core_Loader::isCallable("Core_CacheBuffer")) {
			Core_CacheBuffer::setSectionName("configs");
			Core_CacheBuffer::writingCache("database.inc.php", $content, true);
		}
	}
}

?>