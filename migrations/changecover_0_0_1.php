<?php
/**
*
* @package phpBB Extension - Change Cover
* @copyright (c) 2019 Ady
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace ady\changecover\migrations;

class changecover_0_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
		);
	}

	public function revert_schema()
	{
		return array(
		);
	}

	public function update_data()
	{
		return array(
			// Current version
			array('config.add', array('changecover_version', '0.0.1')),
		);
	}
}