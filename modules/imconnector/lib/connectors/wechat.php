<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Library;
use	Bitrix\Main\Config\Option;

class WeChat extends Base
{
	//Input

	//END Input

	//Output

	//END Output
	public const ENABLED_OPTION = 'wechat_enabled';

	/**
	 * Temporary method to check if WeChat can be shown for the portal, based on "wechat_enabled" option,
	 * which has been set in the updater, only if portal has active WeChat connection.
	 * Remove this method and its usage when WeChat will be available again.
	 * https://helpdesk.bitrix24.com/open/10225886/
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		if (Option::get(Library::MODULE_ID, self::ENABLED_OPTION, 'N') === 'Y')
		{
			return true;
		}

		return false;
	}
}
