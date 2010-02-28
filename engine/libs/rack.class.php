<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de tableau automatique
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Rack {
	
	/**
	 * Premi�re ligne d�finissant les colonnes
	 * 
	 * @var array
	 */
	private $firstLine = array();
	
	/**
	 * Lignes de donn�es du tableau
	 * 
	 * @var array
	 */
	private $lines = array();
	
	/**
	 * Nouveau tableau
	 * 
	 * @param $startLine array('size in %', 'my title')
	 */
	public function __construct($startLine = array()) {
		$this->firstLine = &$startLine;
	}
	
	/**
	 * Ajoute une colonne
	 * 
	 * @param $size int taille en pourcentage
	 * @param $title String titre de la colonne
	 */
	public function addColumn($size, $title = "") {
		$firstLine[] = array($size, $title);
	}
	
	/**
	 * Ajoute une ligne au tableau
	 * 
	 * @param $line array tableau contenant dans l'ordre toutes les colonnes de la ligne
	 */
	public function addLine($line) {
		$this->lines[] = $line;
	}
	
	/**
	 * Retourne le rendu du rack complet
	 * 
	 * @param $class String
	 * @return String
	 */
	public function &render($class = "") {
		$content = "<table class=\"" . ((!empty($class)) ? $class : "table") . "\">"
		. "<tbody><tr class=\"first\">";
		// Cr�ation de la 1�re lignes
		foreach($this->firstLine as $column) {
			$content .= "<td style=\"width: " . $column[0] . "%;\">" . $column[1] . "</td>";
		}
		$content .= "</tr>";
		// Cr�ation de toutes les lignes
		foreach($this->lines as $line) {
			$content .= "<tr>";
			foreach($line as $column) {
				$content .= "<td>" . $column . "</td>";
			}
			$content .= "</tr>";
		}
		$content .= "</tbody></table>";
		return $content;
	}
	
}


?>