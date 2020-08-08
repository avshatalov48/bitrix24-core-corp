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

namespace Bitrix\Tasks\Item\Task\Template\Field;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class Access extends \Bitrix\Tasks\Item\Field\Collection\Item
{
	protected static function getItemClass()
	{
		return \Bitrix\Tasks\Item\Task\Template\Access::getClass();
	}

	public function hasDefaultValue($key, $item)
	{
		return false;
	}

	/**
	 * @param String $key
	 * @param \Bitrix\Tasks\Item $item
	 * @return array|mixed
	 */
	public function getDefaultValue($key, $item)
	{
		return [];
	}

	/**
	 * @param mixed $value
	 * @param string $key
	 * @param \Bitrix\Tasks\Item $item
	 * @param array $parameters
	 * @return bool
	 */
	public function checkValue($value, $key, $item, array $parameters = array())
	{
		return parent::checkValue($value, $key, $item, $parameters);
	}

	private static function getAccessLevelFullId()
	{
		$level = \Bitrix\Tasks\Util\User::getAccessLevel('task_template', 'full');

		return $level['ID'];
	}
}