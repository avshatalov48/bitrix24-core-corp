<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * Also @see \CTasksNotifySchema
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Tasks\Util\Type;

abstract class Pull extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'pull';

	/**
	 * Adds pull event to each of the recipients
	 *
	 * @param array $recipients
	 * @param $tag
	 * @param $cmd
	 * @param $params
	 * @return bool
	 */
	public static function emitMultiple(array $recipients, $tag, $cmd, $params)
	{
		if(!static::includeModule())
		{
			return false;
		}

		$recipients = Type::normalizeArrayOfUInteger($recipients);
		if(!empty($recipients))
		{
			foreach ($recipients as $userId)
			{
				\CPullWatch::addToStack(
					str_replace('#USER_ID#', $userId, $tag),
					array(
						'module_id'  => 'tasks',
						'command'    => $cmd,
						'params'     => $params
					)
				);
			}
		}

		return true;
	}
}