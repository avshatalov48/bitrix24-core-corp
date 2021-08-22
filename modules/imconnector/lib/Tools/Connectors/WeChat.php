<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use	Bitrix\Main\Config\Option;

use Bitrix\ImConnector\Library;

class WeChat
{
	public const ENABLED_OPTION = 'wechat_enabled';

	/**
	 * Temporary method to check if WeChat can be shown for the portal, based on "wechat_enabled" option,
	 * which has been set in the updater, only if portal has active WeChat connection.
	 * Remove this method and its usage when WeChat will be available again.
	 * https://helpdesk.bitrix24.com/open/10225886/
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return Option::get(Library::MODULE_ID, self::ENABLED_OPTION, 'N') === 'Y';
	}
}