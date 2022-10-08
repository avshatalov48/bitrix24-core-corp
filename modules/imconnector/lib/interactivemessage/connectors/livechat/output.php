<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\Livechat;

use Bitrix\Im\Bot\Keyboard;
use Bitrix\Main\Web\Json;
use Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage\Livechat
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
		if ($this->isLoadedKeyboard())
		{
			$keyboard = new Keyboard();

			$sessionManagementCommands = [
				InteractiveMessage\Input::COMMAND_SESSION,
				InteractiveMessage\Input::COMMAND_SESSION_NEW,
				InteractiveMessage\Input::COMMAND_SESSION_CLOSE,
				InteractiveMessage\Input::COMMAND_SESSION_CONTINUE,
			];

			foreach ($this->keyboardData as $button)
			{
				if (in_array($button['COMMAND'], $sessionManagementCommands, true))
				{
					$commandParams = $button['COMMAND_PARAMS'];
					$commandParams['ACTION'] = InteractiveMessage\Input::COMMAND_SESSION;

					$keyboard->addButton([
						'TEXT' => $button['TEXT_BUTTON'],
						'DISABLED' => 'N',
						'ACTION' => 'LIVECHAT',
						'ACTION_VALUE' => Json::encode($commandParams),
						'BG_COLOR' => $button['BOTTOM_COLOR'],
						'TEXT_COLOR' => $button['TEXT_COLOR'],
						'DISPLAY' => 'LINE',
					]);
				}
			}

			$message['KEYBOARD'] = $keyboard;
		}

		return $message;
	}
}