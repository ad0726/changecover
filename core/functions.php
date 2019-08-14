<?php

/**
 *
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */


namespace ady\changecover\core;

class functions
{
	/** @var db_interface */
	protected $db;

	/** @var string table_prefix */
	protected $table_prefix;

	/** @var string root_path */
	protected $root_path;

	/**
	 * Constructor
	 *
	 * @param db_interface			$db
     * @param template          	$template
	 * @param string                $table_prefix
	 * @param string                $root_path
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface 	$db,
											$table_prefix,
											$root_path
    )
	{
		$this->db           = $db;
		$this->table_prefix = $table_prefix;
		$this->root_path    = $root_path;
	}

	public function coverHTML($urlRelease, $pathCover)
	{
		return "<a href='$urlRelease'><img src='/$pathCover'></a>";
	}

	public function fetchCoverToApprove()
	{
		$table  = $this->table_prefix."changecover_toapprove";
		$sql    = "SELECT * FROM $table";
		$result = $this->db->sql_query($sql);
		$rows   = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$request = [];
		foreach ($rows as $row) {
			$request[$row['section']][] = [
				'ID'    => $row['id'],
				'COVER' => self::coverHTML($row['url_release'], $row['path_cover']),
				'USER'  => self::fetchUser($row['user_id'])
			];
		}

		return $request;
	}

	public function countCoverToApprove()
	{
		$table  = $this->table_prefix."changecover_toapprove";
		$sql    = "SELECT COUNT(id) FROM $table";
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$count  = $row[0]['COUNT(id)'] ?? 0;

		return $count;
	}

	public function fetchUser($user_id)
	{
		$sql    = "SELECT username FROM ".$this->table_prefix."users WHERE user_id = '$user_id'";
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $row[0]['username'];
	}

	public function fetchAndParseForTabNews($ids)
	{
		$covers = [];

		foreach ($ids as $i=>$id) {
			$cover = self::fetchCoverApproved($id);
			$html  = self::coverHTML($cover['url_release'], $cover['path_cover']);
			$covers[$cover['section']][] = $html;
		}

		return $covers;
	}

	public function fetchCoverApproved($id)
	{
		$table  = $this->table_prefix."changecover_toapprove";
		$sql    = "SELECT section, url_release, path_cover FROM $table WHERE id = '$id'";
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if (!empty($row)) {
			$cover = $row[0];
		}

		return $cover;
	}

	public function updateTabNews($covers)
	{
		$tabnews_order = [
			"dcrebirth"     => "tabnews2_urlpage",
			"dchorsrebirth" => "tabnews_text",
			"inde"          => "tabnews4_urlpage",
			"marvel"        => "tabnews5_urlpage"
		];

		$table = $this->table_prefix."config_text";
		$begin = "<!-- BEGIN COVER -->\n";
		$end   = "\n<!-- END COVER -->";
		foreach ($covers as $section=>$cover) {
			$tab_to_update = $tabnews_order[$section];
			$sql           = "SELECT config_value FROM $table WHERE config_name = '$tab_to_update'";
			$result        = $this->db->sql_query($sql);
			$row           = $this->db->sql_fetchrow($result);
			$tabnews       = html_entity_decode($row['config_value']);
			$this->db->sql_freeresult($result);

			$first_pattern = '/(?s)<\!-- BEGIN COVER -->\s(.*)\s<\!-- END COVER -->/';
			preg_match($first_pattern, $tabnews, $first_search);

			$second_pattern = "/(?:(<a (?:(?!<\/a>).)*<\/a>)+)/";
			preg_match_all($second_pattern, $first_search[1], $tabnews_covers);

			if (isset($tabnews_covers[0])  &&
				!empty($tabnews_covers[0]) &&
				(count($tabnews_covers[0]) == 9))
			{
				$tabnews_covers  = $tabnews_covers[0];
				$covers_toremove = $tabnews_covers;
				$count_toremove  = count($cover);
				array_splice($covers_toremove, 0, 9-$count_toremove);
				array_splice($tabnews_covers, -$count_toremove, $count_toremove);
				$tabnews_covers  = array_merge($cover, $tabnews_covers);
				$tabnew_replace  = $begin.implode("\n", $tabnews_covers).$end;
				$replace         = preg_replace($first_pattern, $tabnew_replace, $tabnews);
				$new_tabnews     = [
					"config_value" => htmlentities($replace)
				];

				$sql = "UPDATE $table SET ".$this->db->sql_build_array('UPDATE', $new_tabnews)." WHERE config_name = '$tab_to_update'";

				if (!$this->db->sql_query($sql)) {
					return false;
				}

				self::removeFiles($covers_toremove, true);
			}

		}

		return true;
	}

	public function removeFiles($files, $in_html=false) {
		if (!$in_html) {
			foreach ($files as $file) {
				@unlink($this->root_path.$file);
			}
		} else {
			foreach ($files as $cover_toremove) {
				$third_pattern = "/<img src=\"(.+)\">/";
				preg_match($third_pattern, $cover_toremove, $result);

				if (isset($file[1]) && !empty($file[1])) {
					@unlink($this->root_path.$file[1]);
				}
			}
		}
	}

	public function deleteRequest($ids) {
		$table  = $this->table_prefix."changecover_toapprove";
		foreach ($ids as $i=>$id) {
			$condition = ["id" => $id];
			$sql       = "DELETE FROM $table WHERE ".$this->db->sql_build_array('DELETE', $condition);

			if (!$this->db->sql_query($sql)) {
				return false;
			}

		}

		return true;
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
		$maxsize = 5242880;
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

// Function debug for dev
// function d($data) {
// 	die(print_r($data, 1));
// }
