<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Livechat;

use Bitrix\Im\Bot\Keyboard;
use \Bitrix\Main\Web\Json;

use \Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage\Livechat
 */
class Output extends InteractiveMessage\Output
{
	/**
	 * The transformation of the description of the outgoing message in native format if possible.
	 *
	 * @param $message
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function nativeMessageProcessing($message): array
	{
		$command = 'session';

		if($this->isLoadedKeyboard())
		{
			$keyboard = new Keyboard();
			foreach ($this->keyboardData as $button)
			{
				$commandParams = [
					'ACTION' => $command,
					'COMMAND' => $button['COMMAND'],
					'CHAT_ID' => $this->idChat,
					'SESSION_ID' => $button['SESSION_ID'],
					'TASK_ID' => $button['TASK_ID'],
					'CONFIG_TASK_ID' => $button['CONFIG_TASK_ID'],
				];
				$commandParams = Json::encode($commandParams);

				$keyboard->addButton([
					'TEXT' => $button['TEXT_BUTTON'],
					'DISABLED' => 'N',
					'ACTION' => 'LIVECHAT',
					'ACTION_VALUE' => $commandParams,
					'BG_COLOR' => $button['BOTTOM_COLOR'],
					'TEXT_COLOR' => $button['TEXT_COLOR'],
					'DISPLAY' => 'LINE',
				]);
			}
			$message['KEYBOARD'] = $keyboard;
		}

		return $message;
	}
}