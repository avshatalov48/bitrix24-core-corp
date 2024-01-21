<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;
use Bitrix\ImOpenLines;

/**
 * Agent for sending notifications about the need to reconnect the VK connector.
 * Should be executed only once, after the update is installed.
 */
final class Update234000
{
	public static function sendNotification(): string
	{
		if (
			Loader::includeModule('imconnector')
			&& Connector::isConnector(Library::ID_VKGROUP_CONNECTOR)
		)
		{
			$statuses = Status::getInstanceAllLine(Library::ID_VKGROUP_CONNECTOR);
			if (!empty($statuses))
			{
				foreach ($statuses as $lineId => $status)
				{
					if ($status->isStatus())
					{
						self::sendNotificationToReconnect((int)$lineId);
					}
				}
			}
		}

		return '';
	}

	private static function sendNotificationToReconnect(int $lineId): void
	{
		if (
			!Loader::includeModule('im')
			|| !Loader::includeModule('imopenlines')
		)
		{
			return;
		}

		$linkToConnector = new Uri(Connector::getDomainDefault());
		$linkToConnector->setPath(ImOpenLines\Common::getContactCenterPublicFolder().'connector/');
		$linkToConnector->addParams([
			'ID' => Library::ID_VKGROUP_CONNECTOR,
			'LINE' => $lineId,
			'action-line' => 'create',
		]);
		$url = $linkToConnector->getLocator();

		$notificationFields = [
			'NOTIFY_TYPE' => \IM_NOTIFY_SYSTEM,
			'NOTIFY_MODULE' => 'imconnector',
			'NOTIFY_MESSAGE' => Loc::getMessage('CONNECTORS_VK_RECONNECT_NOTIFICATION', ['#HREF#' => $url])
		];

		$adminIds = self::getAdminIds();
		foreach ($adminIds as $adminId)
		{
			$notificationFields['TO_USER_ID'] = $adminId;
			\CIMNotify::Add($notificationFields);
		}
	}

	private static function getAdminIds(): array
	{
		$adminIds = [];
		if (Loader::includeModule('bitrix24'))
		{
			$adminIds = \CBitrix24::getAllAdminId();
		}
		else
		{
			$result = \CGroup::GetGroupUserEx(1);
			while ($row = $result->fetch())
			{
				$adminIds[] = (int)$row['USER_ID'];
			}
		}

		return $adminIds;
	}
}