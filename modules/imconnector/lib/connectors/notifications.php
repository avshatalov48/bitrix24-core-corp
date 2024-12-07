<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Class Notifications
 * @package Bitrix\ImConnector\Connectors
 */
class Notifications extends Base implements MessengerUrl
{
	/** @see \Bitrix\NotificationService\Model\Internal\AliasTable */
	public const CODE_LENGTH = 10;

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

	/**
	 * Generate url to redirect into messenger app.
	 *
	 * @param int $lineId
	 * @param array|string|null $additional
	 * @return array{web: string, mob: string}
	 */
	public function getMessengerUrl(int $lineId, $additional = null): array
	{
		$result = [];

		if (
			!Loader::includeModule('imconnector')
			|| !Loader::includeModule('notifications')
		)
		{
			return $result;
		}

		$phoneNumber = \Bitrix\Notifications\VirtualWhatsApp::getPhoneNumberByRegion();
		if (!$phoneNumber)
		{
			return $result;
		}

		$url = 'https://wa.me/' . $phoneNumber . '?text=';

		if (!empty($additional))
		{
			if (is_array($additional))
			{
				$additional = base64_encode(http_build_query($additional));
			}
		}

		$text = Loc::getMessage(
			'IMCONNECTOR_NOTIFICATIONS_WHATSAPP_GOTO_CHAT_MSGVER_1',
			[
				'#PORTAL_CODE#' => $additional
			]
		);

		$host = UrlManager::getInstance()->getHostUrl();
		$urlWeb = $url . urlencode($text);
		$urlMob = str_replace($url, 'whatsapp://send?phone=' . $phoneNumber . '&text=', $urlWeb);
		$result = [
			'web' => $host . \CBXShortUri::getShortUri($urlWeb),
			'mob' => $host . \CBXShortUri::getShortUri($urlMob),
		];

		return $result;
	}
}