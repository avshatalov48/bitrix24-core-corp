<?
/**
 * Class implements all further interactions with "rest" module considering userfields for "task item" entity.
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration\Rest\Task;

use \Bitrix\Tasks\Util\UserField\Restriction;

final class UserField extends \Bitrix\Tasks\Integration\Rest\UserField
{
	public static function getTargetEntityId()
	{
		return 'TASKS_TASK';
	}

	public static function runRestMethod($executiveUserId, $methodName, array $args)
	{
		if(!Restriction::canManage(static::getTargetEntityId(), $executiveUserId))
		{
			// todo: raising an exception is bad, but still we got no error collection to return here
			throw new \Bitrix\Main\SystemException('Action not allowed');
		}

		if ($methodName == 'getlist')
		{
			if (!is_array($args) || empty($args))
			{
				$args = array(array(), array());
			}
			else
			{
				if (!is_array($args[0]))
				{
					$args[0] = array();
				}
				if (!is_array($args[1]))
				{
					$args[1] = array();
				}
			}
		}
		elseif ($methodName == 'add')
		{
			if (count($args) > 1 || !is_array($args[0]))
			{
				$args = array($args);
			}
		}

		return parent::runRestMethod($executiveUserId, $methodName, $args);
	}
}