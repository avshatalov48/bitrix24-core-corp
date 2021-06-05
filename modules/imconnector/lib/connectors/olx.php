<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class Olx extends Base
{
	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$result = parent::processingInputNewMessage($message, $line);

		if($result->isSuccess())
		{
			$message = $result->getResult();

			$status = Status::getInstance($this->idConnector, $line);

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
				Status::save();
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
			&& Connector::isConnector(Library::ID_OLX_CONNECTOR, true)
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