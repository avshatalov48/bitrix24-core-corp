<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\Sender\Sms\Ednaru;
use Bitrix\MessageService\Sender\SmsManager;

class Messageservice
{
	/**
	 * Checks if the "Edna.ru" connector is available.
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		if (!Loader::includeModule('messageservice'))
		{
			return false;
		}

		$sender = SmsManager::getSenderById(Ednaru::ID);
		if (is_null($sender))
		{
			return false;
		}

		return $sender->isAvailable();
	}

	/**
	 * @see \Bitrix\ImConnector\Provider\Messageservice\Output::infoConnectorsLine
	 * @param int $lineId
	 * @return mixed
	 */
	public static function getChannelPhoneNumber(int $lineId)
	{
		$connectorData = \Bitrix\ImConnector\Connector::infoConnectorsLine($lineId);

		return $connectorData[\Bitrix\ImConnector\Library::ID_EDNA_WHATSAPP_CONNECTOR]['phone'];
	}

	//region Widget
	public static function getWidgetScript(): string
	{
		return Notifications::getWidgetScript();
	}

	public static function getWidgetLocalization($langId = LANGUAGE_ID): array
	{
		return Notifications::getWidgetLocalization($langId);
	}

	public static function getWhatsappLink(int $lineId, $langId = LANGUAGE_ID): string
	{
		$phoneNumber = self::getChannelPhoneNumber($lineId);

		$text = Loc::getMessage('IMCONNECTOR_MESSAGESERVICE_WHATSAPP_EDNA_DEFAULT_MESSAGE', [], $langId);

		return "https://api.whatsapp.com/send/?phone={$phoneNumber}&text=". rawurlencode($text);
	}

	public static function getWhatsappOnClick(string $url): string
	{
		$url = str_replace('https://api.whatsapp.com/', 'whatsapp://', $url);

		return "document.location.href='{$url}';event.preventDefault();";
	}

	//endregion
}