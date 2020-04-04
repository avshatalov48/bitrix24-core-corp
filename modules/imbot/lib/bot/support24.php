<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Support24 extends Network
{
	const BOT_CODE = "support24";

	const SUPPORT_LEVEL_FREE = 'free';
	const SUPPORT_LEVEL_PAID = 'paid';
	const SUPPORT_LEVEL_PARTNER = 'partner';

	const SUPPORT_ACTIVE_UNLIMITED = -1;

	const SCHEDULE_ACTION_WELCOME = 'welcome';
	const SCHEDULE_ACTION_INVOLVEMENT = 'involvement';
	const SCHEDULE_ACTION_MESSAGE = 'message';

	const SCHEDULE_DELETE_ALL = null;

	const INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME = 8; // hour

	const LIST_BOX_SUPPORT_CODES = Array(
		'ru' => '4df232699a9e1d0487c3972f26ea8d25',
		'default' => '1a146ac74c3a729681c45b8f692eab73',
	);

	private static $isAdmin = Array();
	private static $isIntegrator = Array();

	public static function getSupportLevel()
	{
		$supportLevel = self::SUPPORT_LEVEL_FREE;

		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$partnerId = self::getPartnerId();
			if ($partnerId > 0 && self::getPartnerCode() && !\CBitrix24::IsNfrLicense())
			{
				$supportLevel = self::SUPPORT_LEVEL_PARTNER;
			}
			else if (self::isActivePaidSupport())
			{
				$supportLevel = self::SUPPORT_LEVEL_PAID;
			}
		}
		else
		{
			$supportLevel = self::SUPPORT_LEVEL_PAID;
		}

		return $supportLevel;
	}

	private static function getLicenceLanguage()
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

	private static function getBusinessUsers()
	{
		$users = null;
		$option = \Bitrix\Main\Config\Option::get("bitrix24", "business_tools_unlim_users", false);
		if ($option)
		{
			$users = explode(",", $option);
		}

		return $users;
	}

	private static function getAdministrators()
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
		$lang = \Bitrix\ImBot\Bot\Support24::getLicenceLanguage();

		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (self::getSupportLevel() == self::SUPPORT_LEVEL_PARTNER)
			{
				$code = self::getPartnerCode();
			}
			else if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
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

	private static function getNetworkOptions()
	{
		return self::getSupportLevel() == self::SUPPORT_LEVEL_PARTNER? self::getPartnerData(): Array();
	}

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$botId = parent::join(self::getBotCode(), self::getNetworkOptions());
		if (!$botId)
			return false;

		\Bitrix\Main\Config\Option::set('imbot', "support24_bot_id", $botId);
		\Bitrix\Main\Config\Option::set('imbot', "support24_support_level", self::getSupportLevel());

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_PARTNER)
		{
			self::updateBotProperties();
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible("main", "OnAfterSetOption_~controller_group_name", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterLicenseChange");
		$eventManager->registerEventHandlerCompatible("main", "OnAfterUserAuthorize", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterUserAuthorize");

		self::scheduleAction(1, self::SCHEDULE_ACTION_WELCOME, '', 10);

		return $botId;
	}

	public static function unRegister($code = '', $serverRequest = true)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$result = parent::unRegister(self::getBotCode(), $serverRequest);
		if (!$result)
			return false;

		self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);

		\Bitrix\Main\Config\Option::set('imbot', "support24_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$code."_bot_id", 0);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler("main", "OnAfterSetOption_~controller_group_name", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterLicenseChange");
		$eventManager->unregisterEventHandler("main", "OnAfterUserAuthorize", "imbot", "\Bitrix\ImBot\Bot\Support24", "onAfterUserAuthorize");

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

	public static function getSessionId()
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE? "support24_free_session_id": "support24_paid_session_id";
		return (int)\Bitrix\Main\Config\Option::get('bitrix24', $optionName, 0);
	}

	public static function setSessionId($sessionId)
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE? "support24_free_session_id": "support24_paid_session_id";
		return \Bitrix\Main\Config\Option::get('bitrix24', $optionName, $sessionId);
	}

	public static function getPartnerId()
	{
		return \Bitrix\Main\Config\Option::get('bitrix24', "partner_id", 0);
	}

	public static function getPartnerName()
	{
		if (!self::getPartnerId())
			return '';

		return \Bitrix\Main\Config\Option::get('bitrix24', "partner_name", '');
	}

	public static function getPartnerCode()
	{
		if (!self::getPartnerId())
			return '';

		return \Bitrix\Main\Config\Option::get("bitrix24", "partner_ol", "");
	}

	public static function getPartnerData()
	{
		if (!self::getPartnerId())
			return '';

		return Array(
			'TYPE' => 'PARTNER',
			'PARTNER_ID' => self::getPartnerId(),
			'PARTNER_CODE' => self::getPartnerCode(),
			'PARTNER_NAME' => self::getPartnerName(),
		);
	}

	public static function getSupportLifeTime()
	{
		if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			$days = \Bitrix\Main\Config\Option::get('imbot', "support24_free_days", 16);
		}
		else
		{
			$days = self::SUPPORT_ACTIVE_UNLIMITED;
		}

		return (int)$days;
	}

	public static function isActiveFreeSupport()
	{
		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
		{
			return false;
		}

		if (self::getSupportLifeTime() == self::SUPPORT_ACTIVE_UNLIMITED)
			return true;

		$generationDate = (int)\Bitrix\Main\Config\Option::get('imbot', 'support24_free_start_date', 0);
		if ($generationDate == 0)
		{
			\Bitrix\Main\Config\Option::set('imbot', 'support24_free_start_date', time());
			return true;
		}

		return time() - $generationDate < 86400 * self::getSupportLifeTime();
	}

	public static function isActiveFreeSupportForUser($userId)
	{
		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			return false;

		if (!\CModule::IncludeModule('bitrix24'))
			return false;

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

	public static function isActivePaidSupport()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', 'support24_paid_active', false);
	}

	public static function isActivePaidSupportForUser($userId)
	{
		if (self::getSupportLevel() != self::SUPPORT_LEVEL_PAID)
		{
			return false;
		}

		if (!self::isActivePaidSupport())
		{
			return false;
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
		$sessionId = 0;
		if($command == "operatorMessageAdd")
		{
			if (is_array($params['PARAMS']) && isset($params['PARAMS']['IMOL_SID']))
			{
				$sessionId = intval($params['PARAMS']['IMOL_SID']);
			}
		}
		else if($command == "operatorMessageReceived")
		{
			$sessionId = $params['SESSION_ID'];
		}

		if ($sessionId)
		{
			if (self::getSessionId() != $sessionId)
			{
				self::setSessionId($sessionId);
			}
		}

		return parent::onReceiveCommand($command, $params);
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

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
		{
			if (self::isActiveFreeSupport())
			{
				if (self::isUserIntegrator($messageFields['USER_ID']))
				{
					$message = self::getMessage('WELCOME_INTEGRATOR');
				}
				else if (self::isActiveFreeSupportForUser($messageFields['USER_ID']))
				{
					$message = self::getMessage('WELCOME');
				}
				else
				{
					$message = self::getMessage('WELCOME_LIMITED');
				}
			}
			else if (self::isUserIntegrator($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME_INTEGRATOR');
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

		$message = self::replacePlaceholders($message, $messageFields['USER_ID']);

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

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
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

		$message = self::replacePlaceholders($message, $messageFields['FROM_USER_ID']);

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

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PARTNER)
			return false;

		$previousDemoState = \Bitrix\Main\Config\Option::get('imbot', "support24_demo_active", false);

		$previousSupportLevel = \Bitrix\Main\Config\Option::get('imbot', "support24_support_level", "free");
		$currentSupportLevel = self::getSupportLevel();

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

		if ($isSupportLevelChange)
		{
			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);
		}

		if ($currentLicence == 'demo')
		{
			\Bitrix\Main\Config\Option::set('imbot', 'support24_free_start_date', time());
		}

		$userLimit = "!= ".self::getBotId();
		$businessUsers = self::getBusinessUsers();

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			if ($businessUsers)
			{
				$userLimit = 'IN ('.implode(',', $businessUsers).')';
			}
		}
		else
		{
			$users = self::getAdministrators();
			if ($users)
			{
				$userLimit = 'IN ('.implode(',', $users).')';
			}
		}

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
			and RU.USER_ID ".$userLimit."
			and RB.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			and RU.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			and RB.CHAT_ID = RU.CHAT_ID
		";
		$dialogs = \Bitrix\Main\Application::getInstance()->getConnection()->query($query)->fetchAll();

		self::updateBotProperties();

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

				if (!$message)
				{
					continue;
				}

				$message = self::replacePlaceholders($message, $dialog['USER_ID']);

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
						'MESSAGE' => $message
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
						if (is_array($businessUsers) && in_array($dialog['USER_ID'], $businessUsers))
						{
							$message = self::getMessage('CHANGE_DEMO');
						}
					}
				}

				if (!$message)
				{
					continue;
				}

				$message = self::replacePlaceholders($message, $dialog['USER_ID']);

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
						'MESSAGE' => $message
					));
				}
			}
		}

		$http = new \Bitrix\ImBot\Http(parent::BOT_CODE);
		$http->query(
			'clientChangeLicence',
			Array(
				'BOT_ID' => self::getBotId(),
				'SESSION_ID' => self::getSessionId() ,
				'PREVIOUS_LICENCE_TYPE' => $previousLicence,
				'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName($previousLicence),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'PREVIOUS_BOT_CODE' => $previousCode,
				'CURRENT_BOT_CODE' => $currentCode,
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
			return false;

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PARTNER)
			return false;

		\Bitrix\Im\Bot::update(Array('BOT_ID' => self::getBotId()), Array(
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onWelcomeMessage',
			'TEXT_CHAT_WELCOME_MESSAGE' => '',
			'TEXT_PRIVATE_WELCOME_MESSAGE' => '',
			'VERIFIED' => 'Y',
			'CODE' => 'network_'.self::getBotCode(),
			'APP_ID' => self::getBotCode(),
			'PROPERTIES' => Array(
				'NAME' => self::getBotName(),
				'WORK_POSITION' => self::getBotDesc()
			)
		));

		return true;
	}

	public static function sendMessage($messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$messageFields['FROM_USER_ID'] = self::getBotId();
		$messageFields['PARAMS']['IMOL_QUOTE_MSG'] = 'Y';

		return \CIMMessenger::Add($messageFields);
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

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);
		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);

		$currentLicenceName = \CBitrix24::getLicenseName($currentLicence);
		$currentLicenceName = $currentLicenceName? $currentLicenceName: $currentLicence;

		$previousLicenceName = \CBitrix24::getLicenseName($previousLicence);
		$previousLicenceName = $previousLicenceName? $previousLicenceName: $previousLicence;

		$message = str_replace(Array(
			'#TARIFF_NAME#',
			'#TARIFF_CODE#',
			'#PREVIOUS_TARIFF_NAME#',
			'#PREVIOUS_TARIFF_CODE#',
		), Array(
			$currentLicenceName,
			$currentLicence,
			$previousLicenceName,
			$previousLicence,
		), $message);

		return $message;
	}

	public static function scheduleAction($userId, $action, $code = '', $delayMinutes = 1)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

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
		$userId = intval($userId);
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

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
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
				'MESSAGE' => self::replacePlaceholders($message, $userId),
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
				'MESSAGE' => self::replacePlaceholders($message, $userId),
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));
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