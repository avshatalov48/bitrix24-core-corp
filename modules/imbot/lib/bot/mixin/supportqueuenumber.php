<?php

namespace Bitrix\ImBot\Bot\Mixin;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Imbot;
use Bitrix\Im\Bot\Keyboard;

const COMMAND_OPERATOR_QUEUE_NUMBER = 'operatorQueueNumber';
const COMMAND_QUEUE_NUMBER = 'queueNumber';
const MESSAGE_PARAM_QUEUE_NUMBER = 'IMB_QUEUE_NUMBER';// position in OL line

trait SupportQueueNumber
{
	/**
	 * Returns command's property list.
	 * @return array{class: string, handler: string, visible: bool, context: string}[]
	 */
	protected static function getQueueNumberCommandList(): array
	{
		return [
			COMMAND_QUEUE_NUMBER => [
				'command' => COMMAND_QUEUE_NUMBER,
				'handler' => 'onCommandAdd',/** @see Imbot\Bot\ChatBot::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => static::getBotId(),
					],
					[
						'COMMAND_CONTEXT' => 'TEXTAREA',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => static::getBotId(),
					],
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
						'CHAT_ENTITY_TYPE' => static::CHAT_ENTITY_TYPE,
					],
					[
						'COMMAND_CONTEXT' => 'TEXTAREA',
						'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
						'CHAT_ENTITY_TYPE' => static::CHAT_ENTITY_TYPE,
					],
				],
			],
		];
	}

	/**
	 * Sends command from user to network line.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(string) MESSAGE_ID
	 * 	(int) SESSION_ID
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function requestQueueNumber(array $params): bool
	{
		$http = static::instanceHttpClient();
		$response = $http->query(
			'clientQueueNumber',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
			]
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) MESSAGE_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID
	 * 	(int) QUEUE_NUMBER
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function operatorQueueNumber(array $params): bool
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$queueNumber = $params['QUEUE_NUMBER'] ?: 1;
		$answerText = static::getMessage('QUEUE_NUMBER');
		if (!$answerText)
		{
			$answerText = Loc::getMessage('SUPPORT_QUEUE_NUMBER');
		}
		$answerText = str_replace('#QUEUE_NUMBER#', $queueNumber, $answerText);


		// button
		$buttonText = static::getMessage('QUEUE_NUMBER_REFRESH');
		if (!$buttonText)
		{
			$buttonText = Loc::getMessage('SUPPORT_QUEUE_NUMBER_REFRESH');
		}
		$keyboard = new Keyboard(static::getBotId());
		$button = [
			'COMMAND' => COMMAND_QUEUE_NUMBER,
			'TEXT' => $buttonText,
			'DISPLAY' => 'LINE',
			'BG_COLOR' => '#29619b',
			'TEXT_COLOR' => '#fff',
		];
		$keyboard->addButton($button);

		$message = [
			'DIALOG_ID' => $params['DIALOG_ID'],
			'MESSAGE' => $answerText,
			'SYSTEM' => 'Y',
			'URL_PREVIEW' => 'N',
			'PARAMS' => [
				MESSAGE_PARAM_QUEUE_NUMBER => $queueNumber,
				static::MESSAGE_PARAM_ALLOW_QUOTE => 'N',
			],
			'KEYBOARD' => $keyboard,
		];
		$messageId = !empty($params['MESSAGE_ID']) ? (int)$params['MESSAGE_ID'] : 0;
		if ($messageId > 0)
		{
			$message['EDIT_FLAG'] = 'N';

			\CIMMessenger::disableMessageCheck();
			$res = static::updateMessage($messageId, $message);
			\CIMMessenger::enableMessageCheck();

			if (!$res)
			{
				if (static::dropMessage($messageId))
				{
					static::sendMessage($message);
				}
			}
		}
		else
		{
			static::sendMessage($message);
		}

		return true;
	}
}
