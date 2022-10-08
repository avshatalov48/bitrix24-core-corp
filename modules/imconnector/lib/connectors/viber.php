<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Viber
 * @package Bitrix\ImConnector\Connectors
 */
class Viber extends Base
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
		$message = InteractiveMessage\Output::processSendingMessage($message, $this->idConnector);
		//Processing rich links
		$message = $this->processMessageForRich($message);

		return $this->processingMessageForOperatorData($message);
	}
}