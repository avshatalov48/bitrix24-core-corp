<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Olx extends Base
{
	public static function newMessageProcessing($message, $connector, $line): array
	{
		if ($connector === Library::ID_OLX_CONNECTOR)
		{
			$status = Status::getInstance($connector, $line);

			if(!($data = $status->getData()))
			{
				$data = array();
			}
			$dataOld = $data;

			if(!empty($message['chat']['id']))
			{
				if(empty($data[$message['chat']['id']]['last_message_id']) || $data[$message['chat']['id']]['last_message_id'] !== (int)$message['message']['id'])
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
				Status::save();
			}
		}

		if (!Library::isEmpty($message['message']['attach']))
		{
			$fileAttach = [
				'BLOCKS' => $message['message']['attach']
			];
			$message['message']['attach'] = $fileAttach;
			$message['message']['attach']['BLOCKS'][]['MESSAGE'] = Loc::getMessage('CONNECTORS_OLX_ATTACHMENTS_NOTIFY_MESSAGE');
		}

		return $message;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function initializeReceiveMessages(): string
	{
		if (Loader::includeModule('imconnector') &&
			Connector::isConnector(Library::ID_OLX_CONNECTOR, true)
		)
		{
			$statuses = Status::getInstanceAllLine(Library::ID_OLX_CONNECTOR);
			if(!empty($statuses))
			{
				foreach ($statuses as $line => $status)
				{
					if ($status->isStatus())
					{
						$connectorOutput = new Output(Library::ID_OLX_CONNECTOR, $line);

						$connectorOutput->initializeReceiveMessages($status->getData());
					}
				}
			}
		}

		return '\Bitrix\ImConnector\Connectors\OLX::initializeReceiveMessages();';
	}
}