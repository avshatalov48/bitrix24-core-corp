<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Partner24 extends Network
{
	const BOT_CODE = "partner24";

	public static function getSupportLevel()
	{
		return Support24::getSupportLevel();
	}

	public static function getLicenceLanguage()
	{
		return Support24::getLicenceLanguage();
	}

	private static function getBusinessUsers()
	{
		return Support24::getBusinessUsers();
	}

	private static function getAdministrators()
	{
		return Support24::getAdministrators();
	}

	public static function getPartnerName()
	{
		return \Bitrix\Main\Config\Option::get('imbot', "partner24_support_name", "");
	}

	public static function getBotCode()
	{
		return \Bitrix\Main\Config\Option::get('imbot', "partner24_support_code", "");
	}

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!Support24::getBotId())
		{
			\Bitrix\ImBot\Bot\Support24::register();
		}

		$supportCode = !empty($params['CODE'])? $params['CODE']: self::getBotCode();

		if (self::getBotId() > 0)
		{
			$botId = Network::getNetworkBotId($supportCode, true);
			if ($botId)
			{
				return $botId;
			}

			// use change method instead
			return false;
		}

		$search = self::search($supportCode, true);
		if (!$search)
		{
			return false;
		}

		$botId = \Bitrix\ImBot\Bot\Network::register($search[0]);
		if (!$botId)
		{
			return false;
		}

		if (isset($params['NAME']) && !empty($params['NAME']))
		{
			$supportName = $params['NAME'];
		}
		else
		{
			$supportName = $search[0]['LINE_NAME'];
		}

		\Bitrix\Main\Config\Option::set('imbot', "partner24_bot_id", $botId);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_name", $supportName);

		self::updateBotProperties();

		return $botId;
	}

	public static function unRegister($code = '', $serverRequest = true)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		global $USER;
		self::deactivate($USER->GetID());

		$code = self::getBotCode();
		$botId = self::getBotId();

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $botId));
		if (!$result)
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('imbot', "partner24_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$code."_bot_id", 0);

		\Bitrix\Main\Config\Option::set('imbot', "partner24_active", false);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", false);

		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_code", '');
		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_name", '');

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
		return \Bitrix\Main\Config\Option::get('imbot', "partner24_bot_id", 0);
	}

	public static function getBotName()
	{
		return \Bitrix\Main\Config\Option::get('imbot', 'partner24_name', '');
	}

	public static function getBotDesc()
	{
		return \Bitrix\Main\Config\Option::get('imbot', 'partner24_desc', '');
	}

	public static function getBotAvatar()
	{
		return \Bitrix\Main\Config\Option::get('imbot', 'partner24_avatar', '');
	}

	public static function isActiveSupport()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', "partner24_active", false);
	}

	public static function isWaitingActivation()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', "partner24_wait_activation", false);
	}

	public static function isActiveSupportForAll()
	{
		return (bool)\Bitrix\Main\Config\Option::get('imbot', 'partner24_for_all', false);
	}

	public static function isActiveSupportForUser($userId)
	{
		if (!self::isActiveSupport())
			return false;

		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		if (self::isActiveSupportForAll())
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
		return Support24::isUserAdmin($userId);
	}

	public static function isUserIntegrator($userId)
	{
		return Support24::isUserIntegrator($userId);
	}

	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return false;
	}

	public static function isNeedUpdateBotAvatarAfterNewMessage()
	{
		return (bool)self::getBotAvatar() !== true;
	}

	public static function onAnswerAdd($command, $params)
	{
		return self::onReceiveCommand($command, $params);
	}

	public static function onReceiveCommand($command, $params)
	{
		if (!self::isActiveSupport())
		{
			return new \Bitrix\ImBot\Error(__METHOD__, 'PARTNER_DISABLED', 'Partner support disabled on this portal');
		}
		else if (isset($params['LINE']['CODE']) && $params['LINE']['CODE'] !== self::getBotCode())
		{
			return new \Bitrix\ImBot\Error(__METHOD__, 'PARTNER_CODE_MISMATCH', 'Partner support code is not correct for this portal');
		}

		return parent::onReceiveCommand($command, $params);
	}

	public static function onWelcomeMessage($dialogId, $joinFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

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

		if (self::isActiveSupport())
		{
			if (self::isUserIntegrator($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME_INTEGRATOR');
			}
			else if (self::isActiveSupportForUser($messageFields['USER_ID']))
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
			$messageCode = Support24::isActivePaidSupport()? 'MESSAGE_END_PAID': 'MESSAGE_END_FREE';
			$message = self::getMessage($messageCode);
		}

		if (empty($message))
		{
			return true;
		}

		self::sendMessage(Array(
			'DIALOG_ID' => $messageFields['USER_ID'],
			'MESSAGE' => $message,
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		));

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

		if (self::isActiveSupport())
		{
			if (!self::isActiveSupportForUser($messageFields['FROM_USER_ID']))
			{
				if (!self::isUserIntegrator($messageFields['FROM_USER_ID']))
				{
					$message = self::getMessage('MESSAGE_LIMITED');
				}
			}
		}
		else
		{
			$messageCode = Support24::isActivePaidSupport()? 'MESSAGE_END_PAID': 'MESSAGE_END_FREE';
			$message = self::getMessage($messageCode);
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
		if (self::isActiveSupport())
		{
			if (self::isUserIntegrator($params['USER_ID']))
			{
				return false;
			}
			else if (!self::isActiveSupportForUser($params['USER_ID']))
			{
				return false;
			}
		}

		return parent::onStartWriting($params);
	}

	public static function onAfterSupportCodeChange($currentCode = '', $previousCode = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		if (!$previousCode)
		{
			return false;
		}

		self::updateBotProperties();

		self::sendMessageForRecent(self::getMessage('CHANGE_CODE'));

		\Bitrix\Main\Config\Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		\Bitrix\Main\Config\Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		self::sendRequestFinalizeSession();

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
				'MESSAGE' => Partner24::getMessage('PARTNER_INFO_DEACTIVATE'),
			),
			true
		);

		return true;
	}

	public static function sendRequestFinalizeSession()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
			return false;

		if (!self::getBotId())
			return false;

		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);
		$currentCode = self::getBotCode();

		$http = new \Bitrix\ImBot\Http(parent::BOT_CODE);
		$http->query(
			'clientRequestFinalizeSession',
			Array(
				'BOT_ID' => self::getBotId(),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => \CBitrix24::getLicenseName($currentLicence),
				'CURRENT_BOT_CODE' => $currentCode,
				'MESSAGE' => Partner24::getMessage('PARTNER_INFO_DEACTIVATE'),
			),
			false
		);

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
			'LOGIN' => 'bot_imbot_partner24',
			'NAME' => self::getBotName(),
			'WORK_POSITION' => self::getBotDesc()
		));

		return true;
	}

	public static function replacePlaceholders($message, $userId = 0)
	{
		return Support24::replacePlaceholders($message, $userId);
	}

	public static function getMessage($code)
	{
		$messages = unserialize(\Bitrix\Main\Config\Option::get('imbot', 'partner24_messages', "a:0:{}"));
		return isset($messages[$code])? $messages[$code]: '';
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

	private static function sendMessageForRecent($message)
	{
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

		foreach ($dialogs as $dialog)
		{
			if ($dialog['USER_ID'] == self::getBotId())
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

		return true;
	}

	public static function activate(int $userId, string $supportCode, string $supportName = null)
	{
		if (self::getBotId())
		{
			return self::change($userId, $supportCode, $supportName);
		}

		$botId = self::register(['CODE' => $supportCode, 'NAME' => $supportName]);
		if (!$botId)
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('imbot', "partner24_active", true);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", false);

		Support24::sendMessage([
			'DIALOG_ID' => 'ADMIN',
			'MESSAGE' => Support24::getMessage('PARTNER_REQUEST_ACTIVATED'),
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		]);

		self::clientMessageSend([
			'BOT_ID' => self::getBotId(),
			'USER_ID' => $userId,
			'ATTACH' => [['MESSAGE' => Partner24::getMessage('PARTNER_INFO_ACTIVATE')]],
		]);

		Support24::sendRequestFinalizeSession(Partner24::getMessage('SUPPORT_INFO_DEACTIVATE'));

		return true;
	}

	public static function deactivate(int $userId)
	{
		if(!self::isActiveSupport())
		{
			return true;
		}

		$messageCode = Support24::isActivePaidSupport()? 'DEACTIVATE_PAID': 'DEACTIVATE_FREE';
		self::sendMessageForRecent(self::getMessage($messageCode));

		self::sendRequestFinalizeSession();

		\Bitrix\Main\Config\Option::set('imbot', "partner24_active", false);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", false);

		return true;
	}

	public static function change(int $userId, string $supportCode, string $supportName = null)
	{
		$prevSupportCode = self::getBotCode();

		self::setOptions($supportCode, $supportName);
		self::onAfterSupportCodeChange($supportCode, $prevSupportCode);

		self::clientMessageSend([
			'BOT_ID' => self::getBotId(),
			'USER_ID' => $userId,
			'ATTACH' => [['MESSAGE' => self::getMessage('PARTNER_INFO_ACTIVATE')]],
		]);

		\Bitrix\Main\Config\Option::set('imbot', "partner24_active", true);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", false);

		Support24::sendRequestFinalizeSession(Partner24::getMessage('SUPPORT_INFO_DEACTIVATE'));

		return true;
	}

	public static function setOptions(string $supportCode, string $supportName = null)
	{
		if (!$supportCode)
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_code", $supportCode);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_name", $supportName? $supportName: '');

		return true;
	}

	public static function sendRequest(string $supportCode, string $supportName = null)
	{
		if (self::isActiveSupport())
		{
			return false;
		}

		if (!Support24::getBotId())
		{
			return false;
		}

		if (!self::setOptions($supportCode, $supportName))
		{
			return false;
		}

		\Bitrix\ImBot\Bot\Support24::execScheduleAction('ADMIN', 'partner_join');
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", true);

		return true;
	}

	public static function acceptRequest(int $userId)
	{
		$supportCode = self::getBotCode();
		$supportName = self::getPartnerName();

		return self::activate($userId, $supportCode, $supportName);
	}

	public static function declineRequest(int $userId)
	{
		if (self::isActiveSupport())
		{
			return false;
		}

		if (!self::getBotName())
		{
			return true;
		}

		\Bitrix\Main\Config\Option::set('imbot', "partner24_support_name", "");
		\Bitrix\Main\Config\Option::set('imbot', "partner24_active", false);
		\Bitrix\Main\Config\Option::set('imbot', "partner24_wait_activation", false);

		return true;
	}
}