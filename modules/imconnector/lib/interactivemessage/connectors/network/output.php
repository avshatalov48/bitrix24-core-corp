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
		if ($this->isLoadedKeyboard())
		{
			$keyboard = [];
			foreach ($this->keyboardData as $button)
			{
				$buttonData = [
					'TYPE' => $button['TYPE'] ?? 'BUTTON',
					'TEXT' => $button['TEXT_BUTTON'],
					'BG_COLOR' => $button['BOTTOM_COLOR'],
					'TEXT_COLOR' => $button['TEXT_COLOR'],
					'DISABLED' => $button['DISABLED'] ?? 'N',
					'DISPLAY' => $button['DISPLAY'] ?? 'BLOCK',
				];

				if (isset($button['LINK']))
				{
					$buttonData['LINK'] = $button['LINK'];
				}
				if (isset($button['COMMAND']))
				{
					$buttonData['COMMAND'] = $button['COMMAND'];
					if (isset($button['COMMAND_PARAMS']))
					{
						$buttonData['COMMAND_PARAMS'] =
							is_array($button['COMMAND_PARAMS'])
								? Json::encode($button['COMMAND_PARAMS'])
								: $button['COMMAND_PARAMS'] ?? '';
					}
				}
				if (isset($button['ACTION']))
				{
					$buttonData['ACTION'] = $button['ACTION'];
					if (isset($button['ACTION_VALUE']))
					{
						$buttonData['ACTION_VALUE'] =
							is_array($button['ACTION_VALUE'])
								? Json::encode($button['ACTION_VALUE'])
								: $button['ACTION_VALUE'] ?? '-';
					}
				}

				$keyboard[] = $buttonData;
			}

			$message['keyboardData'] = $keyboard;
		}

		return $message;
	}
}