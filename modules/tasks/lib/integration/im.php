<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

abstract class IM extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'im';

	public static function notifyAdd($message)
	{
		if(!static::includeModule())
		{
			return false;
		}

		return \CIMNotify::Add($message);
	}
}