<?php

/**
 *
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */


namespace ady\changecover\core;

use phpbb\auth\auth;
use phpbb\user;
use phpbb\db\driver\driver_interface as db_interface;

class functions
{
	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var db_interface */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param user					$user
	 * @param auth					$auth
	 * @param db_interface			$db
	 */
	public function __construct(
		user $user,
		auth $auth,
		db_interface $db
    )
	{
		$this->user					= $user;
		$this->auth					= $auth;
		$this->db					= $db;
	}

	/**
	 * Upload cover
	 *
	 * @return array : if return TRUE, return root of cover (string). Else return error.
	 */
	public function uploadCover($file)
	{
		// global $name_ext;
		$error = FALSE;
		// VERIF UPLOAD
		if ($file['error'] > 0) $error[1] = "Pas de cover transférée.\n";
		// VERIF WEIGHT
		$maxsize = 1048576;
		if ($file['size'] > $maxsize) $error[2] = "Le fichier est trop gros.\n";
		// VERIF EXTENSION
		$img_ext_ok = ['jpg', 'jpeg', 'png'];
		$ext_upload = strtolower(  substr(  strrchr($file['name'], '.')  ,1)  );
		if ( !in_array($ext_upload, $img_ext_ok) ) $error[3] = "Extension incorrecte.\n";
		// AFFICHAGE DE L'ERREUR OU ENVOI
		if (!empty($error)) {
			return (isset($error[1])) ? [FALSE, $error[1]] : [FALSE, @$error];
		} else {
		// SAUVEGARDE DE L'IMAGE SUR LE FTP
			$image      = self::ResizeCover($file['tmp_name'], "W", 100);
			$name       = md5(uniqid(rand(), true));
			$ext_upload = strtolower(  substr(  strrchr($file['name'], '.')  ,1)  );
			$name_ext   = "ext/ady/changecover/store/{$name}.{$ext_upload}";
			$resultat   = imagejpeg($image, $name_ext, 70);
			if (!$resultat) {
				return [FALSE, "Transfert échoué.\n"];
			} else {
				imagedestroy($image);
				return [TRUE, $name_ext];
			}
		}
	}

	/**
	 * Resize cover
	 * http://memo-web.fr/categorie-php-197.php
	 *
	 * @param resource $source
	 * @param string $type_value
	 * @param integer $new_value
	 * @return resource
	 */
	public function ResizeCover($source, $type_value = "W", $new_value)
	{
		// Récupération des dimensions de l'image
		if( !( list($source_largeur, $source_hauteur) = @getimagesize($source) ) ) {
		return false;
		}
		
		// Calcul de la valeur dynamique en fonction des dimensions actuelles
		// de l'image et de la dimension fixe que nous avons précisée en argument.
		if( $type_value == "H" ) {
		$nouv_hauteur = $new_value;
		$nouv_largeur = ($new_value / $source_hauteur) * $source_largeur;
		} else {
		$nouv_largeur = $new_value;
		$nouv_hauteur = ($new_value / $source_largeur) * $source_hauteur;
		}
		
		// Création du conteneur.
		$image = imagecreatetruecolor($nouv_largeur, $nouv_hauteur);
		
		// Importation de l'image source.
		$source_image = imagecreatefromstring(file_get_contents($source));
		
		// Copie de l'image dans le nouveau conteneur en la rééchantillonant.
		imagecopyresampled($image, $source_image, 0, 0, 0, 0, $nouv_largeur, $nouv_hauteur, $source_largeur, $source_hauteur);
		
		// Libération de la mémoire allouée aux deux images (sources et nouvelle).
		imagedestroy($source_image);
	
		return $image;
	}
}

function d($data) {
	die(print_r($data, 1));
}