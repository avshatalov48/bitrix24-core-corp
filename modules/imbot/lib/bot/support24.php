<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Support24 extends Network
{
	const BOT_CODE = "support24";

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

	const SCHEDULE_DELETE_ALL = null;

	const INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME = 8; // hour

	const LIST_BOX_SUPPORT_CODES = Array(
		'ru' => '4df232699a9e1d0487c3972f26ea8d25',
		'default' => '1a146ac74c3a729681c45b8f692eab73',
	);

	private static $isAdmin = Array();
	private static $isIntegrator = Array();

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

	public static function getSupportLevel()
	{
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
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

	public static function getLicenceLanguage()
	{
		$lang = 'en';
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$prefix = \CBitrix24::getLicensePrefix();
			if ($prefix)
			{
				$lang = $prefix;
			}
		}
		else
		{
			if (\Bitrix\Main\Localization\CultureTable::getList(array('filter' => array('=CODE' => 'ru')))->fetch())
			{
				$lang = 'ru';
			}
		}

		return $lang;
	}

	public static function getBusinessUsers()
	{
		$users = null;
		$option = \Bitrix\Main\Config\Option::get("bitrix24", "business_tools_unlim_users", false);
		if ($option)
		{
			$users = explode(",", $option);
		}

		return $users;
	}

	public static function getAdministrators()
	{
		$users = array();
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$users = \CBitrix24::getAllAdminId();
		}
		else
		{
			$res = \CAllGroup::GetGroupUserEx(1);
			while($row = $res->fetch())
			{
				$users[] = $row["USER_ID"];
			}
		}

		return $users;
	}

	private static function getBotCode()
	{
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
			{
				$code = \Bitrix\Main\Config\Option::get('imbot', "support24_paid_code", "");
			}
			else
			{
				$code = \Bitrix\Main\Config\Option::get('imbot', "support24_free_code", "");
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

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$botId = parent::join(self::getBotCode());
		if (!$botId)
			return false;

		\Bitrix\Main\Config\Option::set('imbot', "support24_bot_id", $botId);
		\Bitrix\Main\Config\Option::set('imbot', "support24_support_level", self::getSupportLevel());

		self::updateBotProperties();

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible("main", "OnAfterSetOption_~controller_group_name", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterLicenseChange");
		$eventManager->registerEventHandlerCompatible("main", "OnAfterUserAuthorize", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterUserAuthorize");

		self::scheduleAction(1, self::SCHEDULE_ACTION_WELCOME, '', 10);

		\Bitrix\Im\Command::register(Array(
			'MODULE_ID' => self::MODULE_ID,
			'BOT_ID' => $botId,
			'COMMAND' => 'support24',
			'HIDDEN' => 'Y',
			'CLASS' => __CLASS__,
			'METHOD_COMMAND_ADD' => 'onCommandAdd'
		));

		return $botId;
	}

	public static function unRegister($code = '', $serverRequest = true)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		self::sendRequestFinalizeSession();

		$code = self::getBotCode();
		$botId = self::getBotId();

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $botId));
		if (!$result)
		{
			return false;
		}

		self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);

		\Bitrix\Main\Config\Option::set('imbot', "support24_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$code."_bot_id", 0);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler("main", "OnAfterSetOption_~controller_group_name", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterLicenseChange");
		$eventManager->unregisterEventHandler("main", "OnAfterUserAuthorize", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterUserAuthorize");

		if ($serverRequest)
		{
			$result = self::sendUnregisterRequest($code, $botId);
		}

		return $result;
	}

	public static function isEnabled()
	{
		return self::getBotId() > 0;
	}

	public static function getBotId()
	{
		return \Bitrix\Main\Config\Option::get('imbot', "support24_bot_id", 0);
	}

	public static function getBotName()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE? "support24_free_name": "support24_paid_name";
		return \Bitrix\Main\Config\Option::get('imbot', $optionName, '');
	}

	public static function getBotDesc()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE? "support24_free_desc": "support24_paid_desc";
		return \Bitrix\Main\Config\Option::get('imbot', $optionName, '');
	}

	public static function getBotAvatar()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE? "support24_free_avatar": "support24_paid_avatar";
		return \Bitrix\Main\Config\Option::get('imbot', $optionName, '');
	}

	public static function getPartnerId()
	{
		return 0;
	}

	public static function getPartnerName()
	{
		return '';
	}

	public static function getPartnerCode()
	{
		return \Bitrix\Main\Config\Option::get("bitrix24", "partner_ol", "");
	}

	public static function getPartnerData()
	{
		return '';
	}

	public static function getFreeSupportLifeTime()
	{
		return (int)\Bitrix\Main\Config\Option::get('imbot', "support24_free_days", 16);
	}

	public static function isFreeSupportLifeTimeExpired()
	{
		$generationDate = (int)\Bitrix\Main\Config\Option::get('imbot', 'support24_free_start_date', 0);
		if ($generationDate == 0)
		{
			\Bitrix\Main\Config\Option::set('imbot', 'support24_free_start_date', time());
			return true;
		}

		$isActive = time() - $generationDate < 86400 * self::getFreeSupportLifeTime();

		return !$isActive;
	}

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

	public static function isActiveFreeSupportForAll()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', 'support24_free_for_all', false);
	}

	public static function isActiveFreeSupportForUser($userId)
	{
		if (!self::getBotId())
			return false;

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			return false;

		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		if (self::isActivePartnerSupport() && !self::isUserIntegrator($userId))
			return false;

		if (self::isActiveFreeSupportForAll())
			return true;

		if (\CBitrix24BusinessTools::isLicenseUnlimited())
			return true;

		if (self::isUserAdmin($userId) || self::isUserIntegrator($userId))
			return true;

		$users = \CBitrix24BusinessTools::getUnlimUsers();
		if (in_array($userId, $users))
			return true;

		return false;
	}

	public static function isUserAdmin($userId)
	{
		if (isset(self::$isAdmin[$userId]))
		{
			return self::$isAdmin[$userId];
		}

		global $USER;
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
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

				$groups = \Bitrix\Main\UserTable::getUserGroupIds($userId);
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

		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
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

	public static function isActivePartnerSupport()
	{
		return Partner24::isEnabled() && Partner24::isActiveSupport();
	}

	public static function isActivePaidSupport()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', 'support24_paid_active', false);
	}

	public static function isActivePaidSupportForAll()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', 'support24_paid_for_all', false);
	}

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

	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return false;
	}

	public static function onAnswerAdd($command, $params)
	{
		return self::onReceiveCommand($command, $params);
	}

	public static function onReceiveCommand($command, $params)
	{
		if (isset($params['LINE']['CODE']) && $params['LINE']['CODE'] !== self::getBotCode())
		{
			return new \Bitrix\ImBot\Error(__METHOD__, 'SUPPORT_CODE_MISMATCH', 'Support code is not correct for this portal');
		}

		return parent::onReceiveCommand($command, $params);
	}

	public static function isNeedUpdateBotAvatarAfterNewMessage()
	{
		return (bool)self::getBotAvatar() !== true;
	}

	public static function onWelcomeMessage($dialogId, $joinFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
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
			$chat->DeleteUser(substr($dialogId, 4), self::getBotId());

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

		\CUserOptions::SetOption("imbot", 'support24_welcome_message', time(), false, $messageFields['USER_ID']);

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

	public static function onMessageAdd($messageId, $messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

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

		$message = '';

		if (
			self::isActivePartnerSupport()
			&& !self::isUserIntegrator($messageFields['USER_ID'])
		)
		{
			$message = self::getMessage('MESSAGE_PARTNER');
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isActiveFreeSupport())
			{
				if (!self::isActiveFreeSupportForUser($messageFields['FROM_USER_ID']))
				{
					$message = self::getMessage('MESSAGE_LIMITED');
				}
			}
			else if (!self::isUserIntegrator($messageFields['FROM_USER_ID']))
			{
				$message = self::getMessage('MESSAGE_END');
			}
		}
		else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!self::isActivePaidSupportForUser($messageFields['FROM_USER_ID']))
			{
				$message = self::getMessage('MESSAGE_LIMITED');
			}
		}

		if (!empty($message))
		{
			self::sendMessage(Array(
				'DIALOG_ID' => $messageFields['FROM_USER_ID'],
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));

			return true;
		}

		return parent::onMessageAdd($messageId, $messageFields);
	}

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

		return parent::onStartWriting($params);
	}

	public static function onAfterLicenseChange()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$previousDemoState = \Bitrix\Main\Config\Option::get('imbot', "support24_demo_active", false);

		$previousSupportLevel = \Bitrix\Main\Config\Option::get('imbot', "support24_support_level", "free");
		$currentSupportLevel = self::getSupportLevel();

		$isPreviousSupportLevelPartner = $previousSupportLevel === self::SUPPORT_LEVEL_PARTNER;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		$currentDemoState = $currentLicence == 'demo';
		\Bitrix\Main\Config\Option::set('imbot', "support24_demo_active", $currentDemoState);

		$isSupportLevelChange = $previousSupportLevel != $currentSupportLevel;
		$isDemoLevelChange = $previousDemoState != $currentDemoState;

		if (!$isSupportLevelChange && !$isDemoLevelChange)
		{
			return true;
		}

		if ($isSupportLevelChange)
		{
			\Bitrix\Main\Config\Option::set('imbot', "support24_support_level", $currentSupportLevel);
		}

		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			$previousCode = \Bitrix\Main\Config\Option::get('imbot', "support24_free_code", "");
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_paid_code", "");
		}
		else
		{
			$previousCode = \Bitrix\Main\Config\Option::get('imbot', "support24_paid_code", "");
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_free_code", "");
		}

		if ($isPreviousSupportLevelPartner)
		{
			$previousCode = self::getPartnerCode();
		}

		if ($isSupportLevelChange)
		{
			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);
		}

		if ($currentLicence == 'demo')
		{
			\Bitrix\Main\Config\Option::set('imbot', 'support24_free_start_date', time());
		}

		self::updateBotProperties();

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_LEVEL_CHANGE' => $isSupportLevelChange,
			'IS_DEMO_LEVEL_CHANGE' => $isDemoLevelChange,
		]);

		\Bitrix\Main\Config\Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		$http = new \Bitrix\ImBot\Http(parent::BOT_CODE);
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

	public static function onAfterSupportCodeChange($previousFreeCode = '', $previousPaidCode = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		$previousSupportLevel = self::getSupportLevel() == self::SUPPORT_LEVEL_PAID? self::SUPPORT_LEVEL_FREE: self::SUPPORT_LEVEL_PAID;

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if (!$previousPaidCode)
			{
				return false;
			}

			$previousCode = $previousPaidCode;
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_paid_code", "");
		}
		else
		{
			if (!$previousFreeCode)
			{
				return false;
			}

			$previousCode = $previousFreeCode;
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_free_code", "");
		}

		self::updateBotProperties();

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_CODE_CHANGE' => true,
		]);

		\Bitrix\Main\Config\Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		$http = new \Bitrix\ImBot\Http(parent::BOT_CODE);
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

	public static function onCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] === 'Y')
			return false;

		if ($messageFields['COMMAND_CONTEXT'] !== 'KEYBOARD')
			return false;

		if ($messageFields['MESSAGE_TYPE'] !== IM_MESSAGE_PRIVATE)
			return false;

		if ($messageFields['COMMAND'] !== 'support24')
			return false;

		if ($messageFields['TO_USER_ID'] != self::getBotId())
			return false;

		$messageParams = [];

		if ($messageFields['COMMAND_PARAMS'] === 'activatePartnerSupport')
		{
			$keyboard = new \Bitrix\Im\Bot\Keyboard(self::getBotId());
			$keyboard->addButton(Array(
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_MANAGE'),
				"LINK" => self::getMessage('PARTNER_BUTTON_MANAGE_URL'),
				"CONTEXT" => "DESKTOP",
			));
			$messageParams['KEYBOARD'] = $keyboard;

			$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
			$attach->AddMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
			$messageParams['ATTACH'] = $attach;

			$result = Partner24::acceptRequest($messageFields['FROM_USER_ID']);
			if (!$result)
			{
				return false;
			}
		}
		else
		{
			if ($messageFields['COMMAND_PARAMS'] === 'deactivatePartnerSupport')
			{
				Partner24::deactivate($messageFields['FROM_USER_ID']);

				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
				$attach->AddMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
				$messageParams['ATTACH'] = $attach;
			}
			else if ($messageFields['COMMAND_PARAMS'] === 'declinePartnerRequest')
			{
				Partner24::declineRequest($messageFields['FROM_USER_ID']);

				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::PROBLEM);
				$attach->AddMessage(self::getMessage('PARTNER_REQUEST_REJECTED'));
				$messageParams['ATTACH'] = $attach;
			}
			$messageParams['KEYBOARD'] = 'N';
		}

		\CIMMessageParam::Set($messageId, $messageParams);
		\CIMMessageParam::SendPull($messageId, ['ATTACH', 'KEYBOARD']);

		return true;
	}


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
		$dialogs = \Bitrix\Main\Application::getInstance()->getConnection()->query($query)->fetchAll();

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
					\Bitrix\Im\Model\MessageTable::add(Array(
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
					\Bitrix\Im\Model\MessageTable::add(Array(
						'CHAT_ID' => $dialog['CHAT_ID'],
						'AUTHOR_ID' => self::getBotId(),
						'MESSAGE' => self::replacePlaceholders($message, $dialog['USER_ID'])
					));
				}
			}
		}

		return true;
	}

	public static function sendRequestFinalizeSession($message = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_paid_code", "");
		}
		else
		{
			$currentCode = \Bitrix\Main\Config\Option::get('imbot', "support24_free_code", "");
		}

		$http = new \Bitrix\ImBot\Http(parent::BOT_CODE);
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

	public static function onAfterUserAuthorize($params)
	{
		$auth = \CHTTP::ParseAuthRequest();
		if (
			isset($auth["basic"]) && $auth["basic"]["username"] <> '' && $auth["basic"]["password"] <> ''
			&& strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'bitrix') === false
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

		$martaCheck = \CUserOptions::GetOption("imbot", 'support24_welcome_message', 0, $params['user_fields']['ID']);
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

		\CUserOptions::SetOption("imbot", 'support24_welcome_message', time(), false, $params['user_fields']['ID']);

		return true;
	}

	public static function updateBotProperties()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		$botData = \Bitrix\Im\User::getInstance(self::getBotId());
		$userAvatar = \Bitrix\Im\User::uploadAvatar(self::getBotAvatar(), self::getBotId());
		if ($userAvatar && $botData->getAvatarId() != $userAvatar)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$connection->query("UPDATE b_user SET PERSONAL_PHOTO = ".intval($userAvatar)." WHERE ID = ".intval(self::getBotId()));
		}

		$botCache = \Bitrix\Im\Bot::getCache(self::getBotId());
		if ($botCache['APP_ID'] !== self::getBotCode())
		{
			\Bitrix\Main\Config\Option::set(self::MODULE_ID, parent::BOT_CODE.'_'.$botCache['APP_ID']."_bot_id", 0);
			\Bitrix\Main\Config\Option::set(self::MODULE_ID, parent::BOT_CODE.'_'.self::getBotCode()."_bot_id", self::getBotId());
		}

		\Bitrix\Im\Bot::update(Array('BOT_ID' => self::getBotId()), Array(
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onWelcomeMessage',
			'TEXT_CHAT_WELCOME_MESSAGE' => '',
			'TEXT_PRIVATE_WELCOME_MESSAGE' => '',
			'VERIFIED' => 'Y',
			'CODE' => 'network_'.self::getBotCode(),
			'APP_ID' => self::getBotCode(),
		));

		$user = new \CUser;
		$user->Update(self::getBotId(), Array(
			'LOGIN' => 'bot_imbot_support24',
			'NAME' => self::getBotName(),
			'WORK_POSITION' => self::getBotDesc()
		));

		return true;
	}

	public static function sendMessage($messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
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
			if (preg_match('/^[0-9]{1,}$/i', $messageFields['DIALOG_ID']))
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
		$messageFields['PARAMS']['IMOL_QUOTE_MSG'] = 'Y';

		$messageFields['MESSAGE'] = self::replacePlaceholders($messageFields['MESSAGE'], $userId);

		$messageId = \CIMMessenger::Add($messageFields);
		if ($messageId)
		{
			return [$messageId];
		}

		return [];
	}

	public static function replacePlaceholders($message, $userId = 0)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return $message;
		}

		if ($userId)
		{
			$message = str_replace(Array(
				'#USER_NAME#',
				'#USER_LAST_NAME#',
				'#USER_FULL_NAME#',
			), Array(
				\Bitrix\Im\User::getInstance($userId)->getName(false),
				\Bitrix\Im\User::getInstance($userId)->getLastName(false),
				\Bitrix\Im\User::getInstance($userId)->getFullName(false),
			), $message);
		}

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
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

	public static function scheduledActionAgent($userId, $action, $code = '')
	{
		self::execScheduleAction($userId, $action, $code);

		return "";
	}

	public static function execScheduleAction($userId, $action, $code = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
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

			$generationDate = (int)\Bitrix\Main\Config\Option::get('imbot', 'support24_free_start_date', 0);
			$currentDay = floor((time() - $generationDate) / 86400) + 1;

			self::scheduleAction($userId, self::SCHEDULE_ACTION_INVOLVEMENT, '', 24*60);

			$message = self::getMessage($currentDay);
			if (strlen($message) <= 0)
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
			$dialog = \Bitrix\Main\Application::getInstance()->getConnection()->query($query)->fetch();

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
			if (strlen($code) <= 0)
			{
				return false;
			}

			$message = self::getMessage($code);
			if (strlen($message) <= 0)
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
			$keyboard = new \Bitrix\Im\Bot\Keyboard(self::getBotId());
			$keyboard->addButton(Array(
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_YES'),
				"BG_COLOR" => "#29619b",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => "support24",
				"COMMAND_PARAMS" => "activatePartnerSupport",
			));
			$keyboard->addButton(Array(
				"DISPLAY" => "LINE",
				"TEXT" => self::getMessage('PARTNER_BUTTON_NO'),
				"BG_COLOR" => "#990000",
				"TEXT_COLOR" => "#fff",
				"BLOCK" => "Y",
				"COMMAND" => "support24",
				"COMMAND_PARAMS" => "declinePartnerRequest",
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
		else
		{
			return false;
		}

		return true;
	}

	public static function getMessage($code, $supportLevel = null)
	{
		if (!$supportLevel)
		{
			$supportLevel = self::getSupportLevel();
		}
		$supportLevel = strtolower($supportLevel);

		if (substr($code, 0, 4) == 'DAY_')
		{
			$code = substr($code, 4);
		}

		$optionCode = $supportLevel == self::SUPPORT_LEVEL_FREE? "support24_free_messages": "support24_paid_messages";
		$messages = unserialize(\Bitrix\Main\Config\Option::get('imbot', $optionCode, "a:0:{}"));

		return isset($messages[$code])? $messages[$code]: '';
	}
}