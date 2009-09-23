<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Constantes pour les noms des tables de base de donn�e
 * Utilisabe via Core_Table::MaTable
 * 
 * @author S�bastien Villemain
 */
class Core_Table {
	
	/**
	 * Prefix de chaque table
	 */ 
	private static $prefix = "";
	
	// Nom des tables
	public static $BANNED_TABLE = "banned";
	public static $BLOCKS_TABLE = "blocks";
	public static $CONFIG_TABLE = "configs";
	public static $MENUS_TABLES = "menus";
	public static $MODULES_TABLE = "modules";
	public static $USERS_TABLE = "users";
	public static $USERS_ADMIN_TABLE = "users_admin";
	
	private static $tables = array(
		"CONFIG_TABLE", "USERS_TABLE",
		"BANNED_TABLE", "BLOCKS_TABLE",
		"MODULES_TABLE", "USERS_ADMIN_TABLE",
		"MENUS_TABLES"
	);
	
	/**
	 * Ajoute le prefixe pour chaque table
	 * 
	 * @param $prefix
	 */
	public static function setPrefix($prefix) {
		// Aucun pr�fixe n'a �t� renseign�
		if (empty(self::$prefix) && !empty($prefix)) {
			self::$prefix = $prefix;
			// Application du pr�fixe
			foreach (self::$tables as $value) {
				self::$$value = $prefix . "_" . self::$$value;
			}
		}
	}
}
?>