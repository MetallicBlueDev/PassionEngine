<?php 
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

// Chargement du parent
Core_Loader::classLoader("Base_Mysql");

/**
 * Gestionnaire de la communication SQL
 * 
 * @author S�bastien Villemain
 * 
 */
class Base_Mysqli extends Base_Mysql {
	
}

?>