<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\ImBot;

Loc::loadMessages(__FILE__);

class Partner24 extends Network implements NetworkBot
{
	const BOT_CODE = "partner24";

	public const OPTION_BOT_ID = 'partner24_bot_id';
	public const OPTION_BOT_NAME = 'partner24_name';
	public const OPTION_BOT_DESC = 'partner24_desc';
	public const OPTION_BOT_AVATAR = 'partner24_avatar';
	public const OPTION_BOT_MESSAGES = 'partner24_messages';
	public const OPTION_BOT_FOR_ALL = 'partner24_for_all';
	public const OPTION_BOT_ACTIVE = 'partner24_active';
	public const OPTION_BOT_WAIT_ACTIVATION = 'partner24_wait_activation';
	public const OPTION_BOT_SUPPORT_CODE = 'partner24_support_code';
	public const OPTION_BOT_SUPPORT_NAME = 'partner24_support_name';
	public const OPTION_BOT_REGULAR_SUPPORT = 'partner24_regular_support';

	public const REGULAR_SUPPORT_NONE = 'PARTNER24_REGULAR_NO';
	public const REGULAR_SUPPORT_INTEGRATOR = 'PARTNER24_REGULAR_INTEGRATOR';

	/**
	 * @return string
	 */
	public static function getSupportLevel()
	{
		return Support24::getSupportLevel();
	}

	/**
	 * @return string
	 */
	public static function getLicenceLanguage()
	{
		return Support24::getLicenceLanguage();
	}

	/**
	 * @return string
	 */
	public static function getPartnerName()
	{
		return Main\Config\Option::get('imbot', self::OPTION_BOT_SUPPORT_NAME, "");
	}

	/**
	 * @return string
	 */
	public static function getBotCode()
	{
		return Main\Config\Option::get('imbot', self::OPTION_BOT_SUPPORT_CODE, "");
	}

	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 * @param string $params['CODE']
	 * @param string $params['NAME']
	 *
	 * @return bool|int
	 */
	public static function register(array $params = Array())
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!Support24::getBotId())
		{
			Support24::register();
		}

		$supportCode = !empty($params['CODE'])? $params['CODE']: self::getBotCode();

		if (self::getBotId() > 0)
		{
			$botId = parent::getNetworkBotId($supportCode, true);
			if ($botId)
			{
				return $botId;
			}

			//todo: use change method instead
			return false;
		}

		$search = parent::search($supportCode, true);
		if (!$search)
		{
			return false;
		}

		$botId = parent::register($search[0]);
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

		Main\Config\Option::set('imbot', self::OPTION_BOT_ID, $botId);
		Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, $supportName);

		self::updateBotProperties();

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

		global $USER;
		self::deactivate($USER->GetID());

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
					Main\Config\Option::delete('imbot', ['name' => parent::BOT_CODE.'_'.$code.'_bot_id']);
				}
			}
		}

		if ($result === false && $botId > 0)
		{
			$result = \Bitrix\Im\Bot::unRegister(['BOT_ID' => $botId]);
		}

		if ($result)
		{
			Main\Config\Option::set('imbot', self::OPTION_BOT_ID, 0);
			//Main\Config\Option::set('imbot', "network_".$code."_bot_id", 0);

			Main\Config\Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
			Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

			Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_CODE, '');
			Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, '');
		}

		return $result;
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
		return Main\Config\Option::get('imbot', self::OPTION_BOT_ID, 0);
	}

	/**
	 * @return string
	 */
	public static function getBotName()
	{
		return Main\Config\Option::get('imbot', self::OPTION_BOT_NAME, '');
	}

	/**
	 * @return string
	 */
	public static function getBotDesc()
	{
		return Main\Config\Option::get('imbot', self::OPTION_BOT_DESC, '');
	}

	/**
	 * @return string
	 */
	public static function getBotAvatar()
	{
		return Main\Config\Option::get('imbot', self::OPTION_BOT_AVATAR, '');
	}

	/**
	 * @return bool
	 */
	public static function isActiveSupport()
	{
		return (bool)Main\Config\Option::get('imbot', self::OPTION_BOT_ACTIVE, false);
	}

	/**
	 * @return bool
	 */
	public static function isWaitingActivation()
	{
		return (bool)Main\Config\Option::get('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);
	}

	/**
	 * Allows everyone writes to OL.
	 *
	 * @return bool
	 */
	public static function isActiveSupportForAll()
	{
		return (bool)Main\Config\Option::get('imbot', self::OPTION_BOT_FOR_ALL, false);
	}

	/**
	 * Checks if integrator has access to partner OL along with regular support active.
	 *
	 * @return bool
	 */
	public static function allowIntegratorAccessAlongSupport24()
	{
		$regulagSupportLevel = Main\Config\Option::get('imbot', self::OPTION_BOT_REGULAR_SUPPORT, self::REGULAR_SUPPORT_NONE);

		return ($regulagSupportLevel === self::REGULAR_SUPPORT_INTEGRATOR);
	}

	/**
	 * Allows certain user write to OL.
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isActiveSupportForUser($userId)
	{
		if (!self::isActiveSupport())
		{
			return false;
		}

		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (self::isActiveSupportForAll())
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

		$users = \CBitrix24BusinessTools::getUnlimUsers();
		if (in_array($userId, $users))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserAdmin($userId)
	{
		return Support24::isUserAdmin($userId);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserIntegrator($userId)
	{
		return Support24::isUserIntegrator($userId);
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
		if (!self::isActiveSupport())
		{
			return new ImBot\Error(__METHOD__, 'PARTNER_DISABLED', 'Partner support disabled on this portal');
		}
		else if (isset($params['LINE']['CODE']) && $params['LINE']['CODE'] !== self::getBotCode())
		{
			return new ImBot\Error(__METHOD__, 'PARTNER_CODE_MISMATCH', 'Partner support code is not correct for this portal');
		}

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

	/**
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
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

	/**
	 * @param array $params
	 *
	 * @return bool
	 */
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

	/**
	 * @param string $currentCode
	 * @param string $previousCode
	 *
	 * @return bool
	 */
	public static function onAfterSupportCodeChange($currentCode = '', $previousCode = '')
	{
		if (!Main\Loader::includeModule('im'))
			return false;

		if (!Main\Loader::includeModule('bitrix24'))
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

		Main\Config\Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		Main\Config\Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		self::sendRequestFinalizeSession([
			'MESSAGE' => Partner24::getMessage('PARTNER_INFO_DEACTIVATE')
		]);

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
				'MESSAGE' => Partner24::getMessage('PARTNER_INFO_DEACTIVATE'),
			),
			true
		);

		return true;
	}

	/**
	 * Finalizes openlines session.
	 *
	 * @param array $params
	 * @param int $params['BOT_ID']
	 * @param string $params['DIALOG_ID']
	 * @param int $params['SESSION_ID']
	 *
	 * @return bool
	 */
	public static function finishDialogSession($params)
	{
		if (self::isActiveSupport())
		{
			if (isset($params['DIALOG_ID']) && preg_match('/^[0-9]+$/i', $params['DIALOG_ID']))
			{
				$userId = (int)$params['DIALOG_ID'];

				if (!self::isUserIntegrator($userId) && self::isActiveSupportForUser($userId))
				{
					// Message with survey of the partner support quality.
					$message = self::getMessage('MESSAGE_QUALITY_SURVEY');

					if (!empty($message))
					{
						self::sendMessage([
							'DIALOG_ID' => $userId,
							'MESSAGE' => $message,
							'SYSTEM' => 'N',
							'URL_PREVIEW' => 'N',
						]);
					}
				}
			}
		}

		return parent::finishDialogSession($params);
	}

	/**
	 * Sends finalize session notification.
	 * @param array $params <pre>
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
		$currentCode = self::getBotCode();

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
				'LOGIN' => 'bot_imbot_partner24',
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
	 * @param string $message
	 * @param int $userId
	 *
	 * @return string
	 */
	public static function replacePlaceholders($message, $userId = 0)
	{
		return Support24::replacePlaceholders($message, $userId);
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public static function getMessage($code)
	{
		$messages = unserialize(
			Main\Config\Option::get('imbot', self::OPTION_BOT_MESSAGES, "a:0:{}"),
			['allowed_classes' => false]
		);
		return isset($messages[$code])? $messages[$code]: '';
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
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
		$dialogs = Main\Application::getInstance()->getConnection()->query($query)->fetchAll();

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
				Im\Model\MessageTable::add(Array(
					'CHAT_ID' => $dialog['CHAT_ID'],
					'AUTHOR_ID' => self::getBotId(),
					'MESSAGE' => self::replacePlaceholders($message, $dialog['USER_ID'])
				));
			}
		}

		return true;
	}

	/**
	 * Activate partner support on portal for certain user.
	 *
	 * @param int $userId
	 * @param string $supportCode
	 * @param string|null $supportName
	 *
	 * @return bool
	 */
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

		Main\Config\Option::set('imbot', self::OPTION_BOT_ACTIVE, true);
		Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

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

		Support24::sendRequestFinalizeSession([
			'MESSAGE' => Partner24::getMessage('SUPPORT_INFO_DEACTIVATE')
		]);

		return true;
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function deactivate(int $userId)
	{
		if(!self::isActiveSupport())
		{
			return true;
		}

		$messageCode = Support24::isActivePaidSupport()? 'DEACTIVATE_PAID': 'DEACTIVATE_FREE';
		self::sendMessageForRecent(self::getMessage($messageCode));

		self::sendRequestFinalizeSession([
			'MESSAGE' => Partner24::getMessage('PARTNER_INFO_DEACTIVATE')
		]);

		Main\Config\Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
		Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		return true;
	}

	/**
	 * @param int $userId
	 * @param string $supportCode
	 * @param string|null $supportName
	 *
	 * @return bool
	 */
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

		Main\Config\Option::set('imbot', self::OPTION_BOT_ACTIVE, true);
		Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		Support24::sendRequestFinalizeSession([
			'MESSAGE' => Partner24::getMessage('SUPPORT_INFO_DEACTIVATE')
		]);

		return true;
	}

	/**
	 * @param string $supportCode
	 * @param string|null $supportName
	 *
	 * @return bool
	 */
	public static function setOptions(string $supportCode, string $supportName = null)
	{
		if (!$supportCode)
		{
			return false;
		}

		$supportCode = trim($supportCode);
		if ($supportName)
		{
			$supportName = trim($supportName);
		}

		Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_CODE, $supportCode);
		Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, $supportName? $supportName: '');

		return true;
	}

	/**
	 * @param string $supportCode
	 * @param string|null $supportName
	 *
	 * @return bool
	 */
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

		Support24::execScheduleAction('ADMIN', 'partner_join');
		Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, true);

		return true;
	}

	/**
	 * Activate partner support on portal.
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function acceptRequest(int $userId)
	{
		$supportCode = self::getBotCode();
		$supportName = self::getPartnerName();

		return self::activate($userId, $supportCode, $supportName);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
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

		Main\Config\Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, '');
		Main\Config\Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
		Main\Config\Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		return true;
	}
}