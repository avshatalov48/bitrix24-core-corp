<?php

namespace Bitrix\Imbot\Bot;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\AI;
use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\Pull;

class CopilotChatBot extends Base
{
	public const BOT_CODE = 'copilot';

	// context id for ai service
	public const
		CONTEXT_MODULE = 'im',
		CONTEXT_ID = 'copilot_chat',
		CONTEXT_SUMMARY = 'copilot_chat_summary',
		SUMMARY_PROMPT_ID = 'set_ai_session_name',
		ASSISTANT_ROLE_ID = 'copilot_assistant_chat'
	;

	// option amount of the messages to select for context
	public const
		OPTION_CONTEXT_AMOUNT = 'copilot_context_amount',
		CONTEXT_AMOUNT_DEFAULT = 50 // default value
	;

	// option mode interaction with ai service
	public const
		OPTION_MODE = 'copilot_mode',
		MODE_LONG_PULLING = 'long_pulling', // long pulling
		MODE_ASYNC_QUEUE = 'async_queue' // asynchronous requests
	;

	public const
		MESSAGE_COMPONENT_ID = 'CopilotMessage',
		MESSAGE_COMPONENT_START = 'ChatCopilotCreationMessage',
		MESSAGE_PARAMS_ERROR = 'COPILOT_ERROR',
		MESSAGE_PARAMS_MORE = 'COPILOT_HAS_MORE'
	;

	public const
		ERROR_SYSTEM = 'SYSTEM_ERROR',
		ERROR_AGREEMENT = 'AGREEMENT_ERROR',
		ERROR_TARIFF = 'TARIFF_ERROR',
		ERROR_NETWORK = 'NETWORK'
	;

	protected const BOT_PROPERTIES = [
		'CODE' => self::BOT_CODE,
		'TYPE' => Im\Bot::TYPE_SUPERVISOR,
		'MODULE_ID' => self::MODULE_ID,
		'CLASS' => self::class,
		'OPENLINE' => 'N', // Allow in Openline chats
		'HIDDEN' => 'Y',
		'INSTALL_TYPE' => Im\Bot::INSTALL_TYPE_SILENT, // suppress success install message
		'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see CopilotChatBot::onMessageAdd */
		'METHOD_MESSAGE_UPDATE' => 'onMessageUpdate',/** @see CopilotChatBot::onMessageUpdate */
		'METHOD_MESSAGE_DELETE' => 'onMessageDelete',/** @see CopilotChatBot::onMessageDelete */
		'METHOD_BOT_DELETE' => 'onBotDelete',/** @see CopilotChatBot::onBotDelete */
		'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see CopilotChatBot::onChatStart */
	];

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
		if (!Loader::includeModule('ai'))
		{
			self::addError(new ImBot\Error(
				__METHOD__,
				self::ERROR_SYSTEM,
				Loc::getMessage('IMBOT_COPILOT_UNAVAILABLE') ?? 'Module AI is unavailable'
			));
			return -1;
		}

		if (self::getBotId())
		{
			return self::getBotId();
		}

		$languageId = Loc::getCurrentLang();
		if (!empty($params['LANG']))
		{
			$languageId = $params['LANG'];
			Loc::loadLanguageFile(__FILE__, $languageId);
		}

		$botProps = array_merge(self::BOT_PROPERTIES, [
			'LANG' => $languageId,// preferred language
			'PROPERTIES' => [
				'NAME' => Loc::getMessage('IMBOT_COPILOT_BOT_NAME', null, $languageId),
				'COLOR' => 'COPILOT',
			]
		]);

		$botAvatar = self::uploadAvatar($languageId);
		if (!empty($botAvatar))
		{
			$botProps['PROPERTIES']['PERSONAL_PHOTO'] = $botAvatar;
		}

		$botId = Im\Bot::register($botProps);
		if ($botId)
		{
			self::setBotId($botId);
		}

		$eventManager = Main\EventManager::getInstance();
		foreach (self::getEventHandlerList() as $handler)
		{
			$eventManager->registerEventHandlerCompatible(
				$handler['module'],
				$handler['event'],
				self::MODULE_ID,
				self::class,
				$handler['handler']
			);
		}

		self::restoreHistory();

		return $botId;
	}

	/**
	 * Returns event handler list.
	 * @return array{module: string, event: string, class: string, handler: string}[]
	 */
	public static function getEventHandlerList(): array
	{
		return [
			[
				'module' => 'ai',
				'event' => 'onQueueJobExecute', /** @see AI\QueueJob::EVENT_SUCCESS */
				'handler' => 'onQueueJobMessage', /** @see CopilotChatBot::onQueueJobMessage */
			],
			[
				'module' => 'ai',
				'event' => 'onQueueJobFail', /** @see AI\QueueJob::EVENT_FAIL */
				'handler' => 'onQueueJobFail', /** @see CopilotChatBot::onQueueJobFail */
			],
			[
				'module' => 'ai',
				'event' => 'onContextGetMessages', /** @see AI\Context::getMessages */
				'handler' => 'onGetContextMessages', /** @see CopilotChatBot::onGetContextMessages */
			],
		];
	}

	/**
	 * Restores chat copilot membership.
	 * @return void
	 */
	protected static function restoreHistory(): void
	{
		$chatRes = Im\Model\ChatTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=TYPE' => Im\V2\Chat::IM_TYPE_COPILOT,
				'!=AUTHOR_ID' => self::getBotId(),
			],
		]);
		while ($chatData = $chatRes->fetch())
		{
			$chat = Im\V2\Chat::getInstance((int)$chatData['ID']);
			$chat->addUsers([self::getBotId()], [], true, false, true);
		}
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

		$eventManager = Main\EventManager::getInstance();
		foreach (self::getEventHandlerList() as $handler)
		{
			$eventManager->unregisterEventHandler(
				$handler['module'],
				$handler['event'],
				self::MODULE_ID,
				self::class,
				$handler['handler']
			);
		}

		return Im\Bot::unRegister(['BOT_ID' => self::getBotId()]);
	}

	/**
	 * Is bot enabled.
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		return
			Loader::includeModule('ai')
			&& (self::getBotId() > 0)
		;
	}

	/**
	 * Refresh settings agent.
	 * @param bool $regular
	 * @return string
	 */
	public static function refreshAgent(bool $regular = false): string
	{
		self::updateBotProperties();

		return $regular ? __METHOD__.'();' : '';
	}

	/**
	 * @return bool
	 */
	public static function updateBotProperties(): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		$botCache = Im\Bot::getCache(self::getBotId());

		$languageId = $botCache['LANG'] ?: Loc::getCurrentLang();
		Loc::loadLanguageFile(__FILE__, $languageId);

		$newData = array_merge(self::BOT_PROPERTIES, [
			'PROPERTIES' => [
				'NAME' => Loc::getMessage('IMBOT_COPILOT_BOT_NAME', null, $languageId),
				'COLOR' => 'COPILOT',
			]
		]);

		$avatarUrl = self::uploadAvatar($languageId);
		if ($avatarUrl)
		{
			$avatarId = \CFile::saveFile($avatarUrl, self::MODULE_ID);
			if ($avatarId)
			{
				$newData['PROPERTIES']['PERSONAL_PHOTO'] = $avatarId;
			}
		}

		Im\Bot::clearCache();
		Im\Bot::update(['BOT_ID' => self::getBotId()], $newData);

		return true;
	}

	//endregion

	//region Chat events

	/**
	 * Event handler on message add.
	 * @see \Bitrix\Im\Bot::onMessageAdd
	 *
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields): bool
	{
		$chat = Im\V2\Chat::getInstance((int)$messageFields['CHAT_ID']);

		if (!self::checkMembershipRestriction($chat, $messageFields))
		{
			self::sendError($messageFields['DIALOG_ID'], Loc::getMessage('IMBOT_COPILOT_RESTRICTION'));

			$chat->deleteUser(self::getBotId());
			return false;
		}

		if (!self::checkMessageRestriction($messageFields))
		{
			return false;
		}

		$serviceRestriction = self::checkAiServeRestriction();
		if (!$serviceRestriction->isSuccess())
		{
			$serviceRestrictionError = $serviceRestriction->getErrors()[0];
			self::sendError($messageFields['DIALOG_ID'], $serviceRestrictionError->getMessage());
			return false;
		}

		$messages = new Im\V2\MessageCollection();
		$messages->add(new Im\V2\Message($messageId));

		$chat
			->withContextUser(self::getBotId())
			->readMessages($messages, true)
		;

		self::sendTyping($messageFields['DIALOG_ID']);

		// send them to ai service
		$result = self::askService([
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageFields['MESSAGE']
		]);

		if ($result->isSuccess())
		{
			/** @var array{MESSAGE: string, HAS_MORE: bool} $output */
			if (
				($output = $result->getData())
				&& !empty($output['MESSAGE'])
			)
			{
				$message = [
					'MESSAGE' => $output['MESSAGE'],
					'PARAMS' => [
						Im\V2\Message\Params::COMPONENT_PARAMS => [
							self::MESSAGE_PARAMS_MORE => (bool)$output['HAS_MORE'],
						]
					]
				];
				self::sendMessage($messageFields['DIALOG_ID'], $message);

				if (
					strlen($messageFields['MESSAGE']) >= 30
					&& self::isDialogHasDefaultTitle($chat)
				)
				{
					$messageFields['MESSAGE_ID'] = $messageId;
					Main\Application::getInstance()->addBackgroundJob(
						[self::class, 'getDialogMeaning'],
						[$messageFields, $chat]
					);
				}
			}
		}
		else
		{
			$error = $result->getErrors()[0];

			ImBot\Log::write(
				[
					'errorCode' => $error->getCode(),
					'errorMessage' => $error->getMessage(),
					'dialogId' => $messageFields['DIALOG_ID'],
					'messageId' => $messageId,
				],
				'AI MESSAGE ERROR:'
			);

			self::addError(new ImBot\Error(
				__METHOD__,
				$error->getCode(),
				$error->getMessage()
			));

			$errorMessage = self::translateErrorMessage($error);
			self::sendError($messageFields['DIALOG_ID'], $errorMessage);
		}

		return $result->isSuccess();
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
		if (!self::checkMessageRestriction($messageFields))
		{
			return false;
		}

		return true;
	}

	/**
	 * Event handler when bot join to chat.
	 * @see \Bitrix\Im\Bot::onJoinChat
	 * Method registers at bot field `b_im_bot.METHOD_WELCOME_MESSAGE`
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields): bool
	{
		$chat = Im\V2\Chat\ChatFactory::getInstance()->getChat($dialogId);

		if (!self::checkMembershipRestriction($chat, $joinFields))
		{
			self::sendError($dialogId, Loc::getMessage('IMBOT_COPILOT_RESTRICTION'));

			$chat->deleteUser(self::getBotId());

			return false;
		}

		return true;
	}

	//endregion

	//region AI Payload

	/**
	 * Make request to AI Engine.
	 * @param array{DIALOG_ID: string, MESSAGE_TEXT: string} $params
	 * @return Result<array{HASH: string, MESSAGE: string, HAS_MORE: bool}>
	 */
	protected static function askService(array $params): Result
	{
		$result = new Result();

		$contextParams = ['chat_id' => $params['DIALOG_ID']];
		$text = $params['MESSAGE_TEXT'];

		$context = new AI\Context(self::CONTEXT_MODULE, self::CONTEXT_ID);
		$context->setParameters($contextParams);

		$engine = AI\Engine::getByCategory(AI\Engine::CATEGORIES['text'], $context);
		if ($engine instanceof AI\Engine)
		{
			$payload = new AI\Payload\Text($text);
			$payload->setRole(AI\Prompt\Role::get(self::ASSISTANT_ROLE_ID));

			$engine
				->setPayload($payload)
				->setParameters(['collect_context' => true])
				->setHistoryState(false)
				->onSuccess(function (AI\Result $queueResult, ?string $queueHash = null) use($engine, &$result) {
					$isQueueable = $engine instanceof AI\Engine\IQueue;
					$message = $isQueueable ? $queueResult->getRawData() : $queueResult->getPrettifiedData();

					$rawData = $queueResult->getRawData();
					$hasMore =
						isset($rawData['choices'], $rawData['choices'][0], $rawData['choices'][0]['finish_reason'])
						&& $rawData['choices'][0]['finish_reason'] == 'length'
					;

					$result->setData([
						'HASH' => $queueHash,
						'MESSAGE' => $message,
						'HAS_MORE' => $hasMore,
					]);

				})
				->onError(function (Error $processingError) use(&$result) {
					$result->addError($processingError);
				})
			;
			if (self::getMode() == self::MODE_ASYNC_QUEUE)
			{
				$engine->completionsInQueue(); // asynchronous requests
			}
			else
			{
				$engine->completions();// long pulling
			}
		}

		return $result;
	}

	/**
	 * Generates summary.
	 * @param array{DIALOG_ID: string, MESSAGE_TEXT: string} $params
	 * @return Result
	 */
	protected static function extractSummary(array $params): Result
	{
		$result = new Result();

		$prompt = AI\Prompt\Manager::getByCode(self::SUMMARY_PROMPT_ID);
		if ($prompt instanceof AI\Prompt\Item)
		{
			$contextParams = ['chat_id' => $params['DIALOG_ID']];
			$text = $params['MESSAGE_TEXT'];

			$context = new AI\Context(self::CONTEXT_MODULE, self::CONTEXT_SUMMARY);
			$context->setParameters($contextParams);

			$engine = AI\Engine::getByCategory(AI\Engine::CATEGORIES['text'], $context);
			if ($engine instanceof AI\Engine)
			{
				$payload = new AI\Payload\Prompt(self::SUMMARY_PROMPT_ID);
				$payload
					->setMarkers(['original_message' => $text])
					->setRole(AI\Prompt\Role::get(self::ASSISTANT_ROLE_ID))
				;

				$engine
					->setPayload($payload)
					->setParameters(['max_tokens' => 250])
					->setHistoryState(false)
					->onSuccess(function (AI\Result $queueResult, ?string $queueHash = null) use($engine, &$result) {
						$isQueueable = $engine instanceof AI\Engine\IQueue;
						$message = $isQueueable ? $queueResult->getRawData() : $queueResult->getPrettifiedData();

						$result->setData([
							'SUMMARY' => $message,
						]);
					})
					->onError(function (Error $processingError) use(&$result) {
						$result->addError($processingError);
					})
				;
				if (self::getMode() == self::MODE_ASYNC_QUEUE)
				{
					$engine->completionsInQueue(); // asynchronous requests
				}
				else
				{
					$engine->completions();// long pulling
				}
			}
		}

		return $result;
	}

	/**
	 * Event handler for `ai:onContextGetMessages` event.
	 * @see AI\Context::getMessages
	 * @event ai:onContextGetMessages
	 *
	 * @param string $moduleId
	 * @param string $contextId
	 * @param array $parameters
	 * @param mixed|null $nextStep
	 * @return array
	 */
	public static function onGetContextMessages($moduleId, $contextId, $parameters = [], $nextStep = null): array
	{
		$result = [
			'messages' => []
		];

		if (
			!Loader::includeModule('im')
			|| !Loader::includeModule('ai')
			|| $moduleId != self::CONTEXT_MODULE
			|| !in_array($contextId, [self::CONTEXT_ID, self::CONTEXT_SUMMARY])
			|| empty($parameters)
			|| empty($parameters['chat_id'])
		)
		{
			return $result;
		}

		$chatId = Im\Dialog::getChatId($parameters['chat_id']);
		$chat = Im\V2\Chat::getInstance($chatId);

		$lastMessageId = $chat->getLastMessageId();

		$filter = ['CHAT_ID' => $chatId];
		$order = ['ID' => 'DESC']; // start from newest
		$limit = self::getContextAmount();

		$botId = self::getBotId();
		$messages = Im\V2\MessageCollection::find($filter, $order, $limit);
		$messages->fillParams();
		foreach ($messages as $message)
		{
			if (
				$message->isSystem() // skip all system messages
				|| $message->getMessageId() == $lastMessageId // skip last user message
			)
			{
				continue;
			}

			if ($message->getParams()->isSet(Im\V2\Message\Params::COMPONENT_ID))
			{
				// skip welcome chat message
				if ($message->getParams()->get(Im\V2\Message\Params::COMPONENT_ID)->getValue() == self::MESSAGE_COMPONENT_START)
				{
					continue;
				}
				// skip error message
				if (
					$message->getParams()->isSet(Im\V2\Message\Params::COMPONENT_PARAMS)
					&& isset($message->getParams()->get(Im\V2\Message\Params::COMPONENT_PARAMS)->getValue()[self::MESSAGE_PARAMS_ERROR])
				)
				{
					continue;
				}
			}

			if ($message->getAuthorId() == $botId)
			{
				$result['messages'][] = [
					'role' => 'assistant',
					'content' => $message->getMessage(),
				];
			}
			else
			{
				$result['messages'][] = [
					'role' => 'user',
					'content' => $message->getMessage(),
				];
			}
		}

		return $result;
	}

	/**
	 * Success callback handler.
	 * @see AI\QueueJob::execute
	 * @event ai:onQueueJobExecute
	 *
	 * @param string $hash
	 * @param AI\Engine\IEngine $engine
	 * @param AI\Result $result
	 * @param Error|null $error
	 * @return void
	 */
	public static function onQueueJobMessage(string $hash, AI\Engine\IEngine $engine, AI\Result $result, ?Error $error): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$moduleId = $engine->getContext()->getModuleId();
		$contextId = $engine->getContext()->getContextId();
		$parameters = $engine->getContext()->getParameters();

		if (
			empty($moduleId)
			|| $moduleId != self::CONTEXT_MODULE
			|| empty($contextId)
			|| !in_array($contextId, [self::CONTEXT_ID, self::CONTEXT_SUMMARY])
			|| empty($parameters)
			|| empty($parameters['chat_id'])
		)
		{
			return;
		}

		$dialogId = $parameters['chat_id'];

		if (!empty($result->getPrettifiedData()))
		{
			if ($contextId == self::CONTEXT_SUMMARY)
			{
				$chat = Im\V2\Chat::getInstance(Im\Dialog::getChatId($dialogId));
				self::renameChat($chat, $result->getPrettifiedData());
			}
			else
			{
				self::sendMessage($dialogId, $result->getPrettifiedData());
			}
		}
	}

	/**
	 * Callback handler Copilot job has been failed.
	 * @see AI\QueueJob::clearOldAgent
	 * @see AI\QueueJob::fail
	 * @event ai:onQueueJobFail
	 *
	 * @param string $hash
	 * @param AI\Engine\IEngine $engine
	 * @param AI\Result $result
	 * @param Error|null $error
	 * @return void
	 */
	public static function onQueueJobFail(string $hash, AI\Engine\IEngine $engine, AI\Result $result, ?Error $error): void
	{
		$moduleId = $engine->getContext()->getModuleId();
		$contextId = $engine->getContext()->getContextId();
		$parameters = $engine->getContext()->getParameters();

		if (
			empty($moduleId)
			|| $moduleId != self::CONTEXT_MODULE
			|| empty($contextId)
			|| !in_array($contextId, [self::CONTEXT_ID, self::CONTEXT_SUMMARY])
			|| empty($parameters)
			|| empty($parameters['chat_id'])
		)
		{
			return;
		}

		$dialogId = $parameters['chat_id'];

		ImBot\Log::write(
			[
				'errorMessage' => $error ? $error->getMessage() : 'Job fail',
				'errorCode' => $error ? $error->getCode() : '',
				'hash' => $hash,
				'params' => $parameters,
			],
			'AI QUEUE FAIL:'
		);

		if ($contextId == self::CONTEXT_ID)
		{
			$errorMessage = self::translateErrorMessage($error);
			self::sendError($dialogId, $errorMessage);
		}
	}

	/**
	 * Translate ai error message to human.
	 * @param Error|null $error
	 * @return string
	 */
	protected static function translateErrorMessage(?Error $error): string
	{
		$errorMessage = Loc::getMessage('IMBOT_COPILOT_JOB_FAIL_MSGVER_1');
		if ($error)
		{
			switch (true)
			{
				case is_numeric($error->getCode()):
				case $error->getCode() == 'HASH_EXPIRED':
					break;

				case ($errorDesc = Loc::getMessage('IMBOT_COPILOT_ERROR_' . $error->getCode() . '_MSGVER_1')):
					$errorMessage = $errorDesc;
					break;

				case ($errorDesc = $error->getMessage()):
					$errorMessage = $errorDesc;
					break;
			}
		}

		return $errorMessage;
	}

	//endregion

	//region Restrictions

	/**
	 * Check service AI unavailability and restrictions.
	 * @return Result
	 */
	protected static function checkAiServeRestriction(): Result
	{
		$checkResult = new Result();
		if (Loader::includeModule('ai'))
		{
			$context = new AI\Context(self::CONTEXT_MODULE, self::CONTEXT_SUMMARY);
			$engine = AI\Engine::getByCategory(AI\Engine::CATEGORIES['text'], $context);
			if ($engine instanceof AI\Engine)
			{
				if (!$engine->isAvailableByAgreement())
				{
					$checkResult->addError(new Error(
						Loc::getMessage('IMBOT_COPILOT_AGREEMENT_RESTRICTION') ?? 'AI service agreement must be accepted',
						self::ERROR_AGREEMENT
					));
				}
				elseif (!$engine->isAvailableByTariff())
				{
					$checkResult->addError(new Error(
						Loc::getMessage('IMBOT_COPILOT_TARIFF_RESTRICTION') ?? 'AI service unavailable by tariff',
						self::ERROR_TARIFF
					));
				}
			}
			else
			{
				$checkResult->addError(new Error(
					Loc::getMessage('IMBOT_COPILOT_UNAVAILABLE') ?? 'Module AI is unavailable',
					self::ERROR_SYSTEM
				));
			}
		}
		else
		{
			$checkResult->addError(new Error(
				Loc::getMessage('IMBOT_COPILOT_UNAVAILABLE') ?? 'Module AI is unavailable',
				self::ERROR_SYSTEM
			));
		}

		return $checkResult;
	}

	/**
	 * Put here any restriction for chat membership.
	 *
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMembershipRestriction(Im\V2\Chat $chat, array $messageFields): bool
	{
		if (
			!($chat instanceof Im\V2\Chat\CopilotChat)
			|| $messageFields['MESSAGE_TYPE'] != Im\V2\Chat::IM_TYPE_COPILOT
		)
		{
			return false;
		}

		return true;
	}

	/**
	 * Put here any restriction for message type length.
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMessageRestriction(array $messageFields): bool
	{
		if (isset($messageFields['SYSTEM']) && $messageFields['SYSTEM'] == 'Y')
		{
			return false;
		}

		return !empty($messageFields['MESSAGE']);
	}

	//endregion

	//region Title

	/**
	 * Do we need to rename copilot chat.
	 *
	 * @param Im\V2\Chat $chat
	 * @return bool
	 */
	protected static function isDialogHasDefaultTitle(Im\V2\Chat $chat): bool
	{
		if ($chat instanceof Im\V2\Chat\CopilotChat)
		{
			if ($template = Im\V2\Chat\CopilotChat::getTitleTemplate())
			{
				$template = strtr($template, ['#NUMBER#' => '[0-9]+']);
				return preg_match("/{$template}/", $chat->getTitle());
			}
		}

		return false;
	}

	/**
	 * Rename copilot chat.
	 *
	 * @param array $messageFields
	 * @param Im\V2\Chat $chat
	 * @return void
	 */
	public static function getDialogMeaning(array $messageFields, Im\V2\Chat $chat): void
	{
		$resultTitle = self::extractSummary([
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_TEXT' => $messageFields['MESSAGE']
		]);
		if (
			$resultTitle->isSuccess()
			&& ($outputTitle = $resultTitle->getData())
			&& !empty($outputTitle['SUMMARY'])
		)
		{
			self::renameChat($chat, $outputTitle['SUMMARY']);
		}
		elseif (!$resultTitle->isSuccess())
		{
			$error = $resultTitle->getErrors()[0];
			ImBot\Log::write(
				[
					'errorCode' => $error->getCode(),
					'errorMessage' => $error->getMessage(),
					'dialogId' => $messageFields['DIALOG_ID'],
					'messageId' => $messageFields['MESSAGE_ID'],
				],
				'AI TITLE ERROR:'
			);
		}
	}

	/**
	 * Rename copilot chat.
	 *
	 * @param Im\V2\Chat $chat
	 * @param string $title
	 * @return void
	 */
	private static function renameChat(Im\V2\Chat $chat, string $title): void
	{
		if (
			!empty($title)
			&& $chat instanceof Im\V2\Chat\CopilotChat
		)
		{
			//todo: Use v2 api for renaming
			//$chat->setTitle($title)->save();

			(new \CIMChat())->rename($chat->getChatId(), $title, false, false);
		}
	}

	//endregion

	//region Send message

	/**
	 * Sends message to client.
	 *
	 * @param string $dialogId
	 * @param array|string $message
	 * @return void
	 */
	protected static function sendMessage(string $dialogId, $message): void
	{
		if (!is_array($message))
		{
			$message = ['MESSAGE' => $message];
		}

		$message['FROM_USER_ID'] = self::getBotId();
		$message['DIALOG_ID'] = $dialogId;
		$message['URL_PREVIEW'] = 'N';

		if (!empty($message['PARAMS']))
		{
			$message['PARAMS'] = [];
		}
		$message['PARAMS'][Im\V2\Message\Params::COMPONENT_ID] = self::MESSAGE_COMPONENT_ID;

		\CIMMessenger::add($message);
	}

	/**
	 * Sends message to client.
	 *
	 * @param string $dialogId
	 * @param array|string $message
	 * @return void
	 */
	protected static function sendError(string $dialogId, $message): void
	{
		if (!is_array($message))
		{
			$message = ['MESSAGE' => $message];
		}

		$message['FROM_USER_ID'] = self::getBotId();
		$message['DIALOG_ID'] = $dialogId;
		$message['URL_PREVIEW'] = 'N';

		if (!empty($message['PARAMS']))
		{
			$message['PARAMS'] = [];
		}

		$message['PARAMS'][Im\V2\Message\Params::COMPONENT_ID] = self::MESSAGE_COMPONENT_ID;
		$message['PARAMS'][Im\V2\Message\Params::COMPONENT_PARAMS] = [
			self::MESSAGE_PARAMS_ERROR => true
		];

		\CIMMessenger::add($message);
	}

	/**
	 * Sends typing event.
	 *
	 * @param string $dialogId
	 * @return void
	 */
	protected static function sendTyping(string $dialogId): void
	{
		Im\Bot::startWriting(['BOT_ID' => self::getBotId()], $dialogId);
		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}
	}

	//endregion

	//region Options

	/**
	 * Returns current mode interaction with AI service: asynchronous requests or long pulling request.
	 * @return string
	 */
	public static function getMode(): string
	{
		static $mode;
		if ($mode === null)
		{
			$mode = Option::get(self::MODULE_ID, self::OPTION_MODE, self::MODE_LONG_PULLING);
			if (!in_array($mode, [self::MODE_LONG_PULLING, self::MODE_ASYNC_QUEUE]))
			{
				$mode = self::MODE_LONG_PULLING;
			}
		}

		return $mode;
	}

	/**
	 * Returns amount messages for context.
	 * @return int
	 */
	public static function getContextAmount(): int
	{
		static $amount;
		if ($amount === null)
		{
			$amount = (int)Option::get(self::MODULE_ID, self::OPTION_CONTEXT_AMOUNT, self::CONTEXT_AMOUNT_DEFAULT);
			if ($amount == 0)
			{
				$amount = self::CONTEXT_AMOUNT_DEFAULT;
			}
		}

		return $amount;
	}

	//endregion
}