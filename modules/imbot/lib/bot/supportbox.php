<?php declare(strict_types=1);

namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;

class SupportBox extends Network implements NetworkBot
{
	public const
		BOT_CODE = 'support',

		OPTION_BOT_ID = 'support_bot_id',
		OPTION_BOT_ACTIVE = 'support_enabled',
		OPTION_BOT_CODE = 'support_code',
		OPTION_BOT_NAME = 'support_name',
		OPTION_BOT_DESC = 'support_desc',
		OPTION_BOT_AVATAR = 'support_avatar',
		OPTION_BOT_MESSAGES = 'support_messages',

		COMMAND_ACTIVATE = 'activate';

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
		if (!Main\Loader::includeModule('im'))
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
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see ImBot\Bot\SupportBox::onChatStart */
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see ImBot\Bot\SupportBox::onMessageAdd */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see ImBot\Bot\SupportBox::onBotDelete */
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
			Im\Command::clearCache();

			Im\Command::register([
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => self::COMMAND_ACTIVATE,/** @see ImBot\Bot\SupportBox::activate */
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onCommandAdd'/** @see ImBot\Bot\SupportBox::onCommandAdd */
			]);
		}

		if ($botId)
		{
			// set recent contact for admins
			self::setAsRecent();
		}

		// must be activate before use
		if ($showActivateMessage)
		{
			self::setActive(false);

			$keyboard = new Im\Bot\Keyboard(self::getBotId());
			self::appendActivateButton($keyboard);

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
		if (!Main\Loader::includeModule('im'))
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

		return $result;
	}

	//region Chart bot interface

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
	 * Event handler on message add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields): bool
	{
		if (!self::getCurrentUser()->isAdmin())
		{
			return true;
		}

		return parent::onMessageAdd($messageId, $messageFields);
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
			Log::write($params, 'NETWORK: operatorChangeLine');

			if (self::updateBotProperties())
			{
				//notify
				self::notifyAdministrators(self::getMessage('CHANGE_CODE', Loc::getMessage('SUPPORT_BOX_CHANGE_LINE_USER')));
			}

			return ['RESULT' => 'OK'];
		}

		return parent::onReceiveCommand($command, $params);
	}

	//endregion

	//region Commands

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

		if ($messageFields['COMMAND'] == self::COMMAND_ACTIVATE)
		{
			Im\Bot::startWriting(['BOT_ID' => self::getBotId()], $messageFields['DIALOG_ID']);

			if (self::activate())
			{
				parent::sendMessage([
					'DIALOG_ID' => self::USER_LEVEL_ADMIN,
					'MESSAGE' => self::getMessage('ACTIVATION_SUCCESS', Loc::getMessage('SUPPORT_BOX_ACTIVATION_SUCCESS')),
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);

				foreach (self::getRecentDialogs() as $dialog)
				{
					if ($dialog['MESSAGE_ID'] > 0)
					{
						\CIMMessageParam::Set($dialog['MESSAGE_ID'], [self::MESSAGE_PARAM_KEYBOARD => 'N']);
						\CIMMessageParam::SendPull($dialog['MESSAGE_ID'], [self::MESSAGE_PARAM_KEYBOARD]);
					}
				}
			}
			else
			{
				$error = self::getError();

				$helpDeskUrl = '';
				if (Main\Loader::includeModule('ui'))
				{
					$helpDeskUrl = \Bitrix\UI\Util::getArticleUrlByCode(self::HELP_DESK_CODE);
				}

				$message = Loc::getMessage('SUPPORT_BOX_ACTIVATION_ERROR', [
					'#ERROR#' => $error->msg,
					'#HELP_DESK#' => $helpDeskUrl,
				]);
				$keyboard = new Im\Bot\Keyboard(self::getBotId());
				self::appendActivateButton($keyboard);

				parent::sendMessage([
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $message,
					'KEYBOARD' => $keyboard,
					'URL_PREVIEW' => 'N',
					'SYSTEM' => 'N',
				]);

				\CIMMessageParam::Set($messageId, [self::MESSAGE_PARAM_KEYBOARD => 'N']);
				\CIMMessageParam::SendPull($messageId, [self::MESSAGE_PARAM_KEYBOARD]);
			}

			return true;
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
		self::addAgent();

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
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see ImBot\Bot\SupportBox::onChatStart */
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see ImBot\Bot\SupportBox::onMessageAdd */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see ImBot\Bot\SupportBox::onBotDelete */
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
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * ]
	 * </pre>
	 *
	 * @return array|null
	 */
	public static function getBotSettings(array $params = [])
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
	 * Allows to update bot fields (name, desc, avatar, welcome mess) using data from imcomming message.
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return false;
	}

	/**
	 * Allows to update bot's avatar using data from imcomming message.
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage()
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
			(self::getBotId() > 0) &&
			((bool)Option::get(self::MODULE_ID, self::OPTION_BOT_ACTIVE, false) === true);
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
			\CIMMessage::GetChatId($userId, $botId);

			\CAllIMContactList::SetRecent([
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
	public static function getBotCode()
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
		Option::set(self::MODULE_ID, self::OPTION_BOT_ID, $botId);

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
	 * Returns phrase bi it the code.
	 *
	 * @param string $code
	 * @param string $defaultPhrase
	 *
	 * @return string
	 */
	public static function getMessage(string $code, string $defaultPhrase = ''): string
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

	//region Keyboard & buttons

	private static function appendActivateButton(Im\Bot\Keyboard &$keyboard): void
	{
		$keyboard->addButton([
			"DISPLAY" => "LINE",
			"TEXT" =>  Loc::getMessage('SUPPORT_BOX_ACTIVATE'),
			"BG_COLOR" => "#29619b",
			"TEXT_COLOR" => "#fff",
			"BLOCK" => "Y",
			"COMMAND" => self::COMMAND_ACTIVATE,
		]);
	}

	//endregion

	//region Agent

	/**
	 * Refresh settings agent.
	 *
	 * @param int $retryCount
	 *
	 * @return string
	 */
	public static function refreshAgent(int $retryCount = 0): string
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

			$prevBotAvatar = self::getBotAvatar();

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

			if ($prevBotAvatar != self::getBotAvatar())
			{
				$botAvatar = Im\User::uploadAvatar(self::getBotAvatar(), $botId);
				if (!empty($botAvatar))
				{
					$botParams['PROPERTIES']['PERSONAL_PHOTO'] = $botAvatar;
				}
			}

			Im\Bot::clearCache();
			Im\Bot::update(['BOT_ID' => $botId], $botParams);

			//notify
			/*
			if ($prevBotCode !== $botCode)
			{
				self::sendNotifyChangeLicence([
					'MESSAGE' => Loc::getMessage('SUPPORT_BOX_CHANGE_LINE'),
					'PREVIOUS_BOT_CODE' => $prevBotCode,
					'CURRENT_BOT_CODE' => $botCode,
				]);

				self::notifyAdministrators(self::getMessage('CHANGE_CODE', Loc::getMessage('SUPPORT_BOX_CHANGE_LINE_USER')));
			}
			*/
		}
		while (false);

		$error = self::getError();
		if ($error->error === true && $error->msg !== '')
		{
			$helpDeskUrl = '';
			if (Main\Loader::includeModule('ui'))
			{
				$helpDeskUrl = \Bitrix\UI\Util::getArticleUrlByCode(self::HELP_DESK_CODE);
			}

			$message = Loc::getMessage('SUPPORT_BOX_REFRESH_ERROR', [
				'#ERROR#' => $error->msg,
				'#HELP_DESK#' => $helpDeskUrl,
			]);

			if (Main\Loader::includeModule('im'))
			{
				\CIMNotify::DeleteBySubTag("IMBOT|SUPPORT|ERR");

				$notifyUsers = self::getAdministrators();
				foreach ($notifyUsers as $userId)
				{
					\CIMNotify::Add([
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

			$retryCount ++;
		}
		else
		{
			if (Main\Loader::includeModule('im'))
			{
				\CIMNotify::DeleteBySubTag("IMBOT|SUPPORT|ERR");
			}

			$retryCount = '';
		}

		return __METHOD__. "({$retryCount});";
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
		$notifyUsers = array_intersect($notifyUsers, $recentUsers);
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

	/**
	 * Adds agent.
	 *
	 * @param array $params
	 *
	 * @return boolean
	 */
	public static function addAgent(array $params = []): bool
	{
		$nextExecutionTime = '';
		if (!empty($params['delay']) && (int)$params['delay'] > 0)
		{
			$nextExecutionTime = \ConvertTimeStamp(time()+\CTimeZone::GetOffset() + (int)$params['delay'], "FULL");
		}

		$agentAdded = true;
		$agents = \CAgent::GetList([], ['NAME' => __CLASS__.'::refreshAgent%']);
		if (!$agents->Fetch())
		{
			$agentAdded = (bool)(\CAgent::AddAgent(
					__CLASS__.'::refreshAgent();',
					'imbot',
					'Y',
					86400,
					'',
					'Y',
					$nextExecutionTime
				) !== false);
		}

		return $agentAdded;
	}

	//endregion
}
