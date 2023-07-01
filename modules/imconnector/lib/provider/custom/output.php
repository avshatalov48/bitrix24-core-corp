<?php
namespace Bitrix\ImConnector\Provider\Custom;

use Bitrix\ImConnector\Rest,
	Bitrix\ImConnector\Result,
	Bitrix\ImConnector\Status,
	Bitrix\ImConnector\Library,
	Bitrix\ImConnector\Provider\Base;
use Bitrix\Main\Event,
	Bitrix\Main\EventResult;

class Output extends Base\Output
{
	/**
	 * Receive information about all the connected connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function infoConnectorsLine($lineId): Result
	{
		$result = clone $this->result;
		$resultCustom = [];

		if($result->isSuccess())
		{
			Status::getInstanceAllConnector($lineId);

			$event = new Event(Library::MODULE_ID, Library::EVENT_INFO_LINE_CUSTOM_CONNECTOR, ['LINE_ID' => $lineId]);
			$event->send();

			foreach ($event->getResults() as $eventResult)
			{
				if (
					$eventResult != EventResult::ERROR
					&& ($params = $eventResult->getParameters())
				)
				{
					$connectorStatus = Status::getInstance($params['connector_id'], (int)$lineId);

					if(
						!empty($params['connector_id']) &&
						$connectorStatus->isStatus()
					)
					{
						if(!empty($params['id']))
						{
							$resultCustom[$params['connector_id']]['id'] = $params['id'];
						}
						if(!empty($params['url']))
						{
							$resultCustom[$params['connector_id']]['url'] = $params['url'];
						}
						if(!empty($params['url_im']))
						{
							$resultCustom[$params['connector_id']]['url_im'] = $params['url_im'];
						}
						if(!empty($params['name']))
						{
							$resultCustom[$params['connector_id']]['name'] = $params['name'];
						}
						if(!empty($params['picture']['url']))
						{
							$resultCustom[$params['connector_id']]['picture']['url'] = $params['picture']['url'];
						}
					}
				}
			}

			//rest
			$restConnectors = Rest\Helper::listRestConnector();

			foreach ($restConnectors as $restConnector)
			{
				$connectorStatus = Status::getInstance($restConnector['ID'], (int)$lineId);

				if ($connectorStatus->isStatus())
				{
					$restConnectorData = $connectorStatus->getData();

					if (!empty($restConnectorData) && is_array($restConnectorData))
					{
						if(!empty($restConnectorData['id']))
						{
							$resultCustom[$restConnector['ID']]['id'] = $restConnectorData['id'];
						}
						if(!empty($restConnectorData['url']))
						{
							$resultCustom[$restConnector['ID']]['url'] = $restConnectorData['url'];
						}
						if(!empty($restConnectorData['url_im']))
						{
							$resultCustom[$restConnector['ID']]['url_im'] = $restConnectorData['url_im'];
						}
						if(!empty($restConnectorData['name']))
						{
							$resultCustom[$restConnector['ID']]['name'] = $restConnectorData['name'];
						}
					}
				}
			}
		}
		$result->setData($resultCustom);

		return $result;
	}

	/**
	 * @param array $messages
	 * @return mixed
	 */
	protected function sendMessagesProcessing(array $messages): array
	{
		$oldMessages = $messages;

		$messages = parent::sendMessagesProcessing($messages);

		foreach ($messages as $cell=>$message)
		{
			if(isset($oldMessages[$cell]['message']['attachments']))
			{
				$messages[$cell]['attachments_old'] = $oldMessages[$cell]['message']['attachments'];
			}
		}

		return $messages;
	}
}