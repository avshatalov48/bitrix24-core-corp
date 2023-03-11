<?php

namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Network;

use Bitrix\Main\Web\Json;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\InteractiveMessage;

/**
 * @package Bitrix\ImConnector\InteractiveMessage\Network
 */
class Input extends InteractiveMessage\Input
{
	/**
	 * @param $command
	 * @param $data
	 * @return Result
	 */
	public function processingCommandKeyboard($command, $data): Result
	{
		$result = new Result();
		if (!empty($data))
		{
			try
			{
				$data = Json::decode($data);
			}
			catch (\Bitrix\Main\SystemException $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
			}
		}

		if (
			$result->isSuccess()
			&& (empty($data) || empty($data['COMMAND']))
		)
		{
			$result->addError(new Error(
				'Invalid data was transmitted',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
				__METHOD__,
				['command' => $command, 'data' => $data]
			));
		}

		if (
			$result->isSuccess()
			&& (
				$command === self::COMMAND_SESSION
				&& in_array($data['COMMAND'], [self::COMMAND_SESSION_NEW, self::COMMAND_SESSION_CLOSE, self::COMMAND_SESSION_CONTINUE])
			)
		){
			$result = $this->runCommand($data);
		}

		return $result;
	}
}