<?php
/**
*
* @package phpBB Extension - Change Cover
* @copyright (c) 2019 Ady
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace ady\changecover\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\event\data;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return [
			'core.permissions' => 'permissions',
			'core.page_header' => 'add_page_header_link',
		];
	}

	/**
	* Constructor
	*/
	public function __construct(
		\ady\changecover\controller\main $ady_controller
		)
		{
			$this->ady_controller = $ady_controller;
		}

	/**
	 * @param data $event
	 */
	public function permissions(data $event)
	{
		$permission_categories = [
			'misc' => [
				'u_changecover_requester',
				'u_changecover_approver',
			]
		];

		$changecover_permissions = [];

		foreach ($permission_categories as $cat => $permissions)
		{
			foreach ($permissions as $permission)
			{
				$changecover_permissions[$permission] = [
					'lang'	=> 'ACL_' . strtoupper($permission),
					'cat'	=> $cat,
				];
			}
		}

		$event['permissions'] = array_merge($event['permissions'], $changecover_permissions);
	}

	/**
	 * Create a URL to the mchat controller file for the header linklist
	 */
	public function add_page_header_link()
	{
		$this->ady_controller->render_page_header_link();
	}
}