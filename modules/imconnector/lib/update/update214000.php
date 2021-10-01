<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Status;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class Update214000
{
	/**
	 * Agent for sending notifications about the need to reconnect the OLX connector.
	 * Should be executed only once, after the update is installed.
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function sendNotificationOnceAgent(): void
	{
		if (
			\Bitrix\Main\Loader::includeModule('imconnector')
			&& Connector::isConnector(Library::ID_OLX_CONNECTOR, true)
		)
		{
			$statuses = Status::getInstanceAllLine(Library::ID_OLX_CONNECTOR);
			if (!empty($statuses))
			{
				foreach ($statuses as $lineId => $status)
				{
					if ($status->isStatus())
					{
						\Bitrix\ImConnector\Connectors\Olx::sendNotificationToRenewToken($lineId);
					}
				}
			}
		}
	}
}