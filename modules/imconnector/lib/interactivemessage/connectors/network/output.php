<?php

namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Network;

use \Bitrix\Main\Web\Json;
use \Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage\Network
 */
class Output extends InteractiveMessage\Output
{
	/**
	 * The transformation of the description of the outgoing message in native format if possible.
	 *
	 * @param array $message
	 * @return array
	 */
	public function nativeMessageProcessing($message): array
	{
		$command = 'session';
		if ($this->isLoadedKeyboard())
		{
			foreach ($this->keyboardData as $keyboard)
			{
				$commandParams = [
					'COMMAND' => $keyboard['COMMAND'],
					'CHAT_ID' => $this->idChat,
					'SESSION_ID' => $keyboard['SESSION_ID'],
					'TASK_ID' => $keyboard['TASK_ID'],
					'CONFIG_TASK_ID' => $keyboard['CONFIG_TASK_ID'],
				];
				$commandParams = Json::encode($commandParams);

				$message['keyboardData'][] = [
					'TEXT' => $keyboard['TEXT_BUTTON'],
					'DISABLED' => 'N',
					'COMMAND' => $command,
					'COMMAND_PARAMS' => $commandParams,
					'BG_COLOR' => $keyboard['BOTTOM_COLOR'],
					'TEXT_COLOR' => $keyboard['TEXT_COLOR'],
					'DISPLAY' => 'LINE',
				];
			}
		}

		return $message;
	}
}