<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\ImBot\DialogSession;

class SaleSupport24 extends Network implements SupportBot
{
	use Mixin\NetworkMenuBot;

	public const
		BOT_CODE = 'support24sale',
		COMMAND_START_DIALOG = 'startDialog',

		OPTION_BOT_ID = 'support24_sale_bot_id',
		OPTION_BOT_CODE = 'support24_sale_code',
		OPTION_BOT_NAME = 'support24_sale_name',
		OPTION_BOT_DESC = 'support24_sale_desc',
		OPTION_BOT_AVATAR = 'support24_sale_avatar',
		OPTION_BOT_MESSAGES = 'support24_sale_messages',
		OPTION_BOT_TELEMETRY = 'sale_support24_telemetry',

		SCHEDULE_ACTION_HIDE_DIALOG = 'hide_dialog'
	;

	/**
	 * Returns OL code.
	 * @return string
	 */
	public static function getBotCode(): string
	{
		return Option::get('imbot', self::OPTION_BOT_CODE, '');
	}

	/**
	 * Register bot at portal.
	 *
	 * @param array{CODE: string} $params
	 *
	 * @return bool|int
	 */
	public static function register(array $params = [])
	{
		if (
			!Loader::includeModule('im')
			|| !Loader::includeModule('bitrix24')
		)
		{
			return false;
		}

		$botCode = self::getBotCode();
		if (!$botCode)
		{
			$settings = self::getBotSettings();
			if (!$settings)
			{
				return false;
			}

			if (!self::saveSettings($settings))
			{
				return false;
			}
		}

		$botId = parent::join(self::getBotCode());
		if (!$botId)
		{
			return false;
		}

		Option::set(self::MODULE_ID, self::OPTION_BOT_ID, $botId);

		self::updateBotProperties();

		self::addAgent([
			'agent' => 'refreshAgent()',/** @see SaleSupport24::refreshAgent */
			'class' => __CLASS__,
			'delay' => random_int(30, 360),
		]);

		return $botId;
	}

	/**
	 * Agent for deferred bot registration.
	 * @return string
	 */
	public static function delayRegister(): string
	{
		return self::register() ? '' : __METHOD__ . '();';
	}

	/**
	 * Refresh settings agent.
	 *
	 * @param bool $regular
	 *
	 * @return string
	 */
	public static function refreshAgent(bool $regular = true): string
	{
		$botId = self::getBotId();
		$settings = self::getBotSettings(['BOT_ID' => $botId]);
		if ($settings)
		{
			$previousCode = self::getBotCode();
			if (self::saveSettings($settings))
			{
				// line code change
				$currentCode = $settings[self::OPTION_BOT_CODE];
				if ($previousCode != $currentCode)
				{
					self::sendMessageForRecent(self::getMessage('CHANGE_CODE'));

					$currentLicence = \CBitrix24::getLicenseType();

					$http = self::instanceHttpClient();
					$http->query(
						'clientChangeLicence',
						[
							'BOT_ID' => self::getBotId(),
							'PREVIOUS_LICENCE_TYPE' => $currentLicence,
							'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
							'CURRENT_LICENCE_TYPE' => $currentLicence,
							'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
							'PREVIOUS_BOT_CODE' => $previousCode,
							'CURRENT_BOT_CODE' => $currentCode,
							'MESSAGE' => self::getMessage('CHANGE_CODE'),
						],
						false
					);

				}
			}
		}

		return $regular ? __METHOD__. '();' : '';
	}

	/**
	 * Unregister bot at portal.
	 *
	 * @param string $code Open Line Id.
	 * @param bool $notifyController Send unregister notification request to controller.
	 *
	 * @return bool
	 */
	public static function unRegister($code = '', $notifyController = true)
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
			self::sendRequestFinalizeSession();

			$result = parent::unRegister($botCode, $notifyController);

			if (is_array($result) && isset($result['result']))
			{
				$result = $result['result'];
				if ($result)
				{
					Option::delete(self::MODULE_ID, ['name' => parent::BOT_CODE.'_'.$botCode.'_bot_id']);
				}
			}
		}

		if ($result === false && $botId > 0)
		{
			$result = Im\Bot::unRegister(['BOT_ID' => $botId]);
		}

		if ($result)
		{
			Option::delete(self::MODULE_ID, ['name' => self::OPTION_BOT_ID]);
		}

		return $result;
	}

	/**
	 * Returns command's property list.
	 * @return array{class: string, handler: string, visible: bool, context: string}[]
	 */
	public static function getCommandList(): array
	{
		$commandList = parent::getCommandList();

		unset($commandList[self::COMMAND_UNREGISTER]);

		return array_merge(
			$commandList,
			[
				self::COMMAND_START_DIALOG => [
					'command' => self::COMMAND_START_DIALOG,
					'handler' => 'onCommandAdd',/** @see SaleSupport24::onCommandAdd */
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
			if (Loader::includeModule('bitrix24'))
			{
				if (\CBitrix24::isDemoLicense())
				{
					$params['PORTAL_TARIFF'] = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
				}
			}

			$settings = parent::getBotSettings($params);
			if (empty($settings))
			{
				return null;
			}

			$result = [];
			$mirrors = [
				self::OPTION_BOT_CODE,
				self::OPTION_BOT_NAME,
				self::OPTION_BOT_DESC,
				self::OPTION_BOT_AVATAR,
				self::OPTION_BOT_MESSAGES,
			];
			foreach ($mirrors as $prop)
			{
				if (isset($settings[$prop]))
				{
					$result[$prop] = $settings[$prop];
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
		$updateBotProperties = false;
		foreach ($settings as $optionName => $optionValue)
		{
			if (Option::get(self::MODULE_ID, $optionName, '') != $optionValue)
			{
				if (
					in_array($optionName, [
						self::OPTION_BOT_NAME,
						self::OPTION_BOT_DESC,
						self::OPTION_BOT_AVATAR,
					])
				)
				{
					$updateBotProperties = true;
				}

				Option::set(self::MODULE_ID, $optionName, $optionValue);
			}
		}

		// update im bot props
		if ($updateBotProperties)
		{
			self::updateBotProperties();
		}

		return true;
	}

	/**
	 * @return int
	 */
	public static function getBotId(): int
	{
		return (int)Option::get(self::MODULE_ID, self::OPTION_BOT_ID, 0);
	}

	/**
	 * @return string
	 */
	public static function getBotName(): string
	{
		return Option::get(self::MODULE_ID, self::OPTION_BOT_NAME, '');
	}

	/**
	 * @return string
	 */
	public static function getBotDesc(): string
	{
		return Option::get(self::MODULE_ID, self::OPTION_BOT_DESC, '');
	}

	/**
	 * @return string
	 */
	public static function getBotAvatar(): string
	{
		return Option::get(self::MODULE_ID, self::OPTION_BOT_AVATAR, '');
	}

	/**
	 * @return bool
	 */
	public static function isActiveSupport(): bool
	{
		//todo: Add settings flags or something else.
		return true;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage(): bool
	{
		return (bool)self::getBotAvatar() !== true;
	}

	//endregion

	//region Event handlers

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
		if (!self::isActiveSupport())
		{
			return new ImBot\Error(__METHOD__, 'SUPPORT_DISABLED', 'Support line disabled on this portal');
		}
		else if (isset($params['LINE']['CODE']) && $params['LINE']['CODE'] !== self::getBotCode())
		{
			return new ImBot\Error(__METHOD__, 'CODE_MISMATCH', 'Support line code is not correct for this portal');
		}

		return parent::onReceiveCommand($command, $params);
	}

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

		$messageFields = $joinFields;
		$messageFields['DIALOG_ID'] = $dialogId;

		if ($messageFields['CHAT_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage([
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
			}

			$chat = new \CIMChat(self::getBotId());
			$chat->deleteUser(mb_substr($dialogId, 4), self::getBotId());

			return true;
		}

		if (self::isActiveSupport())
		{
			$message = self::getMessage('WELCOME');
		}
		else
		{
			$message = self::getMessage('MESSAGE_END');
		}

		if (empty($message))
		{
			return true;
		}

		self::sendMessage([
			'DIALOG_ID' => $messageFields['USER_ID'],
			'MESSAGE' => $message,
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		]);

		return true;
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
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::checkMembershipRestriction($messageFields))
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage([
					'DIALOG_ID' => 'chat'.$messageFields['CHAT_ID'],
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
			}

			(new \CIMChat(self::getBotId()))->deleteUser($messageFields['CHAT_ID'], self::getBotId());

			return true;
		}

		$message = '';
		if (!self::isActiveSupport())
		{
			$message = self::getMessage('MESSAGE_END');
		}

		if (!empty($message))
		{
			self::markMessageUndelivered($messageId);

			self::sendMessage([
				'DIALOG_ID' => $messageFields['FROM_USER_ID'],
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

			return true;
		}

		parent::onMessageAdd($messageId, $messageFields);

		self::sendTelemetry($messageFields);

		return true;
	}

	/**
	 * @see \Bitrix\ImBot\Event::onStartWriting
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		if (!self::checkTypingRestriction($params))
		{
			return false;
		}
		if (!self::isActiveSupport())
		{
			return false;
		}

		if ($params['BOT_ID'] == $params['DIALOG_ID'])
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}

		$dialogId = (string)$params['DIALOG_ID'];

		if ((int)self::instanceDialogSession(self::getBotId(), $dialogId)->getParam('CLOSED') == 1)
		{
			self::instanceDialogSession(self::getBotId(), $dialogId)->update([
				'GREETING_SHOWN' => 'N',
			]);
		}

		if (self::allowSendStartMessage($params))
		{
			$message = self::getMessage('DIALOG_START');
			if (!empty($message))
			{
				self::sendMessage([
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $message,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N',
					'PARAMS' => [Network::MESSAGE_PARAM_ALLOW_QUOTE => 'N'],
				]);
				self::startDialogSession([
					'BOT_ID' => self::getBotId(),
					'DIALOG_ID' => $dialogId,
					'GREETING_SHOWN' => 'Y',
				]);
			}
		}

		return parent::onStartWriting($params);
	}

	/**
	 * Checks if starting message at this dialog has been sent.
	 *
	 * @param array $params
	 * <pre>
	 * [
	 * 	(int) BOT_ID Bot id.
	 * 	(string) DIALOG_ID Dialog id.
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function allowSendStartMessage(array $params)
	{
		if (empty($params['DIALOG_ID']) && !empty($params['USER_ID']))
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}
		$sess = self::instanceDialogSession(self::getBotId(), $params['DIALOG_ID']);
		if ($sess->getParam('GREETING_SHOWN') === 'Y')
		{
			return false;
		}

		return true;
	}

	//endregion

	/**
	 * @inheritDoc
	 */
	public static function finishDialogSession($params)
	{
		if (!self::isActiveSupport())
		{
			return false;
		}

		self::scheduleAction($params['DIALOG_ID'], self::SCHEDULE_ACTION_HIDE_DIALOG, '', 1);

		return parent::finishDialogSession($params);
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

		$botParams = [
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd', /** @see SaleSupport24::onMessageAdd */
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',  /** @see SaleSupport24::onChatStart */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see SaleSupport24::onBotDelete */
			'TEXT_CHAT_WELCOME_MESSAGE' => '',
			'TEXT_PRIVATE_WELCOME_MESSAGE' => '',
			'VERIFIED' => 'Y',
			'HIDDEN' => 'Y',
			'CODE' => 'network_'.self::getBotCode(),
			'APP_ID' => self::getBotCode(),
			'PROPERTIES' => [
				'LOGIN' => 'bot_support24_sale',
				'NAME' => self::getBotName(),
				'WORK_POSITION' => self::getBotDesc()
			]
		];

		$botData = Im\User::getInstance(self::getBotId());
		$userAvatar = Im\User::uploadAvatar(self::getBotAvatar(), self::getBotId());
		if ($userAvatar && $botData->getAvatarId() != $userAvatar)
		{
			$botParams['PROPERTIES']['PERSONAL_PHOTO'] = $userAvatar;
		}

		Im\Bot::update(['BOT_ID' => self::getBotId()], $botParams);

		self::registerCommands(self::getBotId());
		self::registerApps(self::getBotId());

		return true;
	}

	protected static function sendTelemetry($messageFields): bool
	{
		$telemetry = Option::get(self::MODULE_ID, self::OPTION_BOT_TELEMETRY, '');
		if (!empty($telemetry))
		{
			$attach = new \CIMMessageParamAttach(null, \Bitrix\Im\Color::getColor('MARENGO'));
			$attach->AddMessage($telemetry);

			self::clientMessageAdd([
				'BOT_ID' => self::getBotId(),
				'USER_ID' => $messageFields['FROM_USER_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => ['TEXT' => Loc::getMessage('SALE_SUPPORT24_PROCESSING_LOG'),],
				'ATTACH' => $attach->getJson(),
				'PARAMS' => [
					'CLASS' => 'bx-messenger-content-item-system',
				]
			]);

			Option::delete(self::MODULE_ID, ['name' => self::OPTION_BOT_TELEMETRY]);
		}

		return true;
	}

	/**
	 * Sends finalize session notification.
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(string) MESSAGE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function sendRequestFinalizeSession(array $params = [])
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		(new DialogSession)->clearSessions(['BOT_ID' => self::getBotId()]);

		$currentLicence = \CBitrix24::getLicenseType();

		$message = $params['MESSAGE'] ?? '';

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientRequestFinalizeSession',
			[
				'BOT_ID' => self::getBotId(),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'CURRENT_BOT_CODE' => self::getBotCode(),
				'MESSAGE' => $message,
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	private static function sendMessageForRecent(string $message): bool
	{
		$users = [self::getBotId()];
		$chats = [];
		foreach (self::getRecentDialogs() as $dialog)
		{
			if ($dialog['MESSAGE_TYPE'] == \IM_MESSAGE_CHAT && in_array($dialog['CHAT_ID'], $chats))
			{
				continue;
			}
			elseif ($dialog['MESSAGE_TYPE'] == \IM_MESSAGE_PRIVATE && in_array($dialog['USER_ID'], $users))
			{
				continue;
			}

			if ($dialog['MESSAGE_TYPE'] == \IM_MESSAGE_CHAT)
			{
				$chats[] = $dialog['CHAT_ID'];
			}
			elseif ($dialog['MESSAGE_TYPE'] == \IM_MESSAGE_PRIVATE)
			{
				$users[] = $dialog['USER_ID'];
			}

			if ($dialog['RECENTLY_TALK'] == 'Y' && $dialog['MESSAGE_TYPE'] == \IM_MESSAGE_PRIVATE)
			{
				self::sendMessage([
					'DIALOG_ID' => $dialog['USER_ID'],
					'MESSAGE' => $message,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
			}
			else
			{
				Im\Model\MessageTable::add([
					'CHAT_ID' => $dialog['CHAT_ID'],
					'AUTHOR_ID' => self::getBotId(),
					'MESSAGE' => self::replacePlaceholders($message, $dialog['USER_ID'])
				]);
			}
		}

		return true;
	}

	/**
	 * @param string $message
	 * @param int $userId
	 *
	 * @return string
	 */
	public static function replacePlaceholders($message, $userId = 0): string
	{
		return Support24::replacePlaceholders($message, $userId);
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public static function getMessage($code): string
	{
		static $messages;
		if ($messages === null)
		{
			$messages = unserialize(
				Option::get('imbot', self::OPTION_BOT_MESSAGES, 'a:0:{}'),
				['allowed_classes' => false]
			);
		}

		return $messages[$code] ?? '';
	}

	/**
	 * Handler for "im:OnSessionVote" event.
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onSessionVote(array $params): bool
	{
		if ($params['BOT_ID'] == $params['DIALOG_ID'])
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}

		self::scheduleAction($params['DIALOG_ID'], self::SCHEDULE_ACTION_HIDE_DIALOG, '', 5);

		return self::clientSessionVote($params);
	}

	/**
	 * @param string $target
	 * @param string $action
	 * @param string $code
	 * @return bool
	 */
	public static function execScheduleAction($target, $action, $code = ''): bool
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if ($action === self::SCHEDULE_ACTION_HIDE_DIALOG)
		{
			$session = self::instanceDialogSession((int)self::getBotId(), $target);

			$sessionActive = (
				$session->getSessionId() > 0
				&& $session->getParam('DATE_FINISH') === null
			);

			if (!$sessionActive)// don't hide active session
			{
				if (\Bitrix\Im\Common::isChatId($target))
				{
					$chatId = self::getChatId($target);
					if ($chatId > 0)
					{
						$relations = Im\Chat::getRelation($chatId, ['SELECT' => ['ID', 'USER_ID']]);
						foreach ($relations as $relation)
						{
							if ((int)$relation['USER_ID'] != static::getBotId())
							{
								\CIMContactList::dialogHide($target, (int)$relation['USER_ID']);
							}
						}
					}
				}
				else
				{
					\CIMContactList::dialogHide(self::getBotId(), $target);
				}
			}
		}

		return true;
	}

	public static function onCommandAdd($messageId, $messageFields): bool
	{
		$command = static::getCommandByMessage($messageFields);
		if (!$command)
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_START_DIALOG)
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
					Mixin\MESSAGE_PARAM_MENU_ACTION => 'SKIP:MENU',
				],
			]);

			$userGender = Im\User::getInstance(self::getCurrentUser()->getId())->getGender();
			$forward = self::getMessage('START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			if (!$forward)
			{
				$forward = Loc::getMessage('SALE_SUPPORT24_START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
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


		return parent::onCommandAdd($messageId, $messageFields);
	}
}