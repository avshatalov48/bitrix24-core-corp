<?php

namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Livechat;

use Bitrix\Main\Web\Json;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\InteractiveMessage;

/**
 * @package Bitrix\ImConnector\InteractiveMessage\Livechat
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

		if(
			$result->isSuccess()
			&& $command === self::COMMAND_SESSION
			&& !empty($data)
			&& (
				$data['COMMAND'] === self::COMMAND_SESSION_NEW
				|| $data['COMMAND'] === self::COMMAND_SESSION_CLOSE
				|| $data['COMMAND'] === self::COMMAND_SESSION_CONTINUE
			)
		)
		{
			$result = $this->runCommand($data);
		}
		else
		{
			$result->addError(new Error(
				'Invalid data was transmitted',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
				__METHOD__,
				['command' => $command, 'data' => $data]
			));
		}

		return $result;
	}
}