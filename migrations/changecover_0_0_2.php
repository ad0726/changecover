<?php
/**
*
* @package phpBB Extension - Change Cover
* @copyright (c) 2019 Ady
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace ady\changecover\migrations;

class changecover_0_0_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\ady\changecover\migrations\changecover_0_0_1',
		];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'changecover_toapprove' => [
					'COLUMNS'		=> [
						'id'          => ['UINT'	 , null, 'auto_increment'],
						'section'     => ['VCHAR:15' , ''],
						'url_release' => ['VCHAR:255', ''],
						'path_cover'  => ['VCHAR:65' , ''],
						'user_id'     => ['UINT'	 , 0]
					],
					'PRIMARY_KEY'	=> 'id',
				],
			]
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'changecover_toapprove',
			],
		];
	}

	public function update_data()
	{
		return [
			// Current version
			['config.add', ['changecover_version', '0.0.2']],
			['permission.add', ['u_changecover_requester', true]],
			['permission.add', ['u_changecover_approver', true]]
		];
	}
}