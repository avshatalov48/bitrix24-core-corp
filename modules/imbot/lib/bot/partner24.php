<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Im;
use Bitrix\ImBot;

class Partner24 extends Network implements NetworkBot
{
	public const
		BOT_CODE = 'partner24',

		OPTION_BOT_ID = 'partner24_bot_id',
		OPTION_BOT_NAME = 'partner24_name',
		OPTION_BOT_DESC = 'partner24_desc',
		OPTION_BOT_AVATAR = 'partner24_avatar',
		OPTION_BOT_MESSAGES = 'partner24_messages',
		OPTION_BOT_FOR_ALL = 'partner24_for_all',
		OPTION_BOT_ACTIVE = 'partner24_active',
		OPTION_BOT_WAIT_ACTIVATION = 'partner24_wait_activation',
		OPTION_BOT_SUPPORT_CODE = 'partner24_support_code',
		OPTION_BOT_SUPPORT_NAME = 'partner24_support_name',
		OPTION_BOT_REGULAR_SUPPORT = 'partner24_regular_support',

		REGULAR_SUPPORT_NONE = 'PARTNER24_REGULAR_NO',
		REGULAR_SUPPORT_INTEGRATOR = 'PARTNER24_REGULAR_INTEGRATOR';

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
		return Option::get('imbot', self::OPTION_BOT_SUPPORT_NAME, '');
	}

	/**
	 * @return string
	 */
	public static function getBotCode()
	{
		return Option::get('imbot', self::OPTION_BOT_SUPPORT_CODE, '');
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
	public static function register(array $params = [])
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

		Option::set('imbot', self::OPTION_BOT_ID, $botId);
		Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, $supportName);

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

		self::deactivate(self::getCurrentUser()->getId());

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
					Option::delete('imbot', ['name' => parent::BOT_CODE.'_'.$code.'_bot_id']);
				}
			}
		}

		if ($result === false && $botId > 0)
		{
			$result = Im\Bot::unRegister(['BOT_ID' => $botId]);
		}

		if ($result)
		{
			Option::set('imbot', self::OPTION_BOT_ID, 0);

			Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
			Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

			Option::set('imbot', self::OPTION_BOT_SUPPORT_CODE, '');
			Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, '');
		}

		return $result;
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
		return Option::get('imbot', self::OPTION_BOT_NAME, '');
	}

	/**
	 * @return string
	 */
	public static function getBotDesc()
	{
		return Option::get('imbot', self::OPTION_BOT_DESC, '');
	}

	/**
	 * @return string
	 */
	public static function getBotAvatar()
	{
		return Option::get('imbot', self::OPTION_BOT_AVATAR, '');
	}

	/**
	 * @return bool
	 */
	public static function isActiveSupport()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_ACTIVE, false);
	}

	/**
	 * @return bool
	 */
	public static function isWaitingActivation()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);
	}

	/**
	 * Allows everyone writes to OL.
	 *
	 * @return bool
	 */
	public static function isActiveSupportForAll()
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_FOR_ALL, false);
	}

	/**
	 * Checks if integrator has access to partner OL along with regular support active.
	 *
	 * @return bool
	 */
	public static function allowIntegratorAccessAlongSupport24()
	{
		$regulagSupportLevel = Option::get('imbot', self::OPTION_BOT_REGULAR_SUPPORT, self::REGULAR_SUPPORT_NONE);

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

		self::sendMessage([
			'DIALOG_ID' => $messageFields['USER_ID'],
			'MESSAGE' => $message,
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		]);

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
		{
			return false;
		}

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
			self::sendMessage([
				'DIALOG_ID' => $messageFields['FROM_USER_ID'],
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

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

		Option::set('imbot', "network_".$previousCode."_bot_id", 0);
		Option::set('imbot', "network_".$currentCode."_bot_id", self::getBotId());

		self::sendRequestFinalizeSession([
			'MESSAGE' => self::getMessage('PARTNER_INFO_DEACTIVATE')
		]);

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
				'MESSAGE' => self::getMessage('PARTNER_INFO_DEACTIVATE'),
			],
			true
		);

		return true;
	}

	//endregion

	/**
	 * @inheritDoc
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
			'METHOD_MESSAGE_ADD' => 'onMessageAdd', /** @see Partner24::onMessageAdd */
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',  /** @see Partner24::onChatStart */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see Partner24::onBotDelete */
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

		Im\Bot::update(['BOT_ID' => self::getBotId()], $botParams);

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
		static $messages;
		if ($messages === null)
		{
			$messages = unserialize(
				Option::get('imbot', self::OPTION_BOT_MESSAGES, 'a:0:{}'),
				['allowed_classes' => false]
			);
		}

		return isset($messages[$code]) ? $messages[$code] : '';
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	private static function sendMessageForRecent($message)
	{
		foreach (self::getRecentDialogs() as $dialog)
		{
			if ($dialog['USER_ID'] == self::getBotId())
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

		Option::set('imbot', self::OPTION_BOT_ACTIVE, true);
		Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		self::sendMessage([
			'DIALOG_ID' => self::USER_LEVEL_ADMIN,
			'MESSAGE' => Support24::getMessage('PARTNER_REQUEST_ACTIVATED'),
			'SYSTEM' => 'N',
			'URL_PREVIEW' => 'N'
		]);

		self::clientMessageSend([
			'BOT_ID' => self::getBotId(),
			'USER_ID' => $userId,
			'ATTACH' => [['MESSAGE' => self::getMessage('PARTNER_INFO_ACTIVATE')]],
		]);

		Support24::sendRequestFinalizeSession([
			'MESSAGE' => self::getMessage('SUPPORT_INFO_DEACTIVATE')
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
			'MESSAGE' => self::getMessage('PARTNER_INFO_DEACTIVATE')
		]);

		Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
		Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

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

		Option::set('imbot', self::OPTION_BOT_ACTIVE, true);
		Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		Support24::sendRequestFinalizeSession([
			'MESSAGE' => self::getMessage('SUPPORT_INFO_DEACTIVATE')
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

		Option::set('imbot', self::OPTION_BOT_SUPPORT_CODE, $supportCode);
		Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, $supportName ? $supportName : '');

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

		Support24::execScheduleAction(self::USER_LEVEL_ADMIN, Support24::SCHEDULE_ACTION_PARTNER_JOIN);
		Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, true);

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

		Option::set('imbot', self::OPTION_BOT_SUPPORT_NAME, '');
		Option::set('imbot', self::OPTION_BOT_ACTIVE, false);
		Option::set('imbot', self::OPTION_BOT_WAIT_ACTIVATION, false);

		return true;
	}
}