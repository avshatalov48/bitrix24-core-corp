<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Im;
use Bitrix\ImBot;

Loc::loadMessages(__FILE__);

class Support24 extends Network implements NetworkBot, MenuBot
{
	const BOT_CODE = "support24";

	public const COMMAND_SUPPORT24 = 'support24';
	public const COMMAND_SUPPORT24_ACTIVATE_PARTNER = 'activatePartnerSupport';
	public const COMMAND_SUPPORT24_DEACTIVATE_PARTNER = 'deactivatePartnerSupport';
	public const COMMAND_SUPPORT24_DECLINE_PARTNER_REQUEST = 'declinePartnerRequest';

	const SUPPORT_LEVEL_NONE = 'none';
	const SUPPORT_LEVEL_FREE = 'free';
	const SUPPORT_LEVEL_PAID = 'paid';
	const SUPPORT_LEVEL_PARTNER = 'partner';

	const SUPPORT_TIME_UNLIMITED = -1;
	const SUPPORT_TIME_NONE = 0;

	const SCHEDULE_ACTION_WELCOME = 'welcome';
	const SCHEDULE_ACTION_INVOLVEMENT = 'involvement';
	const SCHEDULE_ACTION_MESSAGE = 'message';
	const SCHEDULE_ACTION_PARTNER_JOIN = 'partner_join';
	const SCHEDULE_ACTION_HIDE_DIALOG = 'hide_dialog';

	const SCHEDULE_DELETE_ALL = null;

	const INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME = 8; // hour
	const HIDE_DIALOG_TIME = 5; // minuts

	const LIST_BOX_SUPPORT_CODES = Array(
		'ru' => '4df232699a9e1d0487c3972f26ea8d25',
		'default' => '1a146ac74c3a729681c45b8f692eab73',
	);

	// send start message if there are no conversation in the chat within this period days
	const START_MESSAGE_DAYS_DEPTH = 30;

	public const OPTION_BOT_ID = 'support24_bot_id';
	public const OPTION_BOT_WELCOME_SHOWN = 'support24_welcome_message';
	public const OPTION_BOT_SUPPORT_LEVEL = 'support24_support_level';
	public const OPTION_BOT_PAID_CODE = 'support24_paid_code';
	public const OPTION_BOT_FREE_CODE = 'support24_free_code';
	public const OPTION_BOT_PAID_ACTIVE = 'support24_paid_active';
	public const OPTION_BOT_DEMO_ACTIVE = 'support24_demo_active';
	public const OPTION_BOT_FREE_DAYS = 'support24_free_days';
	public const OPTION_BOT_FREE_START_DATE = 'support24_free_start_date';
	public const OPTION_BOT_FREE_FOR_ALL = 'support24_free_for_all';
	public const OPTION_BOT_PAID_FOR_ALL = 'support24_paid_for_all';
	public const OPTION_BOT_FREE_NAME = 'support24_free_name';
	public const OPTION_BOT_FREE_DESC = 'support24_free_desc';
	public const OPTION_BOT_FREE_AVATAR = 'support24_free_avatar';
	public const OPTION_BOT_PAID_NAME = 'support24_paid_name';
	public const OPTION_BOT_PAID_DESC = 'support24_paid_desc';
	public const OPTION_BOT_PAID_AVATAR = 'support24_paid_avatar';
	public const OPTION_BOT_FREE_MENU = 'support24_free_menu';
	public const OPTION_BOT_PAID_MENU = 'support24_paid_menu';
	public const OPTION_BOT_FREE_MESSAGES = "support24_free_messages";
	public const OPTION_BOT_PAID_MESSAGES = "support24_paid_messages";

	private static $isAdmin = Array();
	private static $isIntegrator = Array();

	//region Register

	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 *
	 * @return bool|int
	 */
	public static function register(array $params = Array())
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
			'onAfterLicenseChange'/** @see ImBot\Bot\Support24::onAfterLicenseChange */
		);
		$eventManager->registerEventHandlerCompatible(
			'main',
			'OnAfterUserAuthorize',
			self::MODULE_ID,
			__CLASS__,
			'onAfterUserAuthorize'/** @see ImBot\Bot\Support24::onAfterUserAuthorize */
		);

		self::scheduleAction(1, self::SCHEDULE_ACTION_WELCOME, '', 10);

		Im\Command::register([
			'MODULE_ID' => self::MODULE_ID,
			'BOT_ID' => $botId,
			'COMMAND' => self::COMMAND_SUPPORT24,
			'HIDDEN' => 'Y',
			'CLASS' => __CLASS__,
			'METHOD_COMMAND_ADD' => 'onCommandAdd'/** @see ImBot\Bot\Support24::onCommandAdd */
		]);

		Im\Command::register([
			'MODULE_ID' => self::MODULE_ID,
			'BOT_ID' => $botId,
			'COMMAND' => self::COMMAND_MENU,
			'HIDDEN' => 'Y',
			'CLASS' => __CLASS__,
			'METHOD_COMMAND_ADD' => 'onCommandAdd'/** @see ImBot\Bot\Support24::onCommandAdd */
		]);

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
				'onAfterLicenseChange'/** @see ImBot\Bot\Support24::onAfterLicenseChange */
			);
			$eventManager->unregisterEventHandler(
				'main',
				'OnAfterUserAuthorize',
				self::MODULE_ID,
				__CLASS__,
				'onAfterUserAuthorize'/** @see ImBot\Bot\Support24::onAfterUserAuthorize */
			);
		}

		return $result;
	}

	//endregion

	//region Param getters

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
			if (Main\Localization\CultureTable::getList(array('filter' => array('=CODE' => 'ru')))->fetch())
			{
				$lang = 'ru';
			}
		}

		return $lang;
	}


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
	 * @return bool
	 */
	public static function hasBotMenu()
	{
		return !empty(self::getBotMenu());
	}

	/**
	 * Returns stored data for ITR menu.
	 * @return array
	 */
	public static function getBotMenu()
	{
		static $structure;
		if ($structure === null)
		{
			$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
				self::OPTION_BOT_FREE_MENU : self::OPTION_BOT_PAID_MENU;

			$json = Option::get('imbot', $optionName, '');

			$structure = [];
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

		// temporary remove because free plan is unlimited
		//if (\CBitrix24BusinessTools::isLicenseUnlimited())
		//	return true;

		if (self::isUserAdmin($userId) || self::isUserIntegrator($userId))
		{
			return true;
		}

		// temporary remove because free plan is unlimited
		//$users = \CBitrix24BusinessTools::getUnlimUsers();
		//if (in_array($userId, $users))
		//	return true;

		return false;
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserAdmin($userId)
	{
		if (isset(self::$isAdmin[$userId]))
		{
			return self::$isAdmin[$userId];
		}

		global $USER;
		if (Main\Loader::includeModule('bitrix24'))
		{
			if (is_object($USER) && $USER->GetId() > 0 && $USER->GetId() == $userId && $USER->IsAdmin())
			{
				$result = true;
			}
			else
			{
				$result = \CBitrix24::IsPortalAdmin($userId);
			}
		}
		else
		{
			if (is_object($USER) && $USER->GetId() > 0 && $USER->GetId() == $userId)
			{
				$result = $USER->IsAdmin();
			}
			else
			{
				$result = false;

				$groups = Main\UserTable::getUserGroupIds($userId);
				foreach ($groups as $groupId)
				{
					if ($groupId == 1)
					{
						$result = true;
						break;
					}
				}
			}
		}

		self::$isAdmin[$userId] = $result;

		return $result;
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserIntegrator($userId)
	{
		if (!$userId)
		{
			return false;
		}

		if (isset(self::$isIntegrator[$userId]))
		{
			return self::$isIntegrator[$userId];
		}

		if (Main\Loader::includeModule('bitrix24'))
		{
			$result = \CBitrix24::isIntegrator($userId);
		}
		else
		{
			$result = false;
		}

		self::$isIntegrator[$userId] = $result;

		return $result;
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
	 * @param string $command
	 * @param array $params
	 *
	 * @return ImBot\Error|array
	 */
	public static function onAnswerAdd($command, $params)
	{
		return self::onReceiveCommand($command, $params);
	}

	/**
	 * @param string $command
	 * @param array $params
	 *
	 * @return ImBot\Error|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		// integrity control is disabled due to broken support bots
		//
		// if (isset($params['LINE']['CODE']) && $params['LINE']['CODE'] !== self::getBotCode())
		// {
		//     return new ImBot\Error(__METHOD__, 'SUPPORT_CODE_MISMATCH', 'Support code is not correct for this portal');
		// }

		return parent::onReceiveCommand($command, $params);
	}

	/**
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onWelcomeMessage($dialogId, $joinFields)
	{
		if (!Main\Loader::includeModule('im'))
			return false;

		$message = '';

		$messageFields = $joinFields;
		$messageFields['DIALOG_ID'] = $dialogId;

		if ($messageFields['CHAT_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage(Array(
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				));
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

		self::sendMessage(Array(
			'DIALOG_ID' => $messageFields['USER_ID'],
			'MESSAGE' => $message,
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		));

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
				self::sendMessage(Array(
					'DIALOG_ID' => 'chat'.$messageFields['CHAT_ID'],
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				));
			}

			$chat = new \CIMChat(self::getBotId());
			$chat->DeleteUser($messageFields['CHAT_ID'], self::getBotId());

			return true;
		}

		$allowShowMenu = self::hasBotMenu();
		$warningRestrictionMessage = '';
		if (
			self::isActivePartnerSupport() &&
			self::isUserIntegrator($messageFields['FROM_USER_ID'])
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
			!self::isUserIntegrator($messageFields['FROM_USER_ID'])
		)
		{
			$warningRestrictionMessage = self::getMessage('MESSAGE_PARTNER');
			$allowShowMenu = false;
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isActiveFreeSupport())
			{
				if (!self::isActiveFreeSupportForUser($messageFields['FROM_USER_ID']))
				{
					$warningRestrictionMessage = self::getMessage('MESSAGE_LIMITED');
				}
			}
			else if (!self::isUserIntegrator($messageFields['FROM_USER_ID']))
			{
				$warningRestrictionMessage = self::getMessage('MESSAGE_END');
			}
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!self::isActivePaidSupportForUser($messageFields['FROM_USER_ID']))
			{
				$warningRestrictionMessage = self::getMessage('MESSAGE_LIMITED');
			}
		}

		// ITR menu on before any dialog starts
		if ($allowShowMenu)
		{
			if (!self::isMenuTrackFinished((int)$messageFields['FROM_USER_ID']))
		{
			$prevMenuState = self::getMenuState((int)$messageFields['FROM_USER_ID']);
			$lastMenuItemId = is_array($prevMenuState['track']) ? end($prevMenuState['track']) : null;

			if (!$lastMenuItemId && !empty($warningRestrictionMessage))
			{
				// show restriction warning message first
					self::sendMessage(array(
					'DIALOG_ID' => $messageFields['FROM_USER_ID'],
					'MESSAGE' => $warningRestrictionMessage,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N',
				));
			}

			if ($lastMenuItemId !== self::COMMAND_MENU_EXIT)
			{
				$menuState = self::showMenu(
					[
						'BOT_ID' => self::getBotId(),
						'DIALOG_ID' => $messageFields['FROM_USER_ID'],
					],
					true
				);
					$menuState['messages'][] = $messageId;
				self::saveMenuState(
					(int)$messageFields['FROM_USER_ID'],
					$menuState
				);

					if (!self::isMenuTrackFinished((int)$messageFields['FROM_USER_ID'], $menuState))
				{
					return false;//continue menu travel
				}
			}
		}
		}
		elseif (!empty($warningRestrictionMessage))
		{
			// show restriction warning message
			self::sendMessage(Array(
				'DIALOG_ID' => $messageFields['FROM_USER_ID'],
				'MESSAGE' => $warningRestrictionMessage,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N',
			));

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
	public static function startDialogSession($params)
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
	public static function finishDialogSession($params)
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			// Only for ru, by, kz regions.
			$prefix = \CBitrix24::getLicensePrefix();
			if (in_array($prefix, ['ru', 'by', 'kz'], true))
			{
				if (isset($params['DIALOG_ID']) && preg_match('/^[0-9]+$/i', $params['DIALOG_ID']))
				{
					$userId = (int)$params['DIALOG_ID'];

					self::scheduleAction($userId, self::SCHEDULE_ACTION_HIDE_DIALOG, '', self::HIDE_DIALOG_TIME);
				}
			}
		}

		return parent::finishDialogSession($params);
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
			$res = ImBot\Model\NetworkSessionTable::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'=BOT_ID' => $params['BOT_ID'],
					'=DIALOG_ID' => $params['USER_ID'],
				]
			]);
			if ($sess = $res->fetch())
			{
				ImBot\Model\NetworkSessionTable::delete($sess['ID']);
			}
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
			Array(
				'BOT_ID' => self::getBotId(),
				'PREVIOUS_LICENCE_TYPE' => $previousLicence,
				'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName($previousLicence),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'PREVIOUS_BOT_CODE' => $previousCode,
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => self::getMessage('SUPPORT_INFO_CHANGE_CODE', $previousSupportLevel),
			),
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
			Array(
				'BOT_ID' => self::getBotId(),
				'PREVIOUS_LICENCE_TYPE' => $currentLicence,
				'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'PREVIOUS_BOT_CODE' => $previousCode,
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => self::getMessage('SUPPORT_INFO_CHANGE_CODE', $previousSupportLevel),
			),
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
		if ($messageFields['SYSTEM'] === 'Y')
		{
			return false;
		}

		if ($messageFields['COMMAND_CONTEXT'] !== 'KEYBOARD')
		{
			return false;
		}

		if ($messageFields['MESSAGE_TYPE'] !== IM_MESSAGE_PRIVATE)
		{
			return false;
		}

		if ($messageFields['TO_USER_ID'] != self::getBotId())
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_SUPPORT24)
		{
			$messageParams = [];

			if ($messageFields['COMMAND_PARAMS'] === self::COMMAND_SUPPORT24_ACTIVATE_PARTNER)
			{
				$keyboard = new Im\Bot\Keyboard(self::getBotId());
				$keyboard->addButton(array(
					"DISPLAY" => "LINE",
					"TEXT" => self::getMessage('PARTNER_BUTTON_MANAGE'),
					"LINK" => self::getMessage('PARTNER_BUTTON_MANAGE_URL'),
					"CONTEXT" => "DESKTOP",
				));
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
				if ($messageFields['COMMAND_PARAMS'] === self::COMMAND_SUPPORT24_DEACTIVATE_PARTNER)
				{
					Partner24::deactivate($messageFields['FROM_USER_ID']);

					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
					$attach->AddMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
					$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;
				}
				elseif ($messageFields['COMMAND_PARAMS'] === self::COMMAND_SUPPORT24_DECLINE_PARTNER_REQUEST)
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

		return parent::onCommandAdd($messageId, $messageFields);
	}

	/**
	 * @param array $params
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

		$query = "
			SELECT
				RU.USER_ID,
				RU.CHAT_ID,
				IF(UNIX_TIMESTAMP(M.DATE_CREATE) > UNIX_TIMESTAMP()-86400*7, 'Y', 'N') RECENTLY_TALK
			FROM
				b_im_relation RB,
				b_im_relation RU LEFT JOIN b_im_message M ON RU.LAST_ID = M.ID
			WHERE
				RB.USER_ID = ".self::getBotId()."
			and RU.USER_ID != ".self::getBotId()."
			and RB.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			and RU.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			and RB.CHAT_ID = RU.CHAT_ID
		";
		$dialogs = Main\Application::getInstance()->getConnection()->query($query)->fetchAll();

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			foreach ($dialogs as $dialog)
			{
				if ($dialog['USER_ID'] == self::getBotId())
				{
					continue;
				}

				$message = '';

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
				else if ($isDemoLevelChange)
				{
					if (self::isActivePaidSupportForUser($dialog['USER_ID']))
					{
						$message = self::getMessage('CHANGE_DEMO');
					}
				}
				else if ($isSupportCodeChange)
				{
					if (self::isActivePaidSupportForUser($dialog['USER_ID']))
					{
						$message = self::getMessage('CHANGE_CODE');
					}
				}

				if (!$message)
				{
					continue;
				}

				if ($dialog['RECENTLY_TALK'] == 'Y')
				{
					self::sendMessage(Array(
						'DIALOG_ID' => $dialog['USER_ID'],
						'MESSAGE' => $message,
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N'
					));
				}
				else
				{
					Im\Model\MessageTable::add(Array(
						'CHAT_ID' => $dialog['CHAT_ID'],
						'AUTHOR_ID' => self::getBotId(),
						'MESSAGE' => self::replacePlaceholders($message, $dialog['USER_ID'])
					));
				}
			}
		}
		else
		{
			$isActiveFreeSupport = self::isActiveFreeSupport();

			foreach ($dialogs as $dialog)
			{
				if ($dialog['USER_ID'] == self::getBotId())
				{
					continue;
				}

				$message = '';

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
				else if ($isDemoLevelChange)
				{
					if ($isActiveFreeSupport)
					{
						$message = self::getMessage('CHANGE_DEMO');
					}
				}
				else if ($isSupportCodeChange)
				{
					if ($isActiveFreeSupport)
					{
						$message = self::getMessage('CHANGE_CODE');
					}
				}

				if (!$message)
				{
					continue;
				}

				if ($dialog['RECENTLY_TALK'] == 'Y')
				{
					self::sendMessage(Array(
						'DIALOG_ID' => $dialog['USER_ID'],
						'MESSAGE' => $message,
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N'
					));
				}
				else
				{
					Im\Model\MessageTable::add(Array(
						'CHAT_ID' => $dialog['CHAT_ID'],
						'AUTHOR_ID' => self::getBotId(),
						'MESSAGE' => self::replacePlaceholders($message, $dialog['USER_ID'])
					));
				}
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
			Array(
				'BOT_ID' => self::getBotId(),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => $message,
			),
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
			isset($auth["basic"]) && $auth["basic"]["username"] <> '' && $auth["basic"]["password"] <> ''
			&& mb_strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'bitrix') === false
		)
		{
			return true;
		}

		if (isset($params['update']) && $params['update'] === false)
		{
			return true;
		}

		if ($params['user_fields']['ID'] <= 0)
		{
			return true;
		}

		$params['user_fields']['ID'] = intval($params['user_fields']['ID']);

		if (isset($_SESSION['SUPPORT24'][$params['user_fields']['ID']]['WELCOME']))
		{
			return true;
		}

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
		{
			$_SESSION['SUPPORT24'][$params['user_fields']['ID']]['WELCOME'] = time();
			return true;
		}

		$martaCheck = \CUserOptions::GetOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, 0, $params['user_fields']['ID']);
		if ($martaCheck > 0)
		{
			$_SESSION['SUPPORT24'][$params['user_fields']['ID']]['WELCOME'] = $martaCheck;
			return true;
		}

		$_SESSION['SUPPORT24'][$params['user_fields']['ID']]['WELCOME'] = time();

		if (self::isActiveFreeSupport() && self::isActiveFreeSupportForUser($params['user_fields']['ID']))
		{
			self::scheduleAction($params['user_fields']['ID'], self::SCHEDULE_ACTION_WELCOME, '', 10);
		}

		\CUserOptions::SetOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, time(), false, $params['user_fields']['ID']);

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
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onWelcomeMessage',
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

		Im\Bot::update(Array('BOT_ID' => self::getBotId()), $botParams);

		return true;
	}

	/**
	 * Sends message to client.
	 * @inheritDoc
	 *
	 * @param array $messageFields Command arguments.
	 * <pre>
	 * [
	 * 	(int) TO_USER_ID
	 * 	(int) FROM_USER_ID
	 * 	(int|string) DIALOG_ID
	 * 	(array) PARAMS
	 * 	(string) MESSAGE
	 * 	(array | \CIMMessageParamAttach) ATTACH
	 * 	(array | Im\Bot\Keyboard) KEYBOARD
	 * 	(string) SYSTEM - N|Y
	 * 	(string) URL_PREVIEW  - N|Y
	 * ]
	 * </pre>
	 *
	 * @return array
	 */
	public static function sendMessage($messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return [];
		}

		$userId = 0;

		if (isset($messageFields['TO_USER_ID']))
		{
			$userId = $messageFields['TO_USER_ID'];
		}
		else if (isset($messageFields['DIALOG_ID']))
		{
			if (preg_match('/^[0-9]+$/i', $messageFields['DIALOG_ID']))
			{
				$userId = $messageFields['DIALOG_ID'];
			}
			else if (
				$messageFields['DIALOG_ID'] === 'ADMIN'
				|| $messageFields['DIALOG_ID'] === 'BUSINESS'
			)
			{
				if ($messageFields['DIALOG_ID'] === 'ADMIN')
				{
					$users = self::getAdministrators();
				}
				else if ($messageFields['DIALOG_ID'] === 'BUSINESS')
				{
					$users = self::getBusinessUsers();
				}

				$result = [];
				foreach ($users as $userId)
				{
					$messageFields['DIALOG_ID'] = $userId;
					$result = array_merge($result, self::sendMessage($messageFields));
				}

				return $result;
			}
		}

		$messageFields['FROM_USER_ID'] = self::getBotId();

		if (!isset($messageFields['PARAMS'], $messageFields['PARAMS'][self::MESSAGE_PARAM_ALLOW_QUOTE]))
		{
			$messageFields['PARAMS'][self::MESSAGE_PARAM_ALLOW_QUOTE] = 'Y';
		}

		$messageFields['MESSAGE'] = self::replacePlaceholders($messageFields['MESSAGE'], $userId);

		$messageId = \CIMMessenger::Add($messageFields);
		if ($messageId)
		{
			return [$messageId];
		}

		return [];
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
		if (!($userId === 'ADMIN' || $userId === 'BUSINESS'))
		{
			$userId = intval($userId);
			if ($userId <= 0)
			{
				return false;
			}
		}

		$result = \CAgent::GetList(array(), array('MODULE_ID'=>'imbot', '=NAME'=> __CLASS__."::scheduledActionAgent(".$userId.", '".$action."', '".$code."');"));
		while($agent = $result->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		$delaySeconds = intval($delayMinutes) * 60;

		\CAgent::AddAgent(__CLASS__."::scheduledActionAgent(".$userId.", '".$action."', '".$code."');", "imbot", "N", $delaySeconds, "", "Y", ConvertTimeStamp(time()+\CTimeZone::GetOffset()+$delaySeconds, "FULL"));

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
		if (!($userId === 'ADMIN' || $userId === 'BUSINESS'))
		{
			$userId = intval($userId);
		}
		$action = trim($action);
		$code = trim($code);

		$filter = array('MODULE_ID' => 'imbot' );

		if (!$userId)
		{
			$filter['NAME'] = __CLASS__."::scheduledActionAgent(%";
		}
		else
		{
			if ($action && $code)
			{
				$filter['=NAME'] = __CLASS__."::scheduledActionAgent(".$userId.", '".$action."', '".$code."');";
			}
			else if ($action)
			{
				$filter['NAME'] = __CLASS__."::scheduledActionAgent(".$userId.", '".$action."', %";
			}
			else
			{
				$filter['NAME'] = __CLASS__."::scheduledActionAgent(".$userId.", %";
			}
		}

		$result = \CAgent::GetList(array(), $filter);
		while($agent = $result->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		return true;
	}

	/**
	 * @param int $userId
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
	 * @param int $userId
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

		if (!($userId === 'ADMIN' || $userId === 'BUSINESS'))
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
		else if ($action == self::SCHEDULE_ACTION_INVOLVEMENT)
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
			$currentDay = floor((time() - $generationDate) / 86400) + 1;

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

			self::sendMessage(Array(
				'DIALOG_ID' => $userId,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));

			return true;
		}
		else if ($action == self::SCHEDULE_ACTION_MESSAGE)
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

			self::sendMessage(Array(
				'DIALOG_ID' => $userId,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));
		}
		else if ($action == self::SCHEDULE_ACTION_PARTNER_JOIN)
		{
			$keyboard = new Im\Bot\Keyboard(self::getBotId());
			$keyboard->addButton(Array(
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_YES'),
				"BG_COLOR" => "#29619b",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => self::COMMAND_SUPPORT24,
				"COMMAND_PARAMS" => self::COMMAND_SUPPORT24_ACTIVATE_PARTNER,
			));
			$keyboard->addButton(Array(
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_NO'),
				"BG_COLOR" => "#990000",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => "support24",
				"COMMAND_PARAMS" => self::COMMAND_SUPPORT24_DECLINE_PARTNER_REQUEST,
			));

			self::sendMessage(Array(
				'DIALOG_ID' => $userId,
				'MESSAGE' => self::getMessage('PARTNER_REQUEST'),
				'KEYBOARD' => $keyboard,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));

			return true;
		}
		elseif ($action == self::SCHEDULE_ACTION_HIDE_DIALOG)
		{
			$botId = self::getBotId();
			\CIMContactList::DialogHide($botId, $userId);
		}
		else
		{
			return false;
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

		$messages = unserialize(
			Option::get('imbot', $optionCode, "a:0:{}"),
			['allowed_classes' => false]
		);

		return isset($messages[$code])? $messages[$code]: '';
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

		$message = str_replace(Array(
			'#SUPPORT_ID#',
			'#SUPPORT_NAME#',
			'#TARIFF_NAME#',
			'#TARIFF_CODE#',
			'#PREVIOUS_TARIFF_NAME#',
			'#PREVIOUS_TARIFF_CODE#',
		), Array(
			self::getBotId(),
			self::getBotName(),
			$currentLicenceName,
			$currentLicence,
			$previousLicenceName,
			$previousLicence,
		), $message);

		if (self::isEnabled())
		{
			$message = str_replace(Array(
				'#PARTNER_NAME#',
				'#PARTNER_BOT_ID#',
				'#PARTNER_BOT_NAME#',
			), Array(
				Partner24::getPartnerName(),
				Partner24::getBotId(),
				Partner24::getBotName(),
			), $message);
		}

		return $message;
	}
	//endregion
}