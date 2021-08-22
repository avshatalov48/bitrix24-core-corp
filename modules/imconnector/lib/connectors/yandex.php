<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Yandex
 * @package Bitrix\ImConnector\Connectors
 */
class Yandex extends Base
{
	//Input

	//END Input

	//Output

	//END Output
	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		//Processing for native messages
		$message = InteractiveMessage\Output::sendMessageProcessing($message, $this->idConnector);
		//Processing rich links
		$message = $this->processingMessageForRich($message);

		return $this->processingMessageForOperatorData($message);
	}
}