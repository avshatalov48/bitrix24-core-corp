<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;
use Bitrix\Main\Web\Uri;

class Olx extends Base
{
	//Input
	/**
	 * @param array $message
	 * @param int $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$result = parent::processingInputNewMessage($message, $line);

		if($result->isSuccess())
		{
			$message = $result->getResult();

			$status = Status::getInstance($this->idConnector, (int)$line);

			if(!($data = $status->getData()))
			{
				$data = [];
			}
			$dataOld = $data;

			if(!empty($message['chat']['id']))
			{
				if(
					empty($data[$message['chat']['id']]['last_message_id'])
					|| $data[$message['chat']['id']]['last_message_id'] !== (int)$message['message']['id']
				)
				{
					if ((int)$message['message']['id'] > $data[$message['chat']['id']]['last_message_id'])
					{
						$data[$message['chat']['id']]['last_message_id'] = (int)$message['message']['id'];
						$data[$message['chat']['id']]['total_count'] = (int)$message['message']['total_count'];
					}
				}
			}

			if(!empty($data) && $dataOld!==$data)
			{
				$status->setData($data);
				$status->save();
			}

			if (!Library::isEmpty($message['message']['attach']))
			{
				$fileAttach = [
					'BLOCKS' => $message['message']['attach']
				];
				$message['message']['attach'] = $fileAttach;
				$message['message']['attach']['BLOCKS'][]['MESSAGE'] = Loc::getMessage('CONNECTORS_OLX_ATTACHMENTS_NOTIFY_MESSAGE');
			}

			$result->setResult($message);
		}

		return $result;
	}
	//END Input

	//Tools
	/**
	 * @return string
	 */
	public static function initializeReceiveMessages(): string
	{
		if (
			Loader::includeModule('imconnector')
			&& Connector::isConnector(Library::ID_OLX_CONNECTOR)
		)
		{
			$statuses = Status::getInstanceAllLine(Library::ID_OLX_CONNECTOR);
			if (!empty($statuses))
			{
				$isAnyConnected = false;
				foreach ($statuses as $lineId => $status)
				{
					$needNotification = false;
					if ($status->isStatus())
					{
						$isAnyConnected = true;
						$connectorOutput = new Output(Library::ID_OLX_CONNECTOR, $lineId);

						$responseResult = $connectorOutput->initializeReceiveMessages($status->getData());
						if ($responseResult->isSuccess())
						{
							$responseResultData = $responseResult->getData();

							if (
								isset($responseResultData['need_notification'])
								&& $responseResultData['need_notification'] === 'Y'
							)
							{
								$needNotification = true;
							}
						}

						if ($needNotification)
						{
							self::sendNotificationToRenewToken($lineId);
						}
					}
				}
				if (!$isAnyConnected)
				{
					return '';
				}
			}
			else
			{
				return '';
			}
		}

		return __METHOD__. '();';
	}

	public static function sendNotificationToRenewToken(int $lineId): void
	{
		if (!Loader::includeModule('im') || !Loader::includeModule('imopenlines'))
		{
			return;
		}

		$linkToConnector = new Uri(\Bitrix\ImConnector\Connector::getDomainDefault());
		$linkToConnector->setPath(\Bitrix\ImOpenLines\Common::getContactCenterPublicFolder().'connector/');
		$linkToConnector->addParams([
			'ID' => \Bitrix\ImConnector\Library::ID_OLX_CONNECTOR,
			'LINE' => $lineId,
			'action-line' => 'create',
		]);
		$url = $linkToConnector->getLocator();

		$notificationFields = [
			'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
			'NOTIFY_MODULE' => 'imconnector',
			'NOTIFY_MESSAGE' => Loc::getMessage('CONNECTORS_OLX_RECONNECT_REMINDER_NOTIFICATION', [
				'#LINK_START#' => '[URL='.$url.']',
				'#LINK_END#' => '[/URL]'
			])
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

	public static function addAgent(): void
	{
		$agentName = __CLASS__. '::initializeReceiveMessages();';
		$agent = \CAgent::getList(
			[],
			[
				'MODULE_ID' => Library::MODULE_ID,
				'=NAME' => $agentName
			]
		)->fetch();

		if (!$agent)
		{
			\CAgent::AddAgent(
				$agentName,
				Library::MODULE_ID,
				'N',
				60,
				'',
				'Y',
				ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 60, 'FULL')
			);
		}
	}
}