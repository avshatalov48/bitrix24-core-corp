<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Template\Collection;

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Result;

class Access extends \Bitrix\Tasks\Item\Collection
{
	/**
	 * @return Item
	 */
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\Template\Access::getClass();
	}

	public function grantAccessLevel($groupCode, $level)
	{
		$groupCode = trim((string) $groupCode);
		$level = trim((string) $level);

		if(!is_numeric($level))
		{
			$level = \Bitrix\Tasks\Util\User::getAccessLevel('TASK_TEMPLATE', $level);
			if($level)
			{
				$level = $level['ID'];
			}
		}

		$level = intval($level);
		if($level)
		{
			$className = static::getItemClass();

			// todo: here we do not have reference to a parent object. we must have it, because we need to forward userId
			$member = new $className(array(
				'TASK_ID' => $level,
				'GROUP_CODE' => $groupCode,
			));

			// todo: implement ->push() here, instead of this
			$this->push($member);
		}
	}

	public function revokeAll()
	{
		$this->values = array();
		$this->onChange();
	}

	public function revokeAccessLevel($groupCode, $level)
	{
		throw new NotImplementedException();
	}
}