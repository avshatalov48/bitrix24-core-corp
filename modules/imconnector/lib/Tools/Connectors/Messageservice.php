<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\MessageService\Sender\SmsManager;

class Messageservice
{
	private const MESSAGESERVICE_SENDER_ID = 'ednaru';

	/**
	 * Checks if the "Edna.ru" connector is available.
	 *
	 * @return bool
	 * @throws LoaderException
	 */
	public function isEnabled(): bool
	{
		if (!Loader::includeModule('messageservice'))
		{
			return false;
		}

		$sender = SmsManager::getSenderById(self::MESSAGESERVICE_SENDER_ID);
		if (is_null($sender))
		{
			return false;
		}

		return $sender->isAvailable();
	}
}