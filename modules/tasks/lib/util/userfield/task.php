<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\UserField;

class Task extends \Bitrix\Tasks\Util\UserField
{
	public static function getEntityCode()
	{
		return 'TASKS_TASK';
	}

	/**
	 * Get system fields for this entity
	 */
	public static function getSysScheme()
	{
		return
			\Bitrix\Tasks\Integration\Disk\UserField::getSysUFScheme() +
			\Bitrix\Tasks\Integration\CRM\UserField::getSysUFScheme();
	}
}