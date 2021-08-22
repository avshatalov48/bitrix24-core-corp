<?php

namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Network;

use \Bitrix\Main\Web\Json;
use \Bitrix\ImConnector\Error;
use \Bitrix\ImConnector\Result;
use \Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Base
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
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
			}
		}

		if(
			$command === 'session'
			&& !empty($data)
			&& $result->isSuccess()
		)
		{
			$result = $this->runCommand($data);
		}
		else
		{
			$result->addError(new Error('Invalid data was transmitted', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA', __METHOD__, ['command' => $command, 'data' => $data]));
		}

		return $result;
	}
}