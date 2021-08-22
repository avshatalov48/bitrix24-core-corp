<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Result;

Loc::loadMessages(__FILE__);

/**
 * Class FbInstagramDirect
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagramDirect extends Base
{
	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		if(!empty($message['message']['unsupported_message']))
		{
			unset($message['message']['unsupported_message']);

			$message['message']['text'] = Loc::getMessage('IMCONNECTOR_MESSAGE_UNSUPPORTED_INSTAGRAM');
		}

		return parent::processingInputNewMessage($message, $line);
	}
	//END Input
}
