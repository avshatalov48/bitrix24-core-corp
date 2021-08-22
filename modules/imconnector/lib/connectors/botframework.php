<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Model\BotFrameworkTable;

/**
 * @deprecated
 *
 * Class BotFramework
 * @package Bitrix\ImConnector\Connectors
 */
class BotFramework extends Base
{
	//Input
	/**
	 * @param $message
	 * @param $userSourceData
	 * @param $connector
	 */
	protected static function processingInputNewAndUpdateMessageForBotFramework($message, $userSourceData, $connector): void
	{
		$dataBotFramework = [];

		if(!empty($message['recipient']))
		{
			$dataBotFramework['from'] = $message['recipient'];
			unset($message['recipient']);
		}

		if(!empty($message['service_url']))
		{
			$dataBotFramework['service_url'] = $message['service_url'];
			unset($message['service_url']);
		}

		//If the channel is kik
		if($connector == Library::ID_REAL_BOT_FRAMEWORK_KIK_CONNECTOR)
		{
			$dataBotFramework['recipient']['id'] = $userSourceData['id'];

			if(Library::isEmpty($userSourceData['name']))
			{
				$dataBotFramework['recipient']['name'] = '';
			}
			else
			{
				$dataBotFramework['recipient']['name'] = $userSourceData['name'];
			}
		}

		if(!empty($dataBotFramework))
		{
			$rawBotFramework = BotFrameworkTable::getList(
				[
					'select'  => [
						'ID',
						'DATA'
					],
					'filter'  => [
						'VIRTUAL_CONNECTOR' => $connector,
						'ID_CHAT' => $message['chat']['id']
					]
				]
			);

			if ($rowBotFramework = $rawBotFramework->fetch())
			{
				if(array_diff($dataBotFramework, $rowBotFramework['DATA']))
				{
					BotFrameworkTable::update($rowBotFramework['ID'], [
						'ID_MESSAGE' => $message['message']['id'],
						'DATA' => $dataBotFramework
					]);
				}
			}
			else
			{
				BotFrameworkTable::add([
					'ID_CHAT' => $message['chat']['id'],
					'ID_MESSAGE' => $message['message']['id'],
					'VIRTUAL_CONNECTOR' => $connector,
					'DATA' => $dataBotFramework
				]);
			}
		}
	}

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
			self::processingInputNewAndUpdateMessageForBotFramework($result->getResult(), $message['user'], $this->idConnector);
		}

		return $result;
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputUpdateMessage($message, $line): Result
	{
		$result = parent::processingInputUpdateMessage($message, $line);
		if($result->isSuccess())
		{
			self::processingInputNewAndUpdateMessageForBotFramework($result->getResult(), $message['user'], $this->idConnector);
		}

		return $result;
	}
	//END Input

	//Output
	/**
	 * @param array $message
	 * @return array
	 */
	protected function messageProcessing(array $message): array
	{
		if(!empty($message['chat']['id']))
		{
			$rawBotFramework = BotFrameworkTable::getList(
				[
					'select'  => [
						'DATA'
					],
					'filter'  => [
						'VIRTUAL_CONNECTOR' => $this->idConnector,
						'ID_CHAT' => $message['chat']['id']
					]
				]
			);

			if ($rowBotFramework = $rawBotFramework->fetch())
			{
				$message = array_merge($message, $rowBotFramework['DATA']);
			}
		}

		return $message;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		$message = parent::sendMessageProcessing($message, $line);
		$message = $this->messageProcessing($message);

		return $message;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function updateMessageProcessing(array $message, $line): array
	{
		$message = parent::updateMessageProcessing($message, $line);
		$message = $this->messageProcessing($message);

		return $message;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function deleteMessageProcessing(array $message, $line): array
	{
		$message = parent::deleteMessageProcessing($message, $line);
		$message = $this->messageProcessing($message);

		return $message;
	}
	//END Output
}