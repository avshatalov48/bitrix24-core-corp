<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Network;

use \Bitrix\Main\Web\Json;

use \Bitrix\ImConnector\Error,
	\Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\InteractiveMessage;

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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function processingCommandKeyboard($command, $data): Result
	{
		$result = new Result();
		if(!empty($data))
		{
			$data = Json::decode($data);
		}

		if(
			$command === 'session' &&
			!empty($data)
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