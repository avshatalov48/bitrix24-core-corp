<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

/**
 * Class Notifications
 * @package Bitrix\ImConnector\Connectors
 */
class Notifications extends Base
{

	//Input
	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (isset($chat['last_message']) && $chat['last_message'] !== '')
		{
			$chat['description'] = Loc::getMessage('IMCONNECTOR_NOTIFICATIONS_ADDITIONAL_DATA', [
				'#TEXT#' => $chat['last_message'],
			]);

			unset($chat['last_message']);
		}

		return $chat;
	}
	//END Input
}