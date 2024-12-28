<?php
/**
 * Class implements all further interactions with "rest" module considering userfields for "task item" entity.
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration\Rest\Task;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Util\UserField\Restriction;

final class UserField extends \Bitrix\Tasks\Integration\Rest\UserField
{
	public static function getTargetEntityId()
	{
		return 'TASKS_TASK';
	}

	public static function runRestMethod($executiveUserId, $methodName, array $args)
	{
		if (!Restriction::canManage(static::getTargetEntityId(), $executiveUserId))
		{
			// todo: raising an exception is bad, but still we got no error collection to return here
			throw new SystemException('Action not allowed');
		}

		switch (mb_strtolower($methodName))
		{
			case 'gettypes':
				$args = [];
				break;

			case 'getlist':
				if (empty($args))
				{
					$args = [[], []];
				}
				else
				{
					if (!is_array($args[0]))
					{
						$args[0] = [];
					}
					if (!is_array($args[1]))
					{
						$args[1] = [];
					}
				}
				break;

			case 'get':
				if (empty($args))
				{
					throw new ArgumentException('No parameters found.');
				}
				break;

			case 'add':
				if (count($args) > 1 || !is_array($args[0]))
				{
					$args = [$args];
				}
				break;
		}

		return parent::runRestMethod($executiveUserId, $methodName, $args);
	}
}