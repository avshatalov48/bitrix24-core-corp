<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\ImBot\Log;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\Main\Localization\Loc;

class Support24 extends Network implements NetworkBot, MenuBot
{
	public const
		BOT_CODE = 'support24',

		COMMAND_SUPPORT24 = 'support24',
		COMMAND_START_DIALOG = 'startDialog',
		COMMAND_ACTIVATE_PARTNER = 'activatePartnerSupport',
		COMMAND_DEACTIVATE_PARTNER = 'deactivatePartnerSupport',
		COMMAND_DECLINE_PARTNER_REQUEST = 'declinePartnerRequest',

		SUPPORT_TIME_UNLIMITED = -1,
		SUPPORT_TIME_NONE = 0,

		SCHEDULE_ACTION_WELCOME = 'welcome',
		SCHEDULE_ACTION_INVOLVEMENT = 'involvement',
		SCHEDULE_ACTION_MESSAGE = 'message',
		SCHEDULE_ACTION_PARTNER_JOIN = 'partner_join',
		SCHEDULE_ACTION_HIDE_DIALOG = 'hide_dialog',
		SCHEDULE_ACTION_CHECK_STAGE = 'check_stage',

		SCHEDULE_DELETE_ALL = null,

		INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME = 8, // hour
		HIDE_DIALOG_TIME = 5; // minuts

	protected const LIST_BOX_SUPPORT_CODES = [
		'ru' => '4df232699a9e1d0487c3972f26ea8d25',
		'default' => '1a146ac74c3a729681c45b8f692eab73',
	];

	public const
		OPTION_BOT_ID = 'support24_bot_id',
		OPTION_BOT_WELCOME_SHOWN = 'support24_welcome_message',
		OPTION_BOT_SUPPORT_LEVEL = 'support24_support_level',
		OPTION_BOT_PAID_CODE = 'support24_paid_code',
		OPTION_BOT_FREE_CODE = 'support24_free_code',
		OPTION_BOT_PAID_ACTIVE = 'support24_paid_active',
		OPTION_BOT_DEMO_ACTIVE = 'support24_demo_active',
		OPTION_BOT_STAGE_ACTIVE = 'support24_stage_active',
		OPTION_BOT_FREE_DAYS = 'support24_free_days',
		OPTION_BOT_FREE_START_DATE = 'support24_free_start_date',
		OPTION_BOT_FREE_FOR_ALL = 'support24_free_for_all',
		OPTION_BOT_PAID_FOR_ALL = 'support24_paid_for_all',
		OPTION_BOT_FREE_NAME = 'support24_free_name',
		OPTION_BOT_FREE_DESC = 'support24_free_desc',
		OPTION_BOT_FREE_AVATAR = 'support24_free_avatar',
		OPTION_BOT_PAID_NAME = 'support24_paid_name',
		OPTION_BOT_PAID_DESC = 'support24_paid_desc',
		OPTION_BOT_PAID_AVATAR = 'support24_paid_avatar',
		OPTION_BOT_FREE_MENU = 'support24_free_menu',
		OPTION_BOT_PAID_MENU = 'support24_paid_menu',
		OPTION_BOT_FREE_MENU_STAGE = 'support24_free_menu_stage',
		OPTION_BOT_PAID_MENU_STAGE = 'support24_paid_menu_stage',
		OPTION_BOT_FREE_MESSAGES = 'support24_free_messages',
		OPTION_BOT_PAID_MESSAGES = 'support24_paid_messages';

	//region Register

	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 *
	 * @return bool|int
	 */
	public static function register(array $params = [])
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$botId = parent::join(self::getBotCode());
		if (!$botId)
		{
			return false;
		}

		Option::set(self::MODULE_ID, self::OPTION_BOT_ID, $botId);
		Option::set(self::MODULE_ID, self::OPTION_BOT_SUPPORT_LEVEL, self::getSupportLevel());

		self::updateBotProperties();

		$eventManager = Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible(
			'main',
			'OnAfterSetOption_~controller_group_name',
			self::MODULE_ID,
			__CLASS__,
			'onAfterLicenseChange'/** @see Support24::onAfterLicenseChange */
		);
		$eventManager->registerEventHandlerCompatible(
			'main',
			'OnAfterUserAuthorize',
			self::MODULE_ID,
			__CLASS__,
			'onAfterUserAuthorize'/** @see Support24::onAfterUserAuthorize */
		);

		foreach (self::getCommandList() as $commandName => $commandParam)
		{
			Im\Command::register([
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => $commandName,
				'HIDDEN' => $commandParam['visible'] === true ? 'N' : 'Y',
				'CLASS' => $commandParam['class'] ?? __CLASS__,
				'METHOD_COMMAND_ADD' => $commandParam['handler'] ?? 'onCommandAdd' /** @see Support24::onCommandAdd */
			]);
		}

		self::scheduleAction(1, self::SCHEDULE_ACTION_WELCOME, '', 10);

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
	public static function unRegister($code = '', $notifyController = true)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$result = false;
		$code = self::getBotCode();
		$botId = self::getBotId();

		if ($code !== '')
		{
			self::sendRequestFinalizeSession();

			$result = parent::unRegister($code, $notifyController);

			if (is_array($result) && isset($result['result']))
			{
				$result = $result['result'];
				if ($result)
				{
					Option::delete(self::MODULE_ID, ['name' => parent::BOT_CODE.'_'.$code.'_bot_id']);
				}
			}
		}

		if ($result === false && $botId > 0)
		{
			$result = Im\Bot::unRegister(['BOT_ID' => $botId]);
		}

		if ($result)
		{
			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);

			Option::set(self::MODULE_ID, self::OPTION_BOT_ID, 0);

			$eventManager = Main\EventManager::getInstance();
			$eventManager->unregisterEventHandler(
				'main',
				'OnAfterSetOption_~controller_group_name',
				self::MODULE_ID,
				__CLASS__,
				'onAfterLicenseChange'/** @see Support24::onAfterLicenseChange */
			);
			$eventManager->unregisterEventHandler(
				'main',
				'OnAfterUserAuthorize',
				self::MODULE_ID,
				__CLASS__,
				'onAfterUserAuthorize'/** @see Support24::onAfterUserAuthorize */
			);
		}

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

		return array_merge($commandList, [
			self::COMMAND_SUPPORT24 => [
				'command' => self::COMMAND_SUPPORT24,
				'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => self::getBotId(),
					],
				],
			],
			self::COMMAND_MENU => [
				'command' => self::COMMAND_MENU,
				'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => self::getBotId(),
					],
				],
			],
			self::COMMAND_START_DIALOG => [
				'command' => self::COMMAND_START_DIALOG,
				'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'CHAT_ENTITY_TYPE' => ImBot\Service\Notifier::CHAT_ENTITY_TYPE,
					],
				],
			],
			self::COMMAND_QUEUE_NUMBER => [
				'command' => self::COMMAND_QUEUE_NUMBER,
				'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => self::getBotId(),
					],
					[
						'COMMAND_CONTEXT' => 'TEXTAREA',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => self::getBotId(),
					],
				],
			],
		]);
	}

	//endregion

	//region Bitrix24

	/**
	 * @return string
	 */
	public static function getUserSupportLevel()
	{
		if (Partner24::getBotId() && Partner24::isActiveSupport())
		{
			return self::SUPPORT_LEVEL_PARTNER;
		}
		else if (self::getBotId() > 0)
		{
			return self::getSupportLevel();
		}

		return self::SUPPORT_LEVEL_NONE;
	}

	/**
	 * @return string
	 */
	public static function getSupportLevel()
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			if (self::isActivePaidSupport())
			{
				$supportLevel = self::SUPPORT_LEVEL_PAID;
			}
			else
			{
				$supportLevel = self::SUPPORT_LEVEL_FREE;
			}
		}
		else
		{
			$supportLevel = self::SUPPORT_LEVEL_PAID;
		}

		return $supportLevel;
	}

	/**
	 * @return string
	 */
	public static function getLicenceLanguage()
	{
		$lang = 'en';
		if (Main\Loader::includeModule('bitrix24'))
		{
			$prefix = \CBitrix24::getLicensePrefix();
			if ($prefix)
			{
				$lang = $prefix;
			}
		}
		else
		{
			if (Main\Localization\CultureTable::getList(['filter' => ['=CODE' => 'ru']])->fetch())
			{
				$lang = 'ru';
			}
		}

		return $lang;
	}

	//endregion

	//region Param getters

	/**
	 * @return string
	 */
	public static function getBotCode()
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
			{
				$code = Option::get('imbot', self::OPTION_BOT_PAID_CODE, "");
			}
			else
			{
				$code = Option::get('imbot', self::OPTION_BOT_FREE_CODE, "");
			}
		}
		else
		{
			$lang = self::getLicenceLanguage();

			if (array_key_exists($lang, self::LIST_BOX_SUPPORT_CODES))
			{
				$code = self::LIST_BOX_SUPPORT_CODES[$lang];
			}
			else
			{
				$code = self::LIST_BOX_SUPPORT_CODES['default'];
			}
		}

		return $code;
	}

	/**
	 * Is bot enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return self::getBotId() > 0;
	}

	/**
	 * @return bool|int
	 */
	public static function getBotId()
	{
		return Option::get('imbot', self::OPTION_BOT_ID, 0);
	}

	/**
	 * @return string
	 */
	public static function getBotName()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_NAME : self::OPTION_BOT_PAID_NAME;
		return Option::get('imbot', $optionName, '');
	}

	/**
	 * @return string
	 */
	public static function getBotDesc()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_DESC : self::OPTION_BOT_PAID_DESC;
		return Option::get('imbot', $optionName, '');
	}

	/**
	 * @return string
	 */
	public static function getBotAvatar()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_AVATAR : self::OPTION_BOT_PAID_AVATAR;
		return Option::get('imbot', $optionName, '');
	}

	/**
	 * Checks if bot has ITR menu.
	 *
	 * @return bool
	 */
	public static function hasBotMenu()
	{
		return !empty(self::getBotMenu());
	}

	/**
	 * Returns stored data for ITR menu.
	 *
	 * @return array
	 */
	public static function getBotMenu()
	{
		if (self::isStagePortal())
		{
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
			{
				$menuType = self::OPTION_BOT_FREE_MENU_STAGE;
			}
			else
			{
				$menuType = self::OPTION_BOT_PAID_MENU_STAGE;
			}
		}
		elseif (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			$menuType = self::OPTION_BOT_FREE_MENU;
		}
		else
		{
			$menuType = self::OPTION_BOT_PAID_MENU;
		}

		static $structure;
		if ($structure === null)
		{
			$structure = [];

			$json = Option::get(self::MODULE_ID, $menuType, '');

			if ($json)
			{
				try
				{
					$structure = Main\Web\Json::decode($json);
				}
				catch (Main\ArgumentException $e)
				{
				}
			}
		}

		return $structure;
	}

	/**
	 * @return int
	 */
	public static function getFreeSupportLifeTime()
	{
		return (int)Option::get('imbot', self::OPTION_BOT_FREE_DAYS, 16);
	}

	/**
	 * @return bool
	 */
	public static function isFreeSupportLifeTimeExpired()
	{
		$generationDate = (int)Option::get('imbot', self::OPTION_BOT_FREE_START_DATE, 0);
		if ($generationDate == 0)
		{
			Option::set('imbot', self::OPTION_BOT_FREE_START_DATE, time());
			return true;
		}

		$isActive = time() - $generationDate < 86400 * self::getFreeSupportLifeTime();

		return !$isActive;
	}

	/**
	 * @return bool
	 */
	public static function isActiveFreeSupport()
	{
		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
		{
			return false;
		}

		if (self::getFreeSupportLifeTime() == self::SUPPORT_TIME_UNLIMITED)
		{
			return true;
		}

		return !self::isFreeSupportLifeTimeExpired();
	}

	/**
	 * @return bool
	 */
	public static function isActiveFreeSupportForAll()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_FREE_FOR_ALL, false);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isActiveFreeSupportForUser($userId)
	{
		if (!self::getBotId())
		{
			return false;
		}

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
		{
			return false;
		}

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (self::isActivePartnerSupport() && !self::isUserIntegrator($userId))
		{
			return false;
		}

		if (self::isActiveFreeSupportForAll())
		{
			return true;
		}

		if (self::isUserAdmin($userId) || self::isUserIntegrator($userId))
		{
			return true;
		}

		return false;
	}



	/**
	 * @return bool
	 */
	public static function isActivePartnerSupport()
	{
		return Partner24::isEnabled() && Partner24::isActiveSupport();
	}

	/**
	 * @return bool
	 */
	public static function isActivePaidSupport()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_PAID_ACTIVE, false);
	}

	/**
	 * @return bool
	 */
	public static function isActivePaidSupportForAll()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_PAID_FOR_ALL, false);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isActivePaidSupportForUser($userId)
	{
		if (!self::getBotId())
		{
			return false;
		}

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_PAID)
		{
			return false;
		}

		if (self::isActivePartnerSupport() && !self::isUserIntegrator($userId))
		{
			return false;
		}

		if (self::isActivePaidSupportForAll())
		{
			return true;
		}

		if (!$userId)
		{
			return false;
		}

		return self::isUserAdmin($userId) || self::isUserIntegrator($userId);
	}

	/**
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage()
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
		if ($command === self::COMMAND_OPERATOR_QUEUE_NUMBER)
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
	 * Compatibility alias to the onChatStart method.
	 * @todo Remove it.
	 * @deprecated
	 */
	public static function onWelcomeMessage($dialogId, $joinFields)
	{
		return self::onChatStart($dialogId, $joinFields);
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
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$message = '';

		$messageFields = $joinFields;
		$messageFields['DIALOG_ID'] = $dialogId;

		// allow support bot membership in notification channel
		if ($messageFields['CHAT_ENTITY_TYPE'] === ImBot\Service\Notifier::CHAT_ENTITY_TYPE)
		{
			return true;
		}

		if ($messageFields['CHAT_TYPE'] != \IM_MESSAGE_PRIVATE)
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
			$chat->DeleteUser(mb_substr($dialogId, 4), self::getBotId());

			return true;
		}

		if (
			self::isActivePartnerSupport()
			&& !self::isUserIntegrator($messageFields['USER_ID'])
		)
		{
			$message = self::getMessage('MESSAGE_PARTNER');
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isUserIntegrator($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME_INTEGRATOR');
			}
			else if (self::isActiveFreeSupport())
			{
				if (self::isActiveFreeSupportForUser($messageFields['USER_ID']))
				{
					$message = self::getMessage('WELCOME');
				}
				else
				{
					$message = self::getMessage('WELCOME_LIMITED');
				}
			}
			else
			{
				$message = self::getMessage('WELCOME_END');
			}
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (self::isUserIntegrator($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME_INTEGRATOR');
			}
			else if (self::isActivePaidSupportForUser($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME');
			}
			else
			{
				$message = self::getMessage('WELCOME_LIMITED');
			}
		}

		if (empty($message))
		{
			return true;
		}

		\CUserOptions::SetOption(
			self::MODULE_ID,
			self::OPTION_BOT_WELCOME_SHOWN,
			time(),
			false,
			$messageFields['USER_ID']
		);

		self::sendMessage([
			'DIALOG_ID' => $messageFields['USER_ID'],
			'MESSAGE' => $message,
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		]);

		if (
			self::getSupportLevel() == self::SUPPORT_LEVEL_FREE
			&& self::isActiveFreeSupport()
			&& self::isActiveFreeSupportForUser($messageFields['USER_ID'])
		)
		{
			self::scheduleAction($messageFields['USER_ID'], self::SCHEDULE_ACTION_INVOLVEMENT, '', 24*60);
		}

		return true;
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
				'BOT_ID' => static::getBotId(),
				'DIALOG_ID' => (int)$messageFields['DIALOG_ID'],
				'GREETING_SHOWN' => 'Y',
			]);
			self::stopMenuTrack((int)$messageFields['DIALOG_ID']);
		}

		return parent::operatorMessageAdd($messageId, $messageFields);
	}

	/**
	 * Event handler on `clientMessageAdd`.
	 *
	 * @inheritDoc
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		// check restrictions
		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
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

			$chat = new \CIMChat(self::getBotId());
			$chat->DeleteUser($messageFields['CHAT_ID'], self::getBotId());

			return true;
		}

		$fromUserId = (int)$messageFields['FROM_USER_ID'];

		$allowShowMenu = self::hasBotMenu();
		$warningRestrictionMessage = '';
		if (
			self::isActivePartnerSupport() &&
			self::isUserIntegrator($fromUserId)
		)
		{
			// check if integrator may write to support24 OL
			if (!Partner24::allowIntegratorAccessAlongSupport24())
			{
				// show message about partner OL
				$warningRestrictionMessage = self::getMessage('MESSAGE_PARTNER_INTEGRATOR');
				$allowShowMenu = false;
			}
		}
		elseif (
			self::isActivePartnerSupport() &&
			!self::isUserIntegrator($fromUserId)
		)
		{
			$warningRestrictionMessage = self::getMessage('MESSAGE_PARTNER');
			$allowShowMenu = false;
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isActiveFreeSupport())
			{
				if (!self::isActiveFreeSupportForUser($fromUserId))
				{
					$warningRestrictionMessage = self::getMessage('MESSAGE_LIMITED');
				}
			}
			else if (!self::isUserIntegrator($fromUserId))
			{
				$warningRestrictionMessage = self::getMessage('MESSAGE_END');
			}
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!self::isActivePaidSupportForUser($fromUserId))
			{
				$warningRestrictionMessage = self::getMessage('MESSAGE_LIMITED');
			}
		}

		// check user vote for session by direct text input '1' or '0'
		if (
			$messageFields['COMMAND_CONTEXT'] === 'TEXTAREA'
			&& ($messageFields['MESSAGE'] === '1' || $messageFields['MESSAGE'] === '0')
		)
		{
			$i = 0;
			$voteMessage = null;
			$lastMessages = (new \CIMMessage())->getLastMessage($fromUserId, self::getBotId(), false, false);
			foreach ($lastMessages['message'] as $message)
			{
				if (
					$message['senderId'] == self::getBotId()
					&& isset($message['params'], $message['params'][self::MESSAGE_PARAM_IMOL_VOTE])
					&& isset($message['params'], $message['params'][self::MESSAGE_PARAM_IMOL_VOTE_LIKE])
					&& isset($message['params'], $message['params'][self::MESSAGE_PARAM_IMOL_VOTE_DISLIKE])
					&& (int)$message['params'][self::MESSAGE_PARAM_IMOL_VOTE] > 0 //SESSION_ID
				)
				{
					$voteMessage = $message;
					break;
				}
				// check only 7 last messages
				if (++$i > 7)
				{
					break;
				}
			}
			if ($voteMessage)
			{
				$isActionLike = $messageFields['MESSAGE'] === '1';

				self::sendMessage([
					'DIALOG_ID' => $fromUserId,
					'MESSAGE' => $isActionLike
						? $voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE_LIKE]
						: $voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE_DISLIKE],
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N',
				]);

				$voteParams = [
					'BOT_ID' => self::getBotId(),
					'USER_ID' => $fromUserId,
					'ACTION' => ($isActionLike ? 'like' : 'dislike'),
					'SESSION_ID' => $voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE],//SESSION_ID
					'MESSAGE' => [
						'MESSAGE' => $voteMessage['text'],
						'PARAMS' => $voteMessage['params'],//CONNECTOR_MID
					]
				];

				return self::clientSessionVote($voteParams);
			}
		}

		// ITR menu on before any dialog starts
		if ($allowShowMenu)
		{
			if (!self::isMenuTrackFinished($fromUserId))
			{
				$prevMenuState = self::getMenuState($fromUserId);
				$lastMenuItemId = is_array($prevMenuState['track']) ? end($prevMenuState['track']) : null;

				if (!$lastMenuItemId && !empty($warningRestrictionMessage))
				{
					// show restriction warning message first
					self::sendMessage([
						'DIALOG_ID' => $fromUserId,
						'MESSAGE' => $warningRestrictionMessage,
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N',
					]);
				}

				if ($lastMenuItemId !== self::MENU_EXIT_ID)
				{
					self::markMessageUndelivered($messageId);

					$undeliveredMessage = self::getMessage('MESSAGE_UNDELIVERED');
					if ($undeliveredMessage)
					{
						self::sendMessage([
							'DIALOG_ID' => $fromUserId,
							'MESSAGE' => $undeliveredMessage,
							'SYSTEM' => 'N',
							'URL_PREVIEW' => 'N',
						]);
					}

					$menuState = self::showMenu([
						'BOT_ID' => self::getBotId(),
						'DIALOG_ID' => $fromUserId,
						'FULL_REDRAW' => true,
					]);
					$menuState['messages'][] = $messageId;

					self::saveMenuState(
						$fromUserId,
						$menuState
					);

					if (!self::isMenuTrackFinished($fromUserId, $menuState))
					{
						return false;//continue menu travel
					}
				}
			}
		}
		elseif (
			// disallow start dialog
			!empty($warningRestrictionMessage)
			// allow start dialog if greeting has been shown @see
			&& self::allowSendStartMessage(['BOT_ID' => self::getBotId(), 'USER_ID' => $messageFields['DIALOG_ID']])
		)
		{
			self::markMessageUndelivered($messageId);

			// show restriction warning message
			self::sendMessage([
				'DIALOG_ID' => $fromUserId,
				'MESSAGE' => $warningRestrictionMessage,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N',
			]);

			return true;
		}
		elseif (!empty($messageFields['DIALOG_ID']))
		{
			self::startDialogSession([
				'BOT_ID' => self::getBotId(),
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'GREETING_SHOWN' => 'Y',
			]);
			self::stopMenuTrack((int)$messageFields['DIALOG_ID']);
		}

		return parent::onMessageAdd($messageId, $messageFields);
	}

	/**
	 * Handler for `StartWriting` event.
	 *
	 * @inheritDoc
	 *
	 * @param array $params
	 * @param int $params['BOT_ID'] Bot id.
	 * @param int $params['DIALOG_ID'] Dialog id.
	 * @param int $params['USER_ID'] User id.
	 *
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		if (self::isActivePartnerSupport())
		{
			if (!self::isUserIntegrator($params['USER_ID']))
			{
				return false;
			}
		}

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isActiveFreeSupport())
			{
				if (!self::isActiveFreeSupportForUser($params['USER_ID']))
				{
					return false;
				}
			}
			else if (!self::isUserIntegrator($params['USER_ID']))
			{
				return false;
			}
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!self::isActivePaidSupportForUser($params['USER_ID']))
			{
				return false;
			}
		}

		// ITR menu on before any dialog starts
		if (self::hasBotMenu())
		{
			if (!self::isMenuTrackFinished((int)$params['USER_ID']))
			{
				$menuState = self::showMenu([
					'BOT_ID' => $params['BOT_ID'],
					'DIALOG_ID' => $params['USER_ID'],
				]);
				self::saveMenuState(
					(int)$params['USER_ID'],
					$menuState
				);

				if (!self::isMenuTrackFinished((int)$params['USER_ID'], $menuState))
				{
					return false;//continue menu travel
				}
			}
		}

		// Show greeting message on before any dialog starts
		elseif (self::allowSendStartMessage($params))
		{
			// Message for only three state: free, paid and partner.
			$message = '';
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
			{
				if (self::isUserIntegrator($params['USER_ID']))
				{
					$message = self::getMessage('DIALOG_START_INTEGRATOR', self::getSupportLevel());
				}
				else
				{
					$message = self::getMessage('DIALOG_START', self::getSupportLevel());
				}
			}
			elseif (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
			{
				if (self::isUserIntegrator($params['USER_ID']))
				{
					$message = self::getMessage('DIALOG_START_INTEGRATOR', self::getSupportLevel());
				}
				else
				{
					$message = self::getMessage('DIALOG_START', self::getSupportLevel());
				}
			}

			if (!empty($message))
			{
				self::sendMessage([
					'DIALOG_ID' => $params['USER_ID'],
					'MESSAGE' => $message,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
				self::startDialogSession([
					'BOT_ID' => $params['BOT_ID'],
					'DIALOG_ID' => $params['USER_ID'],
					'GREETING_SHOWN' => 'Y',
				]);
				self::stopMenuTrack((int)$params['USER_ID']);
			}
		}

		return parent::onStartWriting($params);
	}

	/**
	 * @inheritDoc
	 */
	protected static function startDialogSession($params)
	{
		if (!parent::startDialogSession($params))
		{
			return false;
		}

		self::deleteScheduledAction($params['DIALOG_ID'], self::SCHEDULE_ACTION_HIDE_DIALOG);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected static function finishDialogSession($params)
	{
		if (isset($params['DIALOG_ID']) && preg_match('/^[0-9]+$/i', $params['DIALOG_ID']))
		{
			$userId = (int)$params['DIALOG_ID'];
			self::scheduleAction($userId, self::SCHEDULE_ACTION_HIDE_DIALOG, '', self::HIDE_DIALOG_TIME);
		}

		return parent::finishDialogSession($params);
	}

	/**
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) MESSAGE_ID
	 * 	(int) DIALOG_ID
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
		$answerText = self::getMessage('QUEUE_NUMBER');
		if (!$answerText)
		{
			$answerText = Loc::getMessage('SUPPORT24_QUEUE_NUMBER');
		}
		$answerText = str_replace('#QUEUE_NUMBER#', $queueNumber, $answerText);


		// button
		$buttonText = self::getMessage('QUEUE_NUMBER_REFRESH');
		if (!$buttonText)
		{
			$buttonText = Loc::getMessage('SUPPORT24_QUEUE_NUMBER_REFRESH');
		}
		$keyboard = new Im\Bot\Keyboard(self::getBotId());
		$button = [
			'COMMAND' => self::COMMAND_QUEUE_NUMBER,
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
			'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
			'PARAMS' => [
				self::MESSAGE_PARAM_QUEUE_NUMBER => $queueNumber,
				self::MESSAGE_PARAM_ALLOW_QUOTE => 'N',
			],
			'KEYBOARD' => $keyboard,
		];
		if (!empty($params['MESSAGE_ID']))
		{
			$message['EDIT_FLAG'] = 'N';
			if (self::updateMessage((int)$params['MESSAGE_ID'], $message) === false)
			{
				self::sendMessage($message);
			}
		}
		else
		{
			self::sendMessage($message);
		}

		return true;
	}

	/**
	 * Checks if starting message at this dialog has been sent.
	 *
	 * @param array $params
	 * <pre>
	 * [
	 * 	(int) BOT_ID Bot id.
	 * 	(int) DIALOG_ID Dialg id.
	 * 	(int) USER_ID User id.
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function allowSendStartMessage(array $params)
	{
		$res = ImBot\Model\NetworkSessionTable::getList([
			'select' => [
				'GREETING_SHOWN',
			],
			'filter' => [
				'=BOT_ID' => $params['BOT_ID'],
				'=DIALOG_ID' => $params['USER_ID'],
			]
		]);
		if ($sessData = $res->fetch())
		{
			if ($sessData['GREETING_SHOWN'] == 'Y')
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 */
	protected static function clientSessionVote($params)
	{
		if (!empty($params['BOT_ID']) && !empty($params['USER_ID']))
		{
			self::deleteDialogSessions([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['USER_ID'],
			]);
		}

		return parent::clientSessionVote($params);
	}

	/**
	 * @return bool
	 */
	public static function onAfterLicenseChange()
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		$previousDemoState = Option::get('imbot', self::OPTION_BOT_DEMO_ACTIVE, false);

		$previousSupportLevel = Option::get('imbot', self::OPTION_BOT_SUPPORT_LEVEL, self::SUPPORT_LEVEL_FREE);
		$currentSupportLevel = self::getSupportLevel();

		$isPreviousSupportLevelPartner = $previousSupportLevel === self::SUPPORT_LEVEL_PARTNER;

		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		$previousZone = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentZone = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_CURRENT);

		$currentDemoState = $currentLicence == 'demo';
		Option::set('imbot', self::OPTION_BOT_DEMO_ACTIVE, $currentDemoState);

		$isSupportLevelChange = $previousSupportLevel != $currentSupportLevel;
		$isDemoLevelChange = $previousDemoState != $currentDemoState;
		$isZoneChanges = $previousZone != $currentZone;

		if (!$isSupportLevelChange && !$isDemoLevelChange && !$isZoneChanges)
		{
			return true;
		}

		if ($isSupportLevelChange)
		{
			Option::set('imbot', self::OPTION_BOT_SUPPORT_LEVEL, $currentSupportLevel);
		}

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			$previousCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, "");
			$currentCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, "");
		}
		else
		{
			$previousCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, "");
			$currentCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, "");
		}

		if ($isPreviousSupportLevelPartner)
		{
			$previousCode = Option::get("bitrix24", "partner_ol", "");
		}

		if ($isSupportLevelChange)
		{
			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);
		}

		if ($currentDemoState)
		{
			Option::set('imbot', self::OPTION_BOT_FREE_START_DATE, time());
		}

		self::updateBotProperties();

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_LEVEL_CHANGE' => $isSupportLevelChange,
			'IS_DEMO_LEVEL_CHANGE' => $isDemoLevelChange,
			'IS_SUPPORT_CODE_CHANGE' => $isZoneChanges,
		]);

		Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		$http = self::instanceHttpClient();
		$http->query(
			'clientChangeLicence',
			[
				'BOT_ID' => self::getBotId(),
				'PREVIOUS_LICENCE_TYPE' => $previousLicence,
				'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName($previousLicence),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'PREVIOUS_BOT_CODE' => $previousCode,
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => self::getMessage('SUPPORT_INFO_CHANGE_CODE', $previousSupportLevel),
			],
			false
		);

		return true;
	}

	/**
	 * @param string $previousFreeCode
	 * @param string $previousPaidCode
	 *
	 * @return bool
	 */
	public static function onAfterSupportCodeChange($previousFreeCode = '', $previousPaidCode = '')
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!$previousPaidCode)
			{
				return false;
			}

			$previousSupportLevel = self::SUPPORT_LEVEL_FREE;
			$previousCode = $previousPaidCode;
			$currentCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, "");
		}
		else
		{
			if (!$previousFreeCode)
			{
				return false;
			}

			$previousSupportLevel = self::SUPPORT_LEVEL_PAID;
			$previousCode = $previousFreeCode;
			$currentCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, "");
		}

		self::updateBotProperties();

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_CODE_CHANGE' => true,
		]);

		Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

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
				'MESSAGE' => self::getMessage('SUPPORT_INFO_CHANGE_CODE', $previousSupportLevel),
			],
			false
		);

		return true;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields)
	{
		$command = static::getCommandByMessage($messageFields);
		if (!$command)
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_SUPPORT24)
		{
			$messageParams = [];

			if ($messageFields['COMMAND_PARAMS'] === self::COMMAND_ACTIVATE_PARTNER)
			{
				$keyboard = new Im\Bot\Keyboard(self::getBotId());
				$keyboard->addButton([
					"DISPLAY" => "LINE",
					"TEXT" => self::getMessage('PARTNER_BUTTON_MANAGE'),
					"LINK" => self::getMessage('PARTNER_BUTTON_MANAGE_URL'),
					"CONTEXT" => "DESKTOP",
				]);
				$messageParams[self::MESSAGE_PARAM_KEYBOARD] = $keyboard;

				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
				$attach->AddMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
				$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;

				$result = Partner24::acceptRequest($messageFields['FROM_USER_ID']);
				if (!$result)
				{
					return false;
				}
			}
			else
			{
				if ($messageFields['COMMAND_PARAMS'] === self::COMMAND_DEACTIVATE_PARTNER)
				{
					Partner24::deactivate($messageFields['FROM_USER_ID']);

					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
					$attach->AddMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
					$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;
				}
				elseif ($messageFields['COMMAND_PARAMS'] === self::COMMAND_DECLINE_PARTNER_REQUEST)
				{
					Partner24::declineRequest($messageFields['FROM_USER_ID']);

					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::PROBLEM);
					$attach->AddMessage(self::getMessage('PARTNER_REQUEST_REJECTED'));
					$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;
				}
				$messageParams[self::MESSAGE_PARAM_KEYBOARD] = 'N';
			}

			\CIMMessageParam::Set($messageId, $messageParams);
			\CIMMessageParam::SendPull($messageId, [self::MESSAGE_PARAM_ATTACH, self::MESSAGE_PARAM_KEYBOARD]);

			return true;
		}
		elseif ($messageFields['COMMAND'] === self::COMMAND_START_DIALOG)
		{
			$message = (new \CIMChat(0))->getMessage($messageId);

			// duplicate message
			self::operatorMessageAdd(0, [
				'BOT_ID' => self::getBotId(),
				'BOT_CODE' => self::getBotCode(),
				'DIALOG_ID' => self::getCurrentUser()->getId(),
				'MESSAGE' => $message['MESSAGE'],
				'PARAMS' => [
					self::MESSAGE_PARAM_ALLOW_QUOTE => 'Y',
					self::MESSAGE_PARAM_MENU_ACTION => 'SKIP:MENU',
				],
			]);

			$userGender = Im\User::getInstance(self::getCurrentUser()->getId())->getGender();
			$forward = self::getMessage('START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			if (!$forward)
			{
				$forward = Loc::getMessage('SUPPORT24_START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			}

			\CIMMessenger::Add([
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
		elseif ($messageFields['COMMAND'] === self::COMMAND_QUEUE_NUMBER)
		{
			$sessionId = self::getDialogSessionId([
				'BOT_ID' => static::getBotId(),
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
			]);

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
						&& isset($message['params'], $message['params'][self::MESSAGE_PARAM_SESSION_ID])
						&& (int)$message['params'][self::MESSAGE_PARAM_SESSION_ID] > 0 //SESSION_ID
					)
					{
						$sessionId = (int)$message['params'][self::MESSAGE_PARAM_SESSION_ID];
					}
					if (isset($message['params'], $message['params'][self::MESSAGE_PARAM_IMOL_VOTE]))
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

		return parent::onCommandAdd($messageId, $messageFields);
	}

	/**
	 * Forwards message into recent dialogs about support lever change.
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(array) BUSINESS_USERS
	 * 	(bool) IS_SUPPORT_LEVEL_CHANGE
	 * 	(bool) IS_SUPPORT_CODE_CHANGE
	 * 	(bool) IS_DEMO_LEVEL_CHANGE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function sendNotifyAboutChangeLevel($params)
	{
		if (self::isActivePartnerSupport())
		{
			return false;
		}

		$businessUsers = $params['BUSINESS_USERS'];
		$isSupportLevelChange = (bool)$params['IS_SUPPORT_LEVEL_CHANGE'];
		$isSupportCodeChange = (bool)$params['IS_SUPPORT_CODE_CHANGE'];
		$isDemoLevelChange = (bool)$params['IS_DEMO_LEVEL_CHANGE'];
		$isActiveFreeSupport = self::isActiveFreeSupport();

		foreach (self::getRecentDialogs() as $dialog)
		{
			if ($dialog['USER_ID'] == self::getBotId())
			{
				continue;
			}

			$message = '';

			if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
			{
				if ($isSupportLevelChange)
				{
					if (self::isActivePaidSupportForUser($dialog['USER_ID']))
					{
						$message = self::getMessage('CHANGE_ADMIN');
					}
					else
					{
						$message = self::getMessage('CHANGE_USER');
					}
				}
				elseif ($isDemoLevelChange)
				{
					if (self::isActivePaidSupportForUser($dialog['USER_ID']))
					{
						$message = self::getMessage('CHANGE_DEMO');
					}
				}
				elseif ($isSupportCodeChange)
				{
					if (self::isActivePaidSupportForUser($dialog['USER_ID']))
					{
						$message = self::getMessage('CHANGE_CODE');
					}
				}
			}
			else
			{
				if ($isSupportLevelChange)
				{
					if ($isActiveFreeSupport)
					{
						if (is_array($businessUsers) && in_array($dialog['USER_ID'], $businessUsers))
						{
							$message = self::getMessage('CHANGE_BUSINESS');
						}
						else
						{
							$message = self::getMessage('CHANGE_ADMIN');
						}
					}
					else
					{
						$message = self::getMessage('CHANGE_END');
					}
				}
				elseif ($isDemoLevelChange)
				{
					if ($isActiveFreeSupport)
					{
						$message = self::getMessage('CHANGE_DEMO');
					}
				}
				elseif ($isSupportCodeChange)
				{
					if ($isActiveFreeSupport)
					{
						$message = self::getMessage('CHANGE_CODE');
					}
				}
			}

			if (!$message)
			{
				continue;
			}

			if ($dialog['RECENTLY_TALK'] == 'Y')
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
		if (!Main\Loader::includeModule('im'))
			return false;

		if (!Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			$currentCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, "");
		}
		else
		{
			$currentCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, "");
		}

		$message = $params['MESSAGE'] ?? '';

		$http = self::instanceHttpClient();
		$http->query(
			'clientRequestFinalizeSession',
			[
				'BOT_ID' => self::getBotId(),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => $message,
			],
			false
		);

		return true;
	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function onAfterUserAuthorize($params)
	{
		$auth = \CHTTP::ParseAuthRequest();
		if (
			isset($auth["basic"])
			&& $auth["basic"]["username"] <> ''
			&& $auth["basic"]["password"] <> ''
			&& mb_strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'bitrix') === false
		)
		{
			return true;
		}

		if (isset($params['update']) && $params['update'] === false)
		{
			return true;
		}

		$userId = (int)$params['user_fields']['ID'];
		if ($userId <= 0)
		{
			return true;
		}

		self::checkWelcomeShown($userId);

		self::checkPortalStageMode($userId, true);

		return true;
	}

	//endregion

	//region Bot methods

	/**
	 * @return bool
	 */
	public static function updateBotProperties()
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		$botCache = Im\Bot::getCache(self::getBotId());
		if ($botCache['APP_ID'] !== self::getBotCode())
		{
			Option::set(self::MODULE_ID, parent::BOT_CODE.'_'.$botCache['APP_ID']."_bot_id", 0);
			Option::set(self::MODULE_ID, parent::BOT_CODE.'_'.self::getBotCode()."_bot_id", self::getBotId());
		}

		$botParams = [
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see Support24::onMessageAdd */
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see Support24::onChatStart */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see Support24::onBotDelete */
			'TEXT_CHAT_WELCOME_MESSAGE' => '',
			'TEXT_PRIVATE_WELCOME_MESSAGE' => '',
			'VERIFIED' => 'Y',
			'CODE' => 'network_'.self::getBotCode(),
			'APP_ID' => self::getBotCode(),
			'PROPERTIES' => [
				'LOGIN' => 'bot_imbot_support24',
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

		return true;
	}

	//endregion

	//region Check actions

	/**
	 * Checks if user has been shown with the welcome message.
	 * @param int $userId Current user Id.
	 * @return bool
	 */
	protected static function checkWelcomeShown($userId)
	{
		$session = Main\Application::getInstance()->getSession();
		if (!$session->has(self::OPTION_BOT_WELCOME_SHOWN))
		{
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
			{
				$isShown = (int)\CUserOptions::GetOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, 0, $userId);
				if ($isShown == 0)
				{
					if (self::isActiveFreeSupport() && self::isActiveFreeSupportForUser($userId))
					{
						self::scheduleAction($userId, self::SCHEDULE_ACTION_WELCOME, '', 10);
					}

					\CUserOptions::SetOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, time(), false, $userId);
				}
			}

			$session->set(self::OPTION_BOT_WELCOME_SHOWN, 1);
		}

		return true;
	}

	//endregion

	//region Portal stage-mode

	/**
	 * Checks if portal is in STAGE mode.
	 * @return bool
	 */
	public static function isStagePortal()
	{
		static $mode;
		if ($mode === null)
		{
			$mode = (bool)in_array(self::getPortalStage(), ['ETALON', 'STAGE']);
		}

		return $mode;
	}

	/**
	 * Sends notification if portal is in test-stage mode.
	 *
	 * @param int $userId
	 * @param bool $delayAction
	 *
	 * @return bool
	 */
	protected static function checkPortalStageMode(int $userId, bool $delayAction = false)
	{
		$session = Main\Application::getInstance()->getSession();
		$time = time();
		if (
			!$session->has(self::OPTION_BOT_STAGE_ACTIVE)
			|| ($time - (int)$session->get(self::OPTION_BOT_STAGE_ACTIVE)) > 86400
		)
		{
			$session->set(self::OPTION_BOT_STAGE_ACTIVE, $time);

			$isStageActive = (int)Option::get(self::MODULE_ID, self::OPTION_BOT_STAGE_ACTIVE, 0);
			if (self::isStagePortal())
			{
				if ($isStageActive == 0)
				{
					Option::set(self::MODULE_ID, self::OPTION_BOT_STAGE_ACTIVE, $time);
					self::deleteScheduledAction(self::USER_LEVEL_ADMIN, self::SCHEDULE_ACTION_CHECK_STAGE);
					if ($delayAction)
					{
						self::scheduleAction(self::USER_LEVEL_ADMIN, self::SCHEDULE_ACTION_CHECK_STAGE, 'START');
					}
					else
					{
						self::sendNotifyPortalStageMode([
							'IS_STAGE_STARTED' => true
						]);

						return false;
					}
				}
			}
			elseif ($isStageActive > 0)
			{
				Option::delete(self::MODULE_ID, ['name' => self::OPTION_BOT_STAGE_ACTIVE]);
				self::deleteScheduledAction(self::USER_LEVEL_ADMIN, self::SCHEDULE_ACTION_CHECK_STAGE);
				if ($delayAction)
				{
					self::scheduleAction(self::USER_LEVEL_ADMIN, self::SCHEDULE_ACTION_CHECK_STAGE, 'STOP');
				}
				else
				{
					self::sendNotifyPortalStageMode([
						'IS_STAGE_STOPPED' => true
					]);
				}
			}
		}

		return true;
	}

	/**
	 * Sends message about stage-portal support level.
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(bool) IS_STAGE_STARTED
	 * 	(bool) IS_STAGE_STOPPED
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function sendNotifyPortalStageMode($params)
	{
		$notifyUsers = self::getAdministrators();
		$recentUsers = [];
		// recent talking in depth 1 hour
		foreach (self::getRecentDialogs(1) as $dialog)
		{
			if ($dialog['RECENTLY_TALK'] === 'Y')
			{
				$recentUsers[] = (int)$dialog['USER_ID'];
			}
		}
		// remove recent talking
		$notifyUsers = array_diff($notifyUsers, $recentUsers);
		if (!$notifyUsers)
		{
			return false;
		}

		if ($params['IS_STAGE_STARTED'] === true)
		{
			$message = self::getMessage('STAGE_START');
			if ($message)
			{
				foreach ($notifyUsers as $userId)
				{
					self::sendMessage([
						'DIALOG_ID' => $userId,
						'MESSAGE' => $message,
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N'
					]);

					$menuState = self::showMenu([
						'BOT_ID' => self::getBotId(),
						'DIALOG_ID' => $userId,
					]);
					self::saveMenuState($userId, $menuState);
				}
			}
		}
		elseif ($params['IS_STAGE_STOPPED'] === true)
		{
			$message = self::getMessage('STAGE_STOP');
			if ($message)
			{
				foreach ($notifyUsers as $userId)
				{
					self::sendMessage([
						'DIALOG_ID' => $userId,
						'MESSAGE' => $message,
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N'
					]);

					$menuState = self::getMenuState($userId) or [];
					if (isset($menuState['message_id']))
					{
						self::disableMessageButtons((int)$menuState['message_id']);
					}

					self::saveMenuState($userId, null);
				}
			}
		}

		return true;
	}

	//endregion

	//region Schedule actions

	/**
	 * @param int|string $userId
	 * @param string $action
	 * @param string $code
	 * @param int $delayMinutes
	 *
	 * @return bool
	 */
	public static function scheduleAction($userId, $action, $code = '', $delayMinutes = 1)
	{
		if (!($userId === self::USER_LEVEL_ADMIN || $userId === self::USER_LEVEL_BUSINESS))
		{
			$userId = (int)$userId;
			if ($userId <= 0)
			{
				return false;
			}
		}

		$agentName = __CLASS__."::scheduledActionAgent('{$userId}', '{$action}', '{$code}');";
		$result = \CAgent::GetList([], ['MODULE_ID'=>'imbot', '=NAME'=> $agentName]);
		while ($agent = $result->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		$delaySeconds = (int)$delayMinutes * 60;

		\CAgent::AddAgent(
			$agentName,
			'imbot',
			'N',
			$delaySeconds,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $delaySeconds, 'FULL')
		);

		return true;
	}

	/**
	 * @param int|string|null $userId
	 * @param string $action
	 * @param string $code
	 *
	 * @return bool
	 */
	public static function deleteScheduledAction($userId = null, $action = '', $code = '')
	{
		if (!($userId === self::USER_LEVEL_ADMIN || $userId === self::USER_LEVEL_BUSINESS))
		{
			$userId = intval($userId);
		}
		$action = trim($action);
		$code = trim($code);

		$filter = ['MODULE_ID' => 'imbot'];

		if (!$userId)
		{
			$filter['NAME'] = __CLASS__."::scheduledActionAgent(%";
		}
		else
		{
			if ($action && $code)
			{
				$filter['=NAME'] = __CLASS__."::scheduledActionAgent('{$userId}', '{$action}', '{$code}');";
			}
			else if ($action)
			{
				$filter['NAME'] = __CLASS__."::scheduledActionAgent('{$userId}', '{$action}', %";
			}
			else
			{
				$filter['NAME'] = __CLASS__."::scheduledActionAgent('{$userId}', %";
			}
		}

		$result = \CAgent::GetList([], $filter);
		while($agent = $result->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		return true;
	}

	/**
	 * @param int|string $userId
	 * @param string $action
	 * @param string $code
	 *
	 * @return string
	 */
	public static function scheduledActionAgent($userId, $action, $code = '')
	{
		self::execScheduleAction($userId, $action, $code);

		return "";
	}

	/**
	 * @param int|string $userId
	 * @param string $action
	 * @param string $code
	 *
	 * @return bool
	 */
	public static function execScheduleAction($userId, $action, $code = '')
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!($userId === self::USER_LEVEL_ADMIN || $userId === self::USER_LEVEL_BUSINESS))
		{
			$userId = intval($userId);
			if ($userId <= 0)
			{
				return false;
			}
		}

		if ($action == self::SCHEDULE_ACTION_WELCOME)
		{
			if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			{
				return true;
			}
			else if (!self::isActiveFreeSupport() || !self::isActiveFreeSupportForUser($userId))
			{
				return true;
			}

			\CIMMessage::GetChatId($userId, self::getBotId());
		}
		elseif ($action == self::SCHEDULE_ACTION_INVOLVEMENT)
		{
			if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			{
				return true;
			}
			else if (!self::isActiveFreeSupport() || !self::isActiveFreeSupportForUser($userId))
			{
				return true;
			}

			$generationDate = (int)Option::get('imbot', self::OPTION_BOT_FREE_START_DATE, 0);
			$currentDay = (int)floor((time() - $generationDate) / 86400) + 1;

			self::scheduleAction($userId, self::SCHEDULE_ACTION_INVOLVEMENT, '', 24*60);

			$message = self::getMessage($currentDay);
			if ($message == '')
			{
				return false;
			}

			$lastMessageMinTime = self::INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME * 60 * 60; // hour to second

			$query = "
				SELECT
					RU.USER_ID,
					RU.CHAT_ID,
					IF(UNIX_TIMESTAMP(MB.DATE_CREATE) > UNIX_TIMESTAMP()-".$lastMessageMinTime.", 'Y', 'N') BOT_RECENTLY_TALK,
					IF(UNIX_TIMESTAMP(MU.DATE_CREATE) > UNIX_TIMESTAMP()-".$lastMessageMinTime.", 'Y', 'N') USER_RECENTLY_TALK
				FROM
					b_im_relation RB LEFT JOIN b_im_message MB ON RB.LAST_ID = MB.ID,
					b_im_relation RU LEFT JOIN b_im_message MU ON RU.LAST_ID = MU.ID
				WHERE
					RB.USER_ID = ".self::getBotId()."
				and RU.USER_ID = ".$userId."
				and RB.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
				and RU.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
				and RB.CHAT_ID = RU.CHAT_ID
			";
			$dialog = Main\Application::getInstance()->getConnection()->query($query)->fetch();

			if (
				$dialog['BOT_RECENTLY_TALK'] == 'Y'
				|| $dialog['USER_RECENTLY_TALK'] == 'Y'
			)
			{
				return false;
			}

			self::sendMessage([
				'DIALOG_ID' => $userId,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

			return true;
		}
		elseif ($action == self::SCHEDULE_ACTION_MESSAGE)
		{
			$code = trim($code);
			if ($code == '')
			{
				return false;
			}

			$message = self::getMessage($code);
			if ($message == '')
			{
				return false;
			}

			self::sendMessage([
				'DIALOG_ID' => $userId,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);
		}
		elseif ($action == self::SCHEDULE_ACTION_PARTNER_JOIN)
		{
			$keyboard = new Im\Bot\Keyboard(self::getBotId());
			$keyboard->addButton([
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_YES'),
				"BG_COLOR" => "#29619b",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => self::COMMAND_SUPPORT24,
				"COMMAND_PARAMS" => self::COMMAND_ACTIVATE_PARTNER,
			]);
			$keyboard->addButton([
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_NO'),
				"BG_COLOR" => "#990000",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => self::COMMAND_SUPPORT24,
				"COMMAND_PARAMS" => self::COMMAND_DECLINE_PARTNER_REQUEST,
			]);

			self::sendMessage([
				'DIALOG_ID' => $userId,
				'MESSAGE' => self::getMessage('PARTNER_REQUEST'),
				'KEYBOARD' => $keyboard,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

			return true;
		}
		elseif ($action == self::SCHEDULE_ACTION_HIDE_DIALOG)
		{
			$botId = self::getBotId();
			\CIMContactList::DialogHide($botId, $userId);
		}
		elseif ($action == self::SCHEDULE_ACTION_CHECK_STAGE)
		{
			if ($code === 'START')
			{
				self::sendNotifyPortalStageMode([
					'IS_STAGE_STARTED' => true
				]);
			}
			elseif ($code === 'STOP')
			{
				self::sendNotifyPortalStageMode([
					'IS_STAGE_STOPPED' => true
				]);
			}
		}

		return true;
	}

	//endregion

	//region Phrases & Messages

	/**
	 * @param string $code
	 * @param string|null $supportLevel
	 *
	 * @return string
	 */
	public static function getMessage($code, $supportLevel = null)
	{
		if (!$supportLevel)
		{
			$supportLevel = self::getSupportLevel();
		}
		$supportLevel = mb_strtolower($supportLevel);

		if (mb_substr($code, 0, 4) == 'DAY_')
		{
			$code = mb_substr($code, 4);
		}

		$optionCode = $supportLevel == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_MESSAGES : self::OPTION_BOT_PAID_MESSAGES;

		static $messages = [];
		if (!isset($messages[$optionCode]))
		{
			$messages[$optionCode] = unserialize(
				Option::get('imbot', $optionCode, "a:0:{}"),
				['allowed_classes' => false]
			);
		}

		return isset($messages[$optionCode][$code]) ? $messages[$optionCode][$code] : '';
	}


	/**
	 * @param string $message
	 * @param int $userId
	 *
	 * @return string
	 */
	public static function replacePlaceholders($message, $userId = 0)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return $message;
		}

		$message = parent::replacePlaceholders($message, $userId);

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return $message;
		}

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);
		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);

		$currentLicenceName = \CBitrix24::getLicenseName($currentLicence);
		$currentLicenceName = $currentLicenceName? $currentLicenceName: $currentLicence;

		$previousLicenceName = \CBitrix24::getLicenseName($previousLicence);
		$previousLicenceName = $previousLicenceName? $previousLicenceName: $previousLicence;

		$message = str_replace(
			[
				'#SUPPORT_ID#',
				'#SUPPORT_NAME#',
				'#TARIFF_NAME#',
				'#TARIFF_CODE#',
				'#PREVIOUS_TARIFF_NAME#',
				'#PREVIOUS_TARIFF_CODE#',
			],
			[
				self::getBotId(),
				self::getBotName(),
				$currentLicenceName,
				$currentLicence,
				$previousLicenceName,
				$previousLicence,
			],
			$message
		);

		if (self::isEnabled())
		{
			$message = str_replace(
				[
					'#PARTNER_NAME#',
					'#PARTNER_BOT_ID#',
					'#PARTNER_BOT_NAME#',
				],
				[
					Partner24::getPartnerName(),
					Partner24::getBotId(),
					Partner24::getBotName(),
				],
				$message
			);
		}

		return $message;
	}
	//endregion
}