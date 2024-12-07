<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Im;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;
use Bitrix\ImBot\Bot\Mixin;
use Bitrix\Imopenlines\MessageParameter;


class SupportBox extends Network implements SupportBot, SupportQuestion
{
	use Mixin\SupportQuestion;
	use Mixin\SupportQueueNumber;

	public const
		BOT_CODE = 'support',

		CHAT_ENTITY_TYPE = 'SUPPORT24_QUESTION',// specialized support chats

		OPTION_BOT_ID = 'support_bot_id',
		OPTION_BOT_ACTIVE = 'support_enabled',
		OPTION_BOT_CODE = 'support_code',
		OPTION_BOT_NAME = 'support_name',
		OPTION_BOT_DESC = 'support_desc',
		OPTION_BOT_AVATAR = 'support_avatar',
		OPTION_BOT_MESSAGES = 'support_messages',

		COMMAND_ACTIVATE = 'activate',
		COMMAND_START_DIALOG = 'startDialog'
	;

	protected const
		AVATAR = 'https://helpdesk.bitrix24.com/images/support/bot.png',
		HELP_DESK_CODE = '7577357';


	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public static function register(array $params = []): int
	{
		if (!Loader::includeModule('im'))
		{
			return -1;
		}

		if (self::isInstalled() && self::getBotId() > 0)
		{
			return self::getBotId();// do nothing
		}

		Im\Bot::clearCache();

		$showActivateMessage = true;

		$botParams = [
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see SupportBox::onChatStart */
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see SupportBox::onMessageAdd */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see SupportBox::onBotDelete */
			'PROPERTIES' => [
				'NAME' => self::getBotName(),
				'WORK_POSITION' => self::getBotDesc(),
			]
		];
		$botAvatar = self::uploadAvatar(self::getBotAvatar());
		if (!empty($botAvatar))
		{
			$botParams['PROPERTIES']['PERSONAL_PHOTO'] = $botAvatar;
		}

		$botId = self::getPreviousBotId();
		if ($botId === null)
		{
			// Stage I - register as local bot
			$botParams['CODE'] = self::BOT_CODE;
			$botParams['TYPE'] = Im\Bot::TYPE_BOT;
			$botParams['INSTALL_TYPE'] =  Im\Bot::INSTALL_TYPE_SILENT;

			$botId = (int)Im\Bot::register($botParams);

			self::setBotId($botId);
		}
		elseif ($botId)
		{
			$botCache = Im\Bot::getCache($botId);

			self::setBotId($botId);

			// upgrade previous one
			Im\Bot::update(['BOT_ID' => $botId], $botParams);

			// perform activation
			if (self::updateBotProperties())
			{
				$showActivateMessage = false;

				self::setActive(true);

				if ($botCache['APP_ID'] !== '' && $botCache['APP_ID'] !== self::getBotCode())
				{
					self::sendRequestFinalizeSession([
						'BOT_CODE' => $botCache['APP_ID'],
						'MESSAGE' => Loc::getMessage('SUPPORT_BOX_CHANGE_LINE'),
					]);
				}
			}
		}

		if ($botId)
		{
			self::registerCommands($botId);
			self::registerApps($botId);

			// set recent contact for admins
			self::setAsRecent();

			self::restoreQuestionHistory();
		}

		// must be activated before use
		if ($showActivateMessage)
		{
			self::setActive(false);

			$keyboard = new Keyboard(self::getBotId());
			$keyboard->addButton([
				'DISPLAY' => 'LINE',
				'TEXT' =>  Loc::getMessage('SUPPORT_BOX_ACTIVATE'),
				'BG_COLOR' => '#29619b',
				'TEXT_COLOR' => '#fff',
				'BLOCK' => 'Y',
				'COMMAND' => self::COMMAND_ACTIVATE,
			]);

			parent::sendMessage([
				'DIALOG_ID' => self::USER_LEVEL_ADMIN,
				'MESSAGE' => Loc::getMessage('SUPPORT_BOX_WELCOME_MESSAGE'),
				'KEYBOARD' => $keyboard,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N',
			]);
		}
		else
		{
			self::notifyAdministrators(self::getMessage('ACTIVATION_SUCCESS', Loc::getMessage('SUPPORT_BOX_ACTIVATION_SUCCESS')));
		}

		return $botId;
	}

	/**
	 * Unregister bot at portal.
	 *
	 * @param string $code Open Line Id.
	 * @param bool $notifyController Send unregister notification request to controller.
	 *
	 * @return bool
	 */
	public static function unRegister($code = '', $notifyController = true): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		$result = false;
		$botCode = self::getBotCode();
		$botId = self::getBotId();

		if ($botCode !== '')
		{
			self::sendRequestFinalizeSession([
				'BOT_CODE' => $botCode,
				'MESSAGE' => Loc::getMessage('SUPPORT_BOX_CLOSE_LINE'),
			]);

			$result = parent::unRegister($botCode, $notifyController);
			if (is_array($result) && isset($result['result']))
			{
				$result = $result['result'];
				if ($result)
				{
					Option::delete('imbot', ['name' => parent::BOT_CODE.'_'.$botCode.'_bot_id']);
				}
			}
		}

		if ($result === false && $botId == 0)
		{
			$res = Im\Model\BotTable::getList([
				'select' => ['BOT_ID'],
				'filter' => [
					'=CLASS' => static::class
				]
			]);
			if ($botData = $res->fetch())
			{
				$botId = (int)$botData['BOT_ID'];
			}
		}

		if ($result === false && $botId > 0)
		{
			$result = Im\Bot::unRegister(['BOT_ID' => $botId]);
		}

		self::clearSettings();

		self::deleteAgent([
			'mask' => 'refreshAgent',/** @see SupportBox::refreshAgent */
		]);

		return $result;
	}

	/**
	 * Returns command's property list.
	 * @return array{handler: string, visible: bool, context: string}[]
	 */
	public static function getCommandList(): array
	{
		$commandList = parent::getCommandList();

		unset($commandList[self::COMMAND_UNREGISTER]);

		return array_merge(
			$commandList,
			self::getQueueNumberCommandList(),
			[
				self::COMMAND_NETWORK_SESSION => [
					'command' => self::COMMAND_NETWORK_SESSION,
					'handler' => 'onCommandAdd',/** @see SupportBox::onCommandAdd */
					'visible' => false,
					'context' => [
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
							'TO_USER_ID' => static::getBotId(),
						],
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
							'CHAT_ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
						],
					],
				],
				self::COMMAND_ACTIVATE => [
					'command' =>  self::COMMAND_ACTIVATE,
					'handler' => 'onCommandAdd',/** @see SupportBox::activate */
					'visible' => false,
					'context' => [
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						],
					],
				],
				self::COMMAND_START_DIALOG => [
					'command' =>  self::COMMAND_START_DIALOG,
					'handler' => 'onCommandAdd',/** @see SupportBox::onCommandAdd */
					'visible' => false,
					'context' => [
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'CHAT_ENTITY_TYPE' => ImBot\Service\Notifier::CHAT_ENTITY_TYPE,
						],
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
							'CHAT_ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
						],
					],
				],
			]
		);
	}

	/**
	 * Returns app's property list.
	 * @return array{command: string, icon: string, js: string, context: string, lang: string}[]
	 */
	public static function getAppList(): array
	{
		$appList = parent::getAppList();

		if (self::isEnabledQuestionFunctional())
		{
			$appList = array_merge($appList, self::getQuestionAppList());
		}

		return $appList;
	}

	//region Chart bot interface

	/**
	 * Event handler when bot join to chat.
	 * @see \Bitrix\Im\Bot::onJoinChat
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields)
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::checkMembershipRestriction($joinFields))
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage([
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
			}

			(new \CIMChat(self::getBotId()))->deleteUser(mb_substr($dialogId, 4), self::getBotId());

			return true;
		}

		return true;
	}
	/**
	 * Event handler on bot remove.
	 *
	 * @param int|null $bodId
	 *
	 * @return bool
	 */
	public static function onBotDelete($bodId = null): bool
	{
		return self::setBotId(0);
	}

	/**
	 * Event handler on answer add.
	 * Alias for @see \Bitrix\Imbot\Bot\ChatBot::onAnswerAdd
	 * Called from @see \Bitrix\ImBot\Controller::sendToBot
	 *
	 * @param string $command
	 * @param array $params
	 *
	 * @return ImBot\Error|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		if ($command === self::COMMAND_OPERATOR_CHANGE_LINE)
		{
			Log::write($params, 'NETWORK: $command');

			if (self::updateBotProperties())
			{
				//notify
				self::notifyAdministrators(self::getMessage('CHANGE_CODE', Loc::getMessage('SUPPORT_BOX_CHANGE_LINE_USER')));
			}

			return ['RESULT' => 'OK'];
		}
		elseif ($command === Mixin\COMMAND_OPERATOR_QUEUE_NUMBER)
		{
			Log::write($params, "NETWORK: $command");

			self::operatorQueueNumber([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'QUEUE_NUMBER' => $params['QUEUE_NUMBER'],
			]);

			return ['RESULT' => 'OK'];
		}

		return parent::onReceiveCommand($command, $params);
	}

	/**
	 * Handler for `StartWriting` event.
	 * @see \Bitrix\ImBot\Event::onStartWriting
	 *
	 * @inheritDoc
	 *
	 * @param array $params <pre>
	 * [
	 * 	(int) BOT_ID Bot id.
	 * 	(string) DIALOG_ID Dialog id.
	 * 	(int) USER_ID User id.
	 * ]
	 * <pre>
	 *
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		if (!self::checkTypingRestriction($params))
		{
			return false;
		}

		return parent::onStartWriting($params);
	}

	/**
	 * Event handler on message add.
	 * @see \Bitrix\Im\Bot::onMessageAdd
	 *
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] === 'Y')
		{
			return false;
		}

		// check restrictions
		if (!self::checkMembershipRestriction($messageFields))
		{
			(new \CIMChat($messageFields['BOT_ID']))->deleteUser($messageFields['CHAT_ID'], $messageFields['BOT_ID']);
			return false;
		}

		if (!self::checkMessageRestriction($messageFields))
		{
			return false;
		}

		$dialogId = (string)$messageFields['FROM_USER_ID'];
		$isChat = $messageFields['CHAT_ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE;
		if ($isChat)
		{
			$dialogId = 'chat'.(int)$messageFields['CHAT_ID'];
		}

		if (
			self::isEnabledQuestionFunctional()
			&& !(self::instanceDialogSession(self::getBotId(), $dialogId)->getSessionId() > 0)
			&& !self::allowAdditionalQuestion()
		)
		{
			self::markMessageUndelivered($messageId);

			$questionDisallowed = self::getQuestionDisallowMessage();
			if ($questionDisallowed)
			{
				self::sendMessage([
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $questionDisallowed,
					'KEYBOARD' => self::getQuestionResumeButton(),
					'URL_PREVIEW' => 'N',
					'SYSTEM' => 'Y',
				]);
			}

			return true;
		}

		$managedCache = Application::getInstance()->getManagedCache();
		$cacheKey = 'telemetry_sent_' . md5($messageFields['BOT_ID'], $messageFields['DIALOG_ID']);
		if (!$managedCache->read(86400, $cacheKey))
		{
			$dialogSession = self::instanceDialogSession((int)$messageFields['BOT_ID'], $messageFields['DIALOG_ID']);
			if ($dialogSession->getParam('TELEMETRY_SENT') === 'N')
			{
				self::sendTelemetry($messageFields);
				$dialogSession->update(['TELEMETRY_SENT' => 'Y']);
				$managedCache->set($cacheKey, true);
			}
		}

		return parent::onMessageAdd($messageId, $messageFields);
	}

	/**
	 * Handler for "im:OnSessionVote" event.
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onSessionVote(array $params): bool
	{
		return self::clientSessionVote($params);
	}

	/**
	 * Event handler on `operatorMessageAdd`.
	 *
	 * @inheritDoc
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	protected static function operatorMessageAdd($messageId, $messageFields)
	{
		if (!empty($messageFields['DIALOG_ID']))
		{
			self::startDialogSession([
				'BOT_ID' => self::getBotId(),
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'GREETING_SHOWN' => 'Y',
			]);
		}

		return parent::operatorMessageAdd($messageId, $messageFields);
	}

	protected static function sendTelemetry($messageFields): bool
	{
		$modulesInfo = ModuleManager::getInstalledModules();
		foreach ($modulesInfo as $key => $mi)
		{
			$modulesInfo[$key]['VERSION'] = ModuleManager::getVersion($key);
		}

		$siteCheckerTest = new \CSiteCheckerTest();
		$filePath = $_SERVER['DOCUMENT_ROOT'] . $siteCheckerTest->LogFile;
		try
		{
			$logFile = new File($filePath);
			$lastCheckDate = (new \DateTimeImmutable)->setTimestamp($logFile->getModificationTime());
			$logContents = htmlspecialcharsEx($logFile->getContents());
		}
		catch (FileNotFoundException $exception)
		{
			self::clientMessageAdd([
				'BOT_ID' => self::getBotId(),
				'USER_ID' => $messageFields['FROM_USER_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => [
					'TEXT' => Loc::getMessage('TELEMETRY_NO_CHECK_NEVER_BEEN_DONE'),
				],
				'EXTRA_DATA' => [
					'MODULES_INFO' => $modulesInfo,
				],
				'PARAMS' => [
					'CLASS' => 'bx-messenger-content-item-system',
					'TELEMETRY' => 'Y',
				],
			]);

			return false;
		}

		if ($failsCount = substr_count($logContents, 'Fail'))
		{
			$text = Loc::getMessagePlural(
				'TELEMETRY_ALL_FAIL',
				$failsCount,
				[
					'#DATE#' => $lastCheckDate->format('Y-m-d H:i'),
					'#FAILS_COUNT#' => $failsCount
				]
			);
		}
		else
		{
			$text = Loc::getMessage(
				'TELEMETRY_ALL_OK',
				[
					'#DATE#' => $lastCheckDate->format('Y-m-d H:i')
				]
			);
		}

		self::clientMessageAdd([
			'BOT_ID' => self::getBotId(),
			'USER_ID' => $messageFields['FROM_USER_ID'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => [
				'TEXT' => $text,
			],
			'EXTRA_DATA' => [
				'MODULES_INFO' => $modulesInfo,
			],
			'FILES_RAW' => [
				[
					'NAME' => 'site_checker.log',
					'TYPE' => 'text/x-log',
					'DATA' => $logContents,
				],
			],
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-system',
				'TELEMETRY' => 'Y',
			]
		]);

		return true;
	}

	/**
	 * Chechs if bot
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMembershipRestriction(array $messageFields): bool
	{
		return (
			// Standard network one-to-one conversation
			(
				$messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_PRIVATE
			)
			// allow conversation in specialized questioning chat
			|| (
				$messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_CHAT
				&& $messageFields['CHAT_ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE
			)
			// allow support bot membership in the notification channel
			|| (
				$messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_CHAT
				&& $messageFields['CHAT_ENTITY_TYPE'] === \Bitrix\ImBot\Service\Notifier::CHAT_ENTITY_TYPE
			)
		);
	}

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMessageRestriction(array $messageFields): bool
	{
		if (!self::isUserAdmin(self::getCurrentUser()->getId()) && !self::isUserIntegrator(self::getCurrentUser()->getId()))
		{
			return false;
		}

		$bot = Im\Bot::getCache($messageFields['BOT_ID']);
		if (mb_substr($bot['CODE'], 0, 7) != parent::BOT_CODE)
		{
			return false;
		}

		return
			// Allow one-to-one conversation
			(
				$messageFields['TO_USER_ID'] == $messageFields['BOT_ID']
			)
			// allow conversation in specialized questioning chat
			|| (
				$messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_CHAT
				&& $messageFields['CHAT_ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE
			);
	}

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkTypingRestriction(array $messageFields): bool
	{
		return
			// Allow only one-to-one conversation
			(
				empty($messageFields['CHAT']) && empty($messageFields['RELATION'])
			)
			||
			// allow conversation in specialized questioning chat
			(
				$messageFields['CHAT']['TYPE'] === \IM_MESSAGE_CHAT
				&& $messageFields['CHAT']['ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE
			);
	}

	//endregion

	//region Commands

	/**
	 * @param string $command
	 * @param string $lang
	 * @return array{TITLE: string, PARAMS: string}
	 */
	public static function onAppLang($command, $lang = null): array
	{
		if ($command === Mixin\COMMAND_ADD_QUESTION)
		{
			return self::getSupportQuestionAppLang($lang);
		}

		return [];
	}

	/**
	 * Event handler on command add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields)
	{
		$command = static::getCommandByMessage($messageFields);
		if (!$command)
		{
			return false;
		}

		if ($messageFields['COMMAND'] == self::COMMAND_ACTIVATE)
		{
			Im\Bot::startWriting(['BOT_ID' => self::getBotId()], $messageFields['DIALOG_ID']);

			if (self::activate())
			{
				foreach (self::getRecentDialogs() as $dialog)
				{
					if (
						$dialog['MESSAGE_ID'] > 0
						&& $dialog['MESSAGE_TYPE'] == \IM_MESSAGE_PRIVATE
					)
					{
						\CIMMessageParam::set($dialog['MESSAGE_ID'], [self::MESSAGE_PARAM_KEYBOARD => 'N']);
						\CIMMessageParam::sendPull($dialog['MESSAGE_ID'], [self::MESSAGE_PARAM_KEYBOARD]);
					}
				}

				parent::sendMessage([
					'DIALOG_ID' => self::USER_LEVEL_ADMIN,
					'MESSAGE' => self::getMessage('ACTIVATION_SUCCESS', Loc::getMessage('SUPPORT_BOX_ACTIVATION_SUCCESS')),
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
			}
			else
			{
				$error = self::getError();

				$helpDeskUrl = '';
				if (Loader::includeModule('ui'))
				{
					$helpDeskUrl = \Bitrix\UI\Util::getArticleUrlByCode(self::HELP_DESK_CODE);
				}

				$message = Loc::getMessage('SUPPORT_BOX_ACTIVATION_ERROR', [
					'#ERROR#' => $error->msg,
					'#HELP_DESK#' => $helpDeskUrl,
				]);
				$keyboard = new Keyboard(self::getBotId());
				$keyboard->addButton([
					'DISPLAY' => 'LINE',
					'TEXT' =>  Loc::getMessage('SUPPORT_BOX_ACTIVATE'),
					'BG_COLOR' => '#29619b',
					'TEXT_COLOR' => '#fff',
					'BLOCK' => 'Y',
					'COMMAND' => self::COMMAND_ACTIVATE,
				]);

				parent::sendMessage([
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $message,
					'KEYBOARD' => $keyboard,
					'URL_PREVIEW' => 'N',
					'SYSTEM' => 'N',
				]);

				\CIMMessageParam::set($messageId, [self::MESSAGE_PARAM_KEYBOARD => 'N']);
				\CIMMessageParam::sendPull($messageId, [self::MESSAGE_PARAM_KEYBOARD]);
			}

			return true;
		}
		elseif ($messageFields['COMMAND'] === self::COMMAND_START_DIALOG)
		{
			$message = (new \CIMChat(self::getBotId()))->getMessage($messageId);

			// duplicate message
			self::operatorMessageAdd(0, [
				'BOT_ID' => self::getBotId(),
				'BOT_CODE' => self::getBotCode(),
				'DIALOG_ID' => self::getCurrentUser()->getId(),
				'MESSAGE' => $message['MESSAGE'],
				'PARAMS' => [
					self::MESSAGE_PARAM_ALLOW_QUOTE => 'Y',
					'IMB_MENU_ACTION' => 'SKIP:MENU', /** @see \Bitrix\Imbot\Bot\Mixin\MESSAGE_PARAM_MENU_ACTION */
				],
			]);

			$userGender = Im\User::getInstance(self::getCurrentUser()->getId())->getGender();
			$forward = self::getMessage('START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			if (!$forward)
			{
				$forward = Loc::getMessage('SUPPORT_BOX_START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			}

			\CIMMessenger::add([
				'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
				'SYSTEM' => 'Y',
				'FROM_USER_ID' => self::getBotId(),
				'TO_CHAT_ID' => $message['CHAT_ID'],
				'MESSAGE' => self::replacePlaceholders($forward, self::getCurrentUser()->getId()),
			]);

			// Send push command to chat switch
			Im\Bot::sendPullOpenDialog(self::getBotId());

			self::disableMessageButtons($messageId);

			return true;
		}
		elseif ($messageFields['COMMAND'] === Mixin\COMMAND_QUEUE_NUMBER)
		{
			$sessionId = self::instanceDialogSession(self::getBotId(), $messageFields['DIALOG_ID'])->getSessionId();
			if (!$sessionId)
			{
				$lastMessages = (new \CIMMessage())->getLastMessage($messageFields['FROM_USER_ID'], static::getBotId(), false, false);
				foreach ($lastMessages['message'] as $message)
				{
					if ($message['senderId'] != self::getBotId())
					{
						continue;
					}
					if (
						!$sessionId
						&& isset($message['params'], $message['params']['IMOL_SID']) /** @see MessageParameter::IMOL_SID */
						&& (int)$message['params']['IMOL_SID'] > 0 /** @see MessageParameter::IMOL_SID  SESSION_ID */
					)
					{
						$sessionId = (int)$message['params']['IMOL_SID'];/** @see MessageParameter::IMOL_SID */
					}
					if (isset($message['params'], $message['params']['IMOL_VOTE_SID']))/** @see MessageParameter::IMOL_VOTE_SID */
					{
						break;// it is previous session
					}
				}
			}

			self::requestQueueNumber([
				'MESSAGE_ID' => $messageId,
				'BOT_ID' => self::getBotId(),
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'SESSION_ID' => $sessionId,
			]);

			return true;
		}

		elseif (
			$messageFields['COMMAND'] === self::COMMAND_NETWORK_SESSION
			&& $messageFields['COMMAND_PARAMS'] === 'resume' /** @see Mixin\COMMAND_RESUME_SESSION */
		)
		{
			if (
				self::isEnabledQuestionFunctional()
				&& !(self::instanceDialogSession(self::getBotId(), $messageFields['DIALOG_ID'])->getSessionId() > 0)
			)
			{
				self::dropMessage((int)$messageId);

				if (!self::allowAdditionalQuestion())
				{
					$questionDisallowed = self::getQuestionDisallowMessage();
					if ($questionDisallowed)
					{
						self::sendMessage([
							'DIALOG_ID' => $messageFields['DIALOG_ID'],
							'MESSAGE' => $questionDisallowed,
							'KEYBOARD' => self::getQuestionResumeButton(),
							'URL_PREVIEW' => 'N',
							'SYSTEM' => 'Y',
						]);
					}

					return false;
				}

				return true;
			}
		}

		return parent::onCommandAdd($messageId, $messageFields);
	}

	/**
	 * Activate support on portal.
	 *
	 * @return bool
	 */
	public static function activate()
	{
		if (self::checkPublicUrl() !== true)
		{
			return false;
		}

		$settings = self::getBotSettings();
		if (empty($settings))
		{
			return false;
		}
		if (!self::saveSettings($settings))
		{
			return false;
		}

		$botCode = self::getBotCode();
		if (empty($botCode))
		{
			return false;
		}

		// Stage II - convert into network bot

		$search = parent::search($botCode, true);
		if (!is_array($search) || empty($search))
		{
			$error = self::getError();
			if ($error->code === 'LINE_NOT_FOUND')
			{
				self::$lastError = new ImBot\Error(
					__METHOD__,
					$error->code,
					Loc::getMessage('IMBOT_NETWORK_ERROR_LINE_NOT_FOUND')
				);
			}
			return false;
		}

		$botId = parent::getNetworkBotId($botCode, true);
		if (!$botId)
		{
			$botId = parent::register([
				'CODE' => $botCode,
				'LINE_NAME' => self::getBotName(),
				'LINE_DESC' => self::getBotDesc(),
				'LINE_AVATAR' => self::getBotAvatar(),
				'CLASS' => __CLASS__,
				'TYPE' => Im\Bot::TYPE_NETWORK,
			]);

			if (!$botId)
			{
				return false;
			}
		}

		self::setBotId($botId);
		self::setActive(true);
		self::addAgent([
			'agent' => 'refreshAgent()', /** @see SupportBox::refreshAgent */
			'class' => __CLASS__,
			'regular' => false,
			'delay' => random_int(30, 360),
		]);

		return true;
	}

	/**
	 * Update bots params.
	 *
	 * @return bool
	 */
	public static function updateBotProperties()
	{
		$botId = self::getBotId();
		if (!$botId)
		{
			return false;
		}

		$settings = self::getBotSettings();
		if (empty($settings))
		{
			return false;
		}
		$botCode = $settings[self::OPTION_BOT_CODE];
		if (empty($botCode))
		{
			return false;
		}

		if (!self::saveSettings($settings))
		{
			return false;
		}

		Im\Bot::clearCache();

		$botParams = [
			'VERIFIED' => 'Y',
			'CODE' => parent::BOT_CODE. '_'. $botCode,
			'APP_ID' => $botCode,
			'TYPE' => Im\Bot::TYPE_NETWORK,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see SupportBox::onChatStart */
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see SupportBox::onMessageAdd */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see SupportBox::onBotDelete */
			'PROPERTIES' => [
				'NAME' => self::getBotName(),
				'WORK_POSITION' => self::getBotDesc(),
			]
		];

		$botAvatar = Im\User::uploadAvatar(self::getBotAvatar(), $botId);
		if (!empty($botAvatar))
		{
			$botParams['PROPERTIES']['PERSONAL_PHOTO'] = $botAvatar;
		}

		Im\Bot::update(['BOT_ID' => $botId], $botParams);

		return true;
	}

	//endregion

	//region Previous bot

	/**
	 * Returns bot id of the previous version.
	 * todo: Remove it.
	 * @see ImBot\Bot\Support::getBotId
	 * @return null|int
	 */
	public static function getPreviousBotId(): ?int
	{
		$botId = (int)parent::getNetworkBotId(self::getPreviousBotCode(), true);
		if ($botId > 0)
		{
			$botData = Im\Bot::getCache($botId);
			if ($botData['CLASS'] != 'Bitrix\\ImBot\\Bot\\Support')
			{
				$botId = -1;
			}
		}
		else
		{
			$res = Im\Model\BotTable::getList([
				'select' => ['BOT_ID'],
				'filter' => [
					'=CLASS' => 'Bitrix\\ImBot\\Bot\\Support'
				]
			]);
			if ($botData = $res->fetch())
			{
				$botId = (int)$botData['BOT_ID'];
			}
		}

		return $botId > 0 ? $botId : null;
	}

	/**
	 * Returns OL code of the previous version.
	 * todo: Remove it.
	 * @see  ImBot\Bot\Support::getCode
	 * @return string
	 */
	private static function getPreviousBotCode()
	{
		// $botCode = ImBot\Bot\Support::getCode();
		if (self::getLangId() == 'ru')
		{
			$botCode = '4df232699a9e1d0487c3972f26ea8d25';
		}
		else
		{
			$botCode = '1a146ac74c3a729681c45b8f692eab73';
		}

		return $botCode;
	}

	//endregion

	//region Getters & Setters

	/**
	 * Loads bot settings from controller.
	 *
	 * @param array{BOT_ID: int} $params Command arguments.
	 *
	 * @return array|null
	 */
	public static function getBotSettings(array $params = []): ?array
	{
		static $result;
		if (empty($result))
		{
			$settings = parent::getBotSettings($params);
			if (empty($settings))
			{
				return null;
			}

			$result = [];
			$mirrors = [
				self::OPTION_BOT_CODE => 'support24_box_code',
				self::OPTION_BOT_NAME => 'support24_box_name',
				self::OPTION_BOT_DESC => 'support24_box_desc',
				self::OPTION_BOT_AVATAR => 'support24_box_avatar',
				self::OPTION_BOT_MESSAGES => 'support24_box_messages',
				Mixin\OPTION_BOT_QUESTION_LIMIT => 'support24_session_limit',
			];
			foreach ($mirrors as $prop => $alias)
			{
				if (isset($settings[$alias]))
				{
					$result[$prop] = $settings[$alias];
				}
			}
		}

		return $result;
	}

	/**
	 * Apply new settings to bot configuration.
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	private static function saveSettings(array $settings): bool
	{
		if (isset($settings[self::OPTION_BOT_CODE]))
		{
			self::setBotCode($settings[self::OPTION_BOT_CODE]);
		}
		if (isset($settings[self::OPTION_BOT_NAME]))
		{
			self::setBotName($settings[self::OPTION_BOT_NAME]);
		}
		if (isset($settings[self::OPTION_BOT_DESC]))
		{
			self::setBotDesc($settings[self::OPTION_BOT_DESC]);
		}
		if (isset($settings[self::OPTION_BOT_AVATAR]))
		{
			self::setBotAvatar($settings[self::OPTION_BOT_AVATAR]);
		}
		if (isset($settings[self::OPTION_BOT_MESSAGES]))
		{
			self::setBotMessages($settings[self::OPTION_BOT_MESSAGES]);
		}

		Option::set(
			self::MODULE_ID,
			Mixin\OPTION_BOT_QUESTION_LIMIT,
			$settings[Mixin\OPTION_BOT_QUESTION_LIMIT] ?? -1
		);

		return true;
	}

	/**
	 * Removes bot configuration.
	 *
	 * @return bool
	 */
	private static function clearSettings(): bool
	{
		$ids = [
			self::OPTION_BOT_ID,
			self::OPTION_BOT_CODE,
			self::OPTION_BOT_NAME,
			self::OPTION_BOT_DESC,
			self::OPTION_BOT_AVATAR,
			self::OPTION_BOT_ACTIVE,
			self::OPTION_BOT_MESSAGES,
		];
		foreach ($ids as $id)
		{
			Option::delete(self::MODULE_ID, ['name' => $id]);
		}

		return true;
	}

	/**
	 * Allows updating bot fields (name, desc, avatar, welcome mess) using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage(): bool
	{
		return false;
	}

	/**
	 * Allows updating bot's avatar using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage(): bool
	{
		return (bool)self::getBotAvatar() !== true;
	}

	/**
	 * Looks for the same installation.
	 *
	 * @return bool
	 */
	private static function isInstalled(): bool
	{
		$res = Im\Model\BotTable::getList([
			'select' => ['BOT_ID'],
			'filter' => [
				'=CLASS' => __CLASS__,
			]
		]);
		if ($botData = $res->fetch())
		{
			return true;
		}

		return false;
	}

	/**
	 * Detects client's support level.
	 * @return string
	 */
	public static function getSupportLevel(): string
	{
		return self::SUPPORT_LEVEL_PAID;
	}

	/**
	 * Set bot enable.
	 *
	 * @param bool $enable
	 *
	 * @return void
	 */
	public static function setActive(bool $enable): void
	{
		Option::set(self::MODULE_ID, self::OPTION_BOT_ACTIVE, $enable);
	}

	/**
	 * Is bot enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		return
			(static::getBotId() > 0)
			&& ((bool)Option::get(self::MODULE_ID, self::OPTION_BOT_ACTIVE, false) === true);
	}

	/**
	 * Sets this bot as recent contact for administrator group.
	 * @return bool
	 */
	private static function setAsRecent(): bool
	{
		$botId = self::getBotId();
		foreach (parent::getAdministrators() as $userId)
		{
			\CIMMessage::getChatId($userId, $botId);

			\CAllIMContactList::setRecent([
				'USER_ID' => $userId,
				'ENTITY_ID' => $botId,
			]);
		}

		return true;
	}

	/**
	 * Detects portal language.
	 * @return string
	 */
	private static function getLangId(): string
	{
		static $langId;
		if ($langId === null)
		{
			$langId = Loc::getCurrentLang();
		}

		return $langId;
	}

	/**
	 * Returns OL code.
	 * @return string
	 */
	public static function getBotCode(): string
	{
		return Option::get(self::MODULE_ID, self::OPTION_BOT_CODE, '');
	}

	/**
	 * Saves new OL code.
	 * @param string $botCode
	 * @return bool
	 */
	public static function setBotCode(string $botCode): bool
	{
		$prevBotCode = self::getBotCode();
		$botId = self::getBotId();

		$prevNetBotExits = false;
		if ($prevBotCode !== '')
		{
			$prevNetBotId = Option::get(self::MODULE_ID, parent::BOT_CODE.'_'.$prevBotCode.'_bot_id', '');
			$prevNetBotExits = (int)$prevNetBotId > 0;

			if ($prevNetBotExits)
			{
				Option::delete(self::MODULE_ID, ['name' => parent::BOT_CODE.'_'.$prevBotCode.'_bot_id']);
			}
		}

		if ($botCode !== '' && $prevNetBotExits)
		{
			Option::set(self::MODULE_ID, parent::BOT_CODE.'_'.$botCode.'_bot_id', $botId);
		}

		Option::set(self::MODULE_ID, self::OPTION_BOT_CODE, $botCode);

		return true;
	}

	/**
	 * Returns registered bot Id.
	 * @return int
	 */
	public static function getBotId(): int
	{
		return (int)Option::get(self::MODULE_ID, self::OPTION_BOT_ID, 0);
	}

	/**
	 * Saves new bot Id.
	 * @param int $botId
	 * @return bool
	 */
	public static function setBotId($botId)
	{
		if ($botId > 0)
		{
			Option::set(self::MODULE_ID, self::OPTION_BOT_ID, $botId);
		}
		else
		{
			Option::delete(self::MODULE_ID, ['name' => self::OPTION_BOT_ID]);
		}

		return true;
	}

	/**
	 * Return name of the bot.
	 * @return string
	 */
	public static function getBotName(): string
	{
		$name = Option::get(self::MODULE_ID, self::OPTION_BOT_NAME, '');
		if ($name === '')
		{
			$name = Loc::getMessage('SUPPORT_BOX_NAME');
		}

		return $name;
	}

	/**
	 * Saves new bot name.
	 * @param string $botName
	 * @return bool
	 */
	public static function setBotName(string $botName): bool
	{
		Option::set(self::MODULE_ID, self::OPTION_BOT_NAME, $botName);

		return true;
	}

	/**
	 * Return bot shot description.
	 * @return string
	 */
	public static function getBotDesc(): string
	{
		$desc = Option::get(self::MODULE_ID, self::OPTION_BOT_DESC, '');
		if ($desc === '')
		{
			$desc = Loc::getMessage('SUPPORT_BOX_POSITION');
		}

		return $desc;
	}

	/**
	 * Saves new bot description.
	 * @param string $botDesc
	 * @return bool
	 */
	public static function setBotDesc(string $botDesc): bool
	{
		Option::set(self::MODULE_ID, self::OPTION_BOT_DESC, $botDesc);

		return true;
	}

	/**
	 * Returns url of the bot avatar picture.
	 * @return string
	 */
	public static function getBotAvatar(): string
	{
		return Option::get(self::MODULE_ID, self::OPTION_BOT_AVATAR, self::AVATAR);
	}

	/**
	 * Saves new url of the bot avatar picture.
	 * @param string $botAvatarUrl
	 * @return bool
	 */
	public static function setBotAvatar(string $botAvatarUrl): bool
	{
		Option::set(self::MODULE_ID, self::OPTION_BOT_AVATAR, $botAvatarUrl);

		return true;
	}

	/**
	 * Saves new bot messages.
	 * @param string $botMessages
	 * @return bool
	 */
	public static function setBotMessages(string $botMessages): bool
	{
		Option::set(self::MODULE_ID, self::OPTION_BOT_MESSAGES, $botMessages);

		return true;
	}

	/**
	 * Returns phrase by the code.
	 *
	 * @param string $code
	 * @param string $defaultPhrase
	 *
	 * @return string|null
	 */
	public static function getMessage(string $code, string $defaultPhrase = ''): ?string
	{
		static $messages;
		if ($messages === null)
		{
			$messages = unserialize(
				Option::get(self::MODULE_ID, self::OPTION_BOT_MESSAGES, 'a:0:{}'),
				['allowed_classes' => false]
			);
		}

		return isset($messages[$code]) ? $messages[$code] : $defaultPhrase;
	}

	//endregion

	//region Agent

	/**
	 * Refresh settings agent.
	 *
	 * @param bool $regular
	 * @return string
	 */
	public static function refreshAgent(bool $regular = true): string
	{
		do
		{
			$prevBotCode = self::getBotCode();
			if (!self::isEnabled() || empty($prevBotCode))
			{
				return '';
			}

			if (self::checkPublicUrl() !== true)
			{
				break;
			}

			$settings = self::getBotSettings([
				'BOT_ID' => self::getBotId()
			]);
			if (empty($settings))
			{
				break;
			}

			$botCode = $settings[self::OPTION_BOT_CODE];
			if (empty($botCode))
			{
				//err
				break;
			}

			$botParams = [];

			if ($prevBotCode !== $botCode)
			{
				$botParams['CODE'] = parent::BOT_CODE. '_'. $botCode;
				$botParams['APP_ID'] = $botCode;
			}

			if (!self::saveSettings($settings))
			{
				break;
			}

			$botId = self::getBotId();

			$botParams['PROPERTIES'] = [
				'NAME' => self::getBotName(),
				'WORK_POSITION' => self::getBotDesc(),
			];

			$botData = Im\User::getInstance($botId);
			$userAvatar = (int)Im\User::uploadAvatar(self::getBotAvatar(), $botId);
			if ($userAvatar > 0 && (int)$botData->getAvatarId() != $userAvatar)
			{
				$botParams['PROPERTIES']['PERSONAL_PHOTO'] = $userAvatar;
			}

			Im\Bot::clearCache();
			Im\Bot::update(['BOT_ID' => $botId], $botParams);

			self::registerCommands();
			self::registerApps();
		}
		while (false);

		$error = self::getError();
		if ($error->error === true && $error->msg !== '')
		{
			$helpDeskUrl = '';
			if (Loader::includeModule('ui'))
			{
				$helpDeskUrl = \Bitrix\UI\Util::getArticleUrlByCode(self::HELP_DESK_CODE);
			}

			$message = Loc::getMessage('SUPPORT_BOX_REFRESH_ERROR', [
				'#ERROR#' => $error->msg,
				'#HELP_DESK#' => $helpDeskUrl,
			]);

			if (Loader::includeModule('im'))
			{
				\CIMNotify::deleteBySubTag("IMBOT|SUPPORT|ERR");

				$notifyUsers = self::getAdministrators();
				foreach ($notifyUsers as $userId)
				{
					\CIMNotify::add([
						"TO_USER_ID" => $userId,
						"NOTIFY_TYPE" => \IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => self::MODULE_ID,
						"NOTIFY_EVENT" => 'refresh_error',
						"NOTIFY_SUB_TAG" => "IMBOT|SUPPORT|ERR",
						"NOTIFY_MESSAGE" => $message,
						"NOTIFY_MESSAGE_OUT" => $message,
						"RECENT_ADD" => 'Y',
					]);
				}
			}
		}
		else
		{
			if (Loader::includeModule('im'))
			{
				\CIMNotify::deleteBySubTag("IMBOT|SUPPORT|ERR");
			}
		}

		return $regular ? __METHOD__. '();' : '';
	}

	/**
	 * Sends $message to administrator group. Only for recent dialogs.
	 *
	 * @param string $message Message to send.
	 *
	 * @return void
	 */
	public static function notifyAdministrators($message): void
	{
		$notifyUsers = self::getAdministrators();
		$recentUsers = [];
		foreach (self::getRecentDialogs() as $dialog)
		{
			if ($dialog['RECENTLY_TALK'] === 'Y')
			{
				$recentUsers[] = (int)$dialog['USER_ID'];
			}
		}
		$notifyUsers = array_unique(array_intersect($notifyUsers, $recentUsers));
		foreach ($notifyUsers as $userId)
		{
			parent::sendMessage([
				'DIALOG_ID' => $userId,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);
		}
	}

	//endregion
}
