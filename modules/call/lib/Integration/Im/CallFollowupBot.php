<?php

namespace Bitrix\Call\Integration\Im;

use Bitrix\Main\Loader;
use Bitrix\Im;
use Bitrix\Im\Command;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\Im\Model\CommandTable;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Params;
use Bitrix\Im\V2\Message\Send\SendingConfig;
use Bitrix\ImBot;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\ChatMessage;
use Bitrix\Call\Integration\AI\CallAIService;

\Bitrix\Main\Loader::includeModule('imbot');

class CallFollowupBot extends ImBot\Bot\Base
{
	public const MODULE_ID = 'call';
	public const BOT_CODE = 'CallFollowupBot';
	public const COMMAND_CONTINUE_FOLLOWUP = 'continueFollowup';

	protected const BOT_PROPERTIES = [
		'CODE' => self::BOT_CODE,
		'TYPE' => Im\Bot::TYPE_SUPERVISOR,
		'MODULE_ID' => self::MODULE_ID,
		'CLASS' => self::class,
		'OPENLINE' => 'N', // Allow in Openline chats
		'HIDDEN' => 'Y',
		'INSTALL_TYPE' => Im\Bot::INSTALL_TYPE_SILENT,
		'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see CallFollowupBot::onChatStart */
		'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see CallFollowupBot::onMessageAdd */
		'METHOD_MESSAGE_UPDATE' => 'onMessageUpdate',/** @see CallFollowupBot::onMessageUpdate */
		'METHOD_MESSAGE_DELETE' => 'onMessageDelete',/** @see CallFollowupBot::onMessageDelete */
		'METHOD_BOT_DELETE' => 'onBotDelete',/** @see CallFollowupBot::onBotDelete */
		'PROPERTIES' => [
			'NAME' => 'Call Followup Bot',
			'COLOR' => 'COPILOT',
		]
	];

	//region Event

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields): bool
	{
		$command = self::detectCommandByMessage($messageFields);
		if (!$command)
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_CONTINUE_FOLLOWUP)
		{
			if (preg_match("/CALL_ID:([0-9]+)/i", $messageFields['COMMAND_PARAMS'], $matches))
			{
				$callId = (int)$matches[1];
				$call = Im\Call\Registry::getCallWithId($callId);
				if ($call)
				{
					$result = CallAIService::getInstance()->restartCallAiTask($callId);
					if ($result->isSuccess())
					{
						$chat = Chat::getInstance($call->getChatId());
						if (NotifyService::getInstance()->findMessage($chat->getId(), $callId, NotifyService::MESSAGE_TYPE_AI_START, 1) === null)
						{
							$message = ChatMessage::generateTaskStartMessage($callId, $chat);
							if ($message)
							{
								$sendingConfig = (new SendingConfig())
									->enableSkipCounterIncrements()
									->enableSkipUrlIndex()
								;
								NotifyService::getInstance()->sendMessageDeferred($chat, $message, $sendingConfig);
							}
						}
					}
					else
					{
						NotifyService::getInstance()->sendTaskFailedMessage($result->getError(), $call, -1);
					}
				}
			}

			self::disableMessageButtons((int)$messageId);

			return true;
		}

		return false;
	}

	/**
	 * Event handler when bot join to chat.
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields)
	{
		return false;
	}

	/**
	 * Event handler on message add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		return false;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageUpdate($messageId, $messageFields): bool
	{
		return false;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageDelete($messageId, $messageFields): bool
	{
		return false;
	}

	//endregion


	//region Register

	/**
	 * Register CopilotChatBot at portal.
	 *
	 * @param array $params
	 * @return int
	 */
	public static function register(array $params = []): int
	{
		if (!Loader::includeModule('im'))
		{
			return -1;
		}

		if (self::getBotId())
		{
			return self::getBotId();
		}

		$botId = Im\Bot::register(self::BOT_PROPERTIES);
		if ($botId)
		{
			self::setBotId($botId);
			self::registerCommands();
		}

		return $botId;
	}

	/**
	 * Agent for deferred bot registration.
	 * @return string
	 */
	public static function delayRegister(int $repeat = 1): string
	{
		if (self::register() > 0 || $repeat > 100)
		{
			return '';
		}

		$repeat++;

		return __METHOD__ . "({$repeat});";
	}

	/**
	 * Unregister CopilotChatBot at portal.
	 *
	 * @return bool
	 */
	public static function unRegister(): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		return Im\Bot::unRegister(['BOT_ID' => self::getBotId()]);
	}

	/**
	 * Returns command's property list.
	 * @return array{class: string, handler: string, visible: bool, context: string}[]
	 */
	public static function getCommandList(): array
	{
		return [
			self::COMMAND_CONTINUE_FOLLOWUP => [
				'command' => self::COMMAND_CONTINUE_FOLLOWUP,
				'handler' => 'onCommandAdd',/** @see CallFollowupBot::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => 'C', /** @see \IM_MESSAGE_CHAT */
					],
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => 'P', /** @see \IM_MESSAGE_PRIVATE */
					],
				],
			],
		];
	}

	/**
	 * Registers chat commands.
	 * @return bool
	 */
	public static function registerCommands(): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}
		$botId = self::getBotId();
		$commandList = [];
		$res = CommandTable::getList([
			'filter' => [
				'=MODULE_ID' => self::MODULE_ID,
				'=BOT_ID' => $botId,
			]
		]);
		while ($row = $res->fetch())
		{
			$commandList[$row['COMMAND']] = $row;
		}

		Command::clearCache();
		foreach (self::getCommandList() as $command)
		{
			if (!isset($commandList[$command['command']]))
			{
				Command::register([
					'MODULE_ID' => self::MODULE_ID,
					'BOT_ID' => $botId,
					'COMMAND' => $command['command'],
					'CLASS' => $command['CLASS'] ?? static::class,
					'METHOD_COMMAND_ADD' => 'onCommandAdd',/** @see CallFollowupBot::onCommandAdd */
					'HIDDEN' => $command['visible'] === true ? 'N' : 'Y',
					'COMMON' => 'Y',
				]);
			}
			elseif (
				($commandList[$command['command']]['CLASS'] != ($command['class'] ?? static::class))
				|| ($commandList[$command['command']]['METHOD_COMMAND_ADD'] != ($command['handler'] ?? 'onCommandAdd'))
			)
			{
				Command::update(
					['COMMAND_ID' => $commandList[$command['command']]['ID']],
					[
						'CLASS' => $command['class'] ?? static::class,
						'METHOD_COMMAND_ADD' => $command['handler'] ?? 'onCommandAdd',/** @see CallFollowupBot::onCommandAdd */
						'HIDDEN' => $command['visible'] === true ? 'N' : 'Y',
					]
				);
			}
			unset($commandList[$command['command']]);
		}
		foreach ($commandList as $command)
		{
			Command::unRegister(['COMMAND_ID' => $command['ID']]);
		}

		return true;
	}

	/**
	 * Detects command by message.
	 *
	 * @param array $message Message params.
	 * @return array|null
	 */
	protected static function detectCommandByMessage(array $message): ?array
	{
		if (
			//(isset($message['SYSTEM']) && $message['SYSTEM'] === 'Y')
			empty($message['COMMAND'])
		)
		{
			return null;
		}

		$command = self::getCommandList()[$message['COMMAND']] ?? null;
		if (!$command)
		{
			return null;
		}

		$result = null;
		foreach ($command['context'] as $context)
		{
			$diff = array_intersect_assoc($message, $context);
			if (count($diff) == count($context))
			{
				$result = $command;
				break;
			}
		}

		return $result;
	}

	//endregion

	//region Keyboard

	/**
	 * Enables keyboard buttons in message.
	 *
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	protected static function enableMessageButtons(int $messageId, bool $sendPullNotify = true): bool
	{
		return self::switchButtonsAvailability(true, $messageId, $sendPullNotify);
	}

	/**
	 * Disables keyboard buttons in message.
	 *
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	protected static function disableMessageButtons(int $messageId, bool $sendPullNotify = true): bool
	{
		return self::switchButtonsAvailability(false, $messageId, $sendPullNotify);
	}

	/**
	 * Disables keyboard buttons in message.
	 *
	 * @param bool $availability Availability flat to set.
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	private static function switchButtonsAvailability(bool $availability, int $messageId, bool $sendPullNotify = true): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}
		if ($messageId <= 0)
		{
			return false;
		}

		$message = new Message($messageId);
		if (
			!$message->getMessageId()
			|| !$message->getParams()->isSet(Params::KEYBOARD)
		)
		{
			return false;
		}

		$buttons = $message->getParams()->toArray()[Params::KEYBOARD] ?? null;
		if (!$buttons)
		{
			return false;
		}

		$keyboard = new Keyboard($buttons[0]['BOT_ID']);
		foreach ($buttons as $buttonData)
		{
			$buttonData['DISABLED'] = $availability ? 'N': 'Y';
			$keyboard->addButton($buttonData);
		}

		$message->getParams()->get(Params::KEYBOARD)->setValue($keyboard);
		$message->save();

		if ($sendPullNotify)
		{
			\CIMMessageParam::sendPull($messageId, ['KEYBOARD']);
		}

		return true;
	}
	//endregion
}
