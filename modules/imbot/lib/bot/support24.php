<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\ImBot\DialogSession;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;
use Bitrix\ImBot\ItrMenu;
use Bitrix\Imbot\Bot\Mixin;
use Bitrix\Imopenlines\MessageParameter;

class Support24 extends Network implements MenuBot, SupportBot, SupportQuestion
{
	use Mixin\NetworkMenuBot;
	use Mixin\SupportQuestion;
	use Mixin\SupportQueueNumber;

	public const
		BOT_CODE = 'support24',

		CHAT_ENTITY_TYPE = 'SUPPORT24_QUESTION',// specialized support chats

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
		HIDE_DIALOG_TIME = 5 // minutes
	;

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
		OPTION_BOT_PAID_MESSAGES = 'support24_paid_messages'
	;

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
		Option::set(self::MODULE_ID, self::OPTION_BOT_SUPPORT_LEVEL, self::getSupportLevel());

		self::updateBotProperties();

		$eventManager = Main\EventManager::getInstance();
		foreach (self::getEventHandlerList() as $handler)
		{
			$eventManager->registerEventHandlerCompatible(
				$handler['module'],
				$handler['event'],
				self::MODULE_ID,
				__CLASS__,
				$handler['handler']
			);
		}

		self::scheduleAction(1, self::SCHEDULE_ACTION_WELCOME, '', 10);

		self::restoreQuestionHistory();

		self::addAgent([
			'agent' => 'refreshAgent()',/** @see Support24::refreshAgent */
			'class' => __CLASS__,
			'delay' => random_int(30, 360),
		]);

		return $botId;
	}

	/**
	 * Unregisters bot at portal.
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
			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);

			Option::delete(self::MODULE_ID, ['name' => self::OPTION_BOT_ID]);

			$eventManager = Main\EventManager::getInstance();
			foreach (self::getEventHandlerList() as $handler)
			{
				$eventManager->unregisterEventHandler(
					$handler['module'],
					$handler['event'],
					self::MODULE_ID,
					__CLASS__,
					$handler['handler']
				);
			}

			self::deleteAgent([
				'mask' => 'refreshAgent',/** @see Support24::refreshAgent */
			]);
		}

		return $result;
	}

	/**
	 * Agent for deferred bot registration.
	 * @return string
	 */
	public static function delayRegister(): string
	{
		if (self::register())
		{
			Option::delete('imbot', ['name' => 'support24_bot_register_in_progress']);

			return '';
		}

		return __METHOD__ . '();';
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
		if (Loader::includeModule('bitrix24'))
		{
			$botId = self::getBotId();
			$settings = self::getBotSettings([
				'BOT_ID' => $botId
			]);
			if ($settings)
			{
				$prevSupportLevel = Option::get(self::MODULE_ID, self::OPTION_BOT_SUPPORT_LEVEL, self::SUPPORT_LEVEL_FREE);
				$prevPaidCode = Option::get(self::MODULE_ID, self::OPTION_BOT_PAID_CODE, '');
				$prevFreeCode = Option::get(self::MODULE_ID, self::OPTION_BOT_FREE_CODE, '');
				$previousCode = self::getBotCode();

				//$prevRegion = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_PREVIOUS);
				//$currentRegion = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_CURRENT);
				//$isRegionChanged = $prevRegion != $currentRegion;

				$prevDemoState = Option::get(self::MODULE_ID, self::OPTION_BOT_DEMO_ACTIVE, false);
				$currentDemoState = \CBitrix24::isDemoLicense();
				$isDemoLevelChanged = $prevDemoState != $currentDemoState;

				if (self::saveSettings($settings))
				{
					self::registerCommands();
					self::registerApps();

					Option::set(self::MODULE_ID, self::OPTION_BOT_DEMO_ACTIVE, $currentDemoState);
					if ($currentDemoState)
					{
						Option::set(self::MODULE_ID, self::OPTION_BOT_FREE_START_DATE, \time());
					}

					// support level
					$currentSupportLevel = self::getSupportLevel();
					$isSupportLevelChanged = $prevSupportLevel != $currentSupportLevel;
					if ($isSupportLevelChanged)
					{
						Option::set(self::MODULE_ID, self::OPTION_BOT_SUPPORT_LEVEL, $currentSupportLevel);
					}

					$isPreviousSupportLevelPartner = $prevSupportLevel === self::SUPPORT_LEVEL_PARTNER;

					// line code change
					$currentCode = self::getBotCode();
					$isLineCodeChanged = false;

					if ($isSupportLevelChanged)
					{
						if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
						{
							$previousCode = $prevFreeCode;
							$currentCode = Option::get(self::MODULE_ID, self::OPTION_BOT_PAID_CODE, '');
						}
						elseif (self::getSupportLevel() == self::SUPPORT_LEVEL_FREE)
						{
							$previousCode = $prevPaidCode;
							$currentCode = Option::get(self::MODULE_ID, self::OPTION_BOT_FREE_CODE, '');
						}
						if ($isPreviousSupportLevelPartner)
						{
							$previousCode = Option::get('bitrix24', 'partner_ol', '');
						}
					}
					else
					{
						if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
						{
							$previousCode = $prevPaidCode;
							$currentCode = Option::get(self::MODULE_ID, self::OPTION_BOT_PAID_CODE, '');

							$isLineCodeChanged = (
								!empty($prevPaidCode)
								&& !empty($currentCode)
								&& $prevPaidCode != $currentCode
							);
						}
						else
						{
							$previousCode = $prevFreeCode;
							$currentCode = Option::get(self::MODULE_ID, self::OPTION_BOT_FREE_CODE, '');

							$isLineCodeChanged = (
								!empty($prevFreeCode)
								&& !empty($currentCode)
								&& $prevFreeCode != $currentCode
							);
						}
					}

					if ($isSupportLevelChanged || $isLineCodeChanged)
					{
						(new DialogSession)->clearSessions(['BOT_ID' => self::getBotId()]);

						self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);
					}

					if (
						$isSupportLevelChanged
						|| $isLineCodeChanged
						|| $isDemoLevelChanged
						//|| $isRegionChanged
					)
					{
						self::onSupportLevelChange([
							'IS_SUPPORT_LEVEL_CHANGE' => $isSupportLevelChanged,
							'IS_DEMO_LEVEL_CHANGE' => $isDemoLevelChanged,
							//'IS_REGION_CHANGED' => $isRegionChanged,
							'IS_SUPPORT_CODE_CHANGE' => $isLineCodeChanged,
							'PREVIOUS_SUPPORT_LEVEL' => $prevSupportLevel,
							'PREVIOUS_BOT_CODE' => $previousCode,
							'CURRENT_BOT_CODE' => $currentCode,
						]);
					}
				}
			}
		}

		return $regular ? __METHOD__. '();' : '';
	}

	/**
	 * Returns event handler list.
	 * @return array{module: string, event: string, class: string, handler: string}[]
	 */
	public static function getEventHandlerList(): array
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnAfterSetOption_~controller_group_name',
				'handler' => 'onAfterLicenseChange', /** @see Support24::onAfterLicenseChange */
			],
			[
				'module' => 'main',
				'event' => 'OnAfterUserAuthorize',
				'handler' => 'onAfterUserAuthorize', /** @see Support24::onAfterUserAuthorize */
			],
		];
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
			self::getQueueNumberCommandList(),
			self::getMenuCommandList(),
			[
				self::COMMAND_NETWORK_SESSION => [
					'command' => self::COMMAND_NETWORK_SESSION,
					'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
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
						[
							'COMMAND_CONTEXT' => 'KEYBOARD',
							'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
							'CHAT_ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
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
		if (self::getBotId() > 0)
		{
			return self::getSupportLevel();
		}

		return self::SUPPORT_LEVEL_NONE;
	}

	/**
	 * Detects client's support level.
	 * @return string
	 */
	public static function getSupportLevel(): string
	{
		if (Loader::includeModule('bitrix24'))
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
	public static function getLicenceLanguage(): string
	{
		$lang = 'en';
		if (Loader::includeModule('bitrix24'))
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
	 * Returns OL code.
	 * @return string
	 */
	public static function getBotCode(): string
	{
		if (Loader::includeModule('bitrix24'))
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
	 * @return int
	 */
	public static function getBotId(): int
	{
		return (int)Option::get('imbot', self::OPTION_BOT_ID, 0);
	}

	/**
	 * @return string
	 */
	public static function getBotName(): string
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_NAME : self::OPTION_BOT_PAID_NAME;
		return Option::get('imbot', $optionName, '');
	}

	/**
	 * @return string
	 */
	public static function getBotDesc(): string
	{
		$optionName = self::getSupportLevel() == self::SUPPORT_LEVEL_FREE ?
			self::OPTION_BOT_FREE_DESC : self::OPTION_BOT_PAID_DESC;
		return Option::get('imbot', $optionName, '');
	}

	/**
	 * @return string
	 */
	public static function getBotAvatar(): string
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
	public static function hasBotMenu(): bool
	{
		return (self::getBotMenu() instanceof ItrMenu);
	}

	/**
	 * Returns stored data for ITR menu.
	 *
	 * @return ItrMenu|null
	 */
	public static function getBotMenu(): ?ItrMenu
	{
		static $hasMenu;
		if ($hasMenu === null)
		{
			$hasMenu = false;

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

			$json = Option::get(self::MODULE_ID, $menuType, '');
			if ($json)
			{
				try
				{
					$structure = Main\Web\Json::decode($json);
					self::instanceMenu()->setStructure($structure);
					$hasMenu = true;
				}
				catch (Main\ArgumentException $e)
				{
				}
			}
		}

		return $hasMenu ? self::instanceMenu() : null;
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
				else
				{
					$params['PORTAL_TARIFF'] = \CBitrix24::getLicenseType();
				}
			}

			$settings = parent::getBotSettings($params);
			if (empty($settings))
			{
				return null;
			}

			$result = [];
			$props = [
				self::OPTION_BOT_FREE_CODE,
				self::OPTION_BOT_FREE_NAME,
				self::OPTION_BOT_FREE_DESC,
				self::OPTION_BOT_FREE_DAYS,
				self::OPTION_BOT_FREE_AVATAR,
				self::OPTION_BOT_FREE_FOR_ALL,
				self::OPTION_BOT_FREE_MESSAGES,
				self::OPTION_BOT_PAID_CODE,
				self::OPTION_BOT_PAID_NAME,
				self::OPTION_BOT_PAID_DESC,
				self::OPTION_BOT_PAID_AVATAR,
				self::OPTION_BOT_PAID_FOR_ALL,
				self::OPTION_BOT_PAID_MESSAGES,
				self::OPTION_BOT_FREE_MENU,
				self::OPTION_BOT_PAID_MENU,
				self::OPTION_BOT_FREE_MENU_STAGE,
				self::OPTION_BOT_PAID_MENU_STAGE,
				self::OPTION_BOT_PAID_ACTIVE,
				Mixin\OPTION_BOT_QUESTION_LIMIT,
			];
			foreach ($props as $prop)
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
		if (
			!empty($settings[self::OPTION_BOT_FREE_CODE])
			&& !empty($settings[self::OPTION_BOT_PAID_CODE])
		)
		{
			$updateBotProperties = false;
			$botCodes = [];
			foreach ($settings as $optionName => $optionValue)
			{
				if ($optionName == Mixin\OPTION_BOT_QUESTION_LIMIT)
				{
					Option::set(self::MODULE_ID, $optionName, $optionValue ?? -1);
					$updateBotProperties = true;
				}
				elseif ($optionName == self::OPTION_BOT_PAID_ACTIVE)
				{
					// set support level - paid
					$optionValue = (int)$optionValue;
					$prevPaidActive = (int)Option::get(self::MODULE_ID, $optionName, -1);
					if ($prevPaidActive != $optionValue)
					{
						Option::set(self::MODULE_ID, $optionName, $optionValue);
						$updateBotProperties = true;
					}
				}
				elseif (Option::get(self::MODULE_ID, $optionName, '') != $optionValue)
				{
					if ($optionName == self::OPTION_BOT_FREE_CODE)
					{
						$prevFreeCode = Option::get(self::MODULE_ID, $optionName, '');
						$botCodes[] = $optionValue;
						$botCodes[] = $prevFreeCode;
						$updateBotProperties = true;
					}
					elseif ($optionName == self::OPTION_BOT_PAID_CODE)
					{
						$prevPaidCode = Option::get(self::MODULE_ID, $optionName, '');
						$botCodes[] = $optionValue;
						$botCodes[] = $prevPaidCode;
						$updateBotProperties = true;
					}
					elseif (
						in_array($optionName, [
							self::OPTION_BOT_FREE_NAME,
							self::OPTION_BOT_FREE_DESC,
							self::OPTION_BOT_FREE_AVATAR,
							self::OPTION_BOT_PAID_NAME,
							self::OPTION_BOT_PAID_DESC,
							self::OPTION_BOT_PAID_AVATAR,
						])
					)
					{
						$updateBotProperties = true;
					}

					Option::set(self::MODULE_ID, $optionName, $optionValue);
				}
			}

			// set start date
			$dateRegister = Option::get(self::MODULE_ID, self::OPTION_BOT_FREE_START_DATE, 0);
			if (!$dateRegister)
			{
				// check previous version of bot
				$res = \Bitrix\Im\Model\BotTable::getList([
					'select' => ['DATE_REGISTER' => 'USER.DATE_REGISTER'],
					'runtime' => [
						'USER' => [
							'data_type' => 'Bitrix\Main\UserTable',
							'reference' => ['=this.BOT_ID' => 'ref.ID'],
							'join_type' => 'INNER',
						],
					],
					'filter' => [
						'=APP_ID' => array_filter($botCodes),
					],
					'order' => [
						'USER.DATE_REGISTER' => 'ASC'
					]
				]);
				if ($row = $res->fetch())
				{
					$dateRegister = $row['DATE_REGISTER']->getTimestamp();
				}
				else
				{
					$dateRegister = time();
				}
				Option::set(self::MODULE_ID, self::OPTION_BOT_FREE_START_DATE, $dateRegister);
			}

			// update im bot props
			if ($updateBotProperties)
			{
				self::updateBotProperties();
			}
		}

		return true;
	}

	/**
	 * @return int
	 */
	public static function getFreeSupportLifeTime(): int
	{
		return (int)Option::get('imbot', self::OPTION_BOT_FREE_DAYS, 16);
	}

	/**
	 * @return bool
	 */
	public static function isFreeSupportLifeTimeExpired(): bool
	{
		$generationDate = (int)Option::get('imbot', self::OPTION_BOT_FREE_START_DATE, 0);
		if ($generationDate == 0)
		{
			Option::set('imbot', self::OPTION_BOT_FREE_START_DATE, \time());
			return true;
		}

		$isActive = \time() - $generationDate < 86400 * self::getFreeSupportLifeTime();

		return !$isActive;
	}

	/**
	 * @return bool
	 */
	public static function isActiveFreeSupport(): bool
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
	public static function isActiveFreeSupportForAll(): bool
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_FREE_FOR_ALL, false);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isActiveFreeSupportForUser($userId): bool
	{
		if (!self::getBotId())
		{
			return false;
		}

		if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
		{
			return false;
		}

		if (!Loader::includeModule('bitrix24'))
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
	public static function isActivePartnerSupport(): bool
	{
		return Partner24::isEnabled() && Partner24::isActiveSupport();
	}

	/**
	 * @return bool
	 */
	public static function isActivePaidSupport(): bool
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_PAID_ACTIVE, false);
	}

	/**
	 * @return bool
	 */
	public static function isActivePaidSupportForAll(): bool
	{
		return (bool)Option::get('imbot', self::OPTION_BOT_PAID_FOR_ALL, false);
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isActivePaidSupportForUser($userId): bool
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
		if ($command === Mixin\COMMAND_OPERATOR_QUEUE_NUMBER)
		{
			Log::write($params, "NETWORK: $command");

			self::operatorQueueNumber([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'] ?? null,
				'SESSION_ID' => $params['SESSION_ID'] ?? null,
				'QUEUE_NUMBER' => $params['QUEUE_NUMBER'] ?? null,
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
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		$messageFields = $joinFields;
		$messageFields['DIALOG_ID'] = $dialogId;

		if (!self::checkMembershipRestriction($messageFields))
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage([
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $groupLimited,
					'URL_PREVIEW' => 'N'
				]);
			}

			(new \CIMChat(self::getBotId()))->deleteUser(mb_substr($dialogId, 4), self::getBotId());

			return true;
		}

		// specialized support chats
		if (
			$messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_CHAT
			&& $messageFields['CHAT_ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE
			&& self::hasBotMenu()
		)
		{
			if ($joinFields['ACCESS_HISTORY'] ?? true) // suppress menu showing on calling restoreQuestionHistory()
			{
				self::showMenu(['DIALOG_ID' => $dialogId]);
			}
			return true;
		}

		// welcome message
		$message = '';
		$isPositiveWelcome = false;
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
				$isPositiveWelcome = true;
			}
			else if (self::isActiveFreeSupport())
			{
				if (self::isActiveFreeSupportForUser($messageFields['USER_ID']))
				{
					$message = self::getMessage('WELCOME');
					$isPositiveWelcome = true;
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
				$isPositiveWelcome = true;
			}
			else if (self::isActivePaidSupportForUser($messageFields['USER_ID']))
			{
				$message = self::getMessage('WELCOME');
				$isPositiveWelcome = true;
			}
			else
			{
				$message = self::getMessage('WELCOME_LIMITED');
			}
		}

		if (!empty($message))
		{
			\CUserOptions::setOption(
				self::MODULE_ID,
				self::OPTION_BOT_WELCOME_SHOWN,
				\time(),
				false,
				$messageFields['USER_ID']
			);

			if (!$isPositiveWelcome || $joinFields['CHAT_ENTITY_TYPE'] !== self::CHAT_ENTITY_TYPE)
			{
				self::sendMessage([
					'DIALOG_ID' => $messageFields['USER_ID'],
					'MESSAGE' => $message,
					'URL_PREVIEW' => 'N'
				]);
			}
		}

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
				'BOT_ID' => self::getBotId(),
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'GREETING_SHOWN' => 'Y',
			]);
			self::stopMenuTrack((string)$messageFields['DIALOG_ID']);
		}

		return parent::operatorMessageAdd($messageId, $messageFields);
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
		if (
			!self::isUserAdmin(self::getCurrentUser()->getId())
			&& !self::isUserIntegrator(self::getCurrentUser()->getId())
			&& !self::isActivePaidSupportForAll()
		)
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

		$fromUserId = (int)$messageFields['FROM_USER_ID'];
		$dialogId = (string)$messageFields['FROM_USER_ID'];
		$isChat = (
			isset($messageFields['CHAT_ENTITY_TYPE'])
			&& $messageFields['CHAT_ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE
		);
		if ($isChat)
		{
			$dialogId = 'chat'.(int)$messageFields['CHAT_ID'];
		}

		// check restrictions
		if (!self::checkMembershipRestriction($messageFields))
		{
			$groupLimited = self::getMessage('GROUP_LIMITED');
			if ($groupLimited)
			{
				self::sendMessage([
					'TO_USER_ID' => $fromUserId,
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $groupLimited,
					'SYSTEM' => 'Y',
					'URL_PREVIEW' => 'N'
				]);
			}

			(new \CIMChat(self::getBotId()))->deleteUser($messageFields['CHAT_ID'], self::getBotId());

			return true;
		}

		// specialized support chats
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

		// ITR menu on before any dialog starts
		if ($allowShowMenu)
		{
			if ((int)self::instanceDialogSession(self::getBotId(), $dialogId)->getParam('CLOSED') == 1)
			{
				self::instanceDialogSession(self::getBotId(), $dialogId)->update(['MENU_STATE' => null]);
			}

			if (!self::isMenuTrackFinished((string)$dialogId))
			{
				$lastMenuItemId = self::getBotMenu()->setDialogId($dialogId)->getLastTrackItemId();
				if (!$lastMenuItemId && !empty($warningRestrictionMessage))
				{
					// show restriction warning message first
					self::sendMessage([
						'DIALOG_ID' => $dialogId,
						'MESSAGE' => $warningRestrictionMessage,
						'URL_PREVIEW' => 'N',
					]);
				}

				if ($lastMenuItemId !== ItrMenu::MENU_EXIT_ID)
				{
					self::markMessageUndelivered($messageId);

					$undeliveredMessage = self::getMessage('MESSAGE_UNDELIVERED');
					if ($undeliveredMessage)
					{
						self::sendMessage([
							'DIALOG_ID' => $dialogId,
							'MESSAGE' => $undeliveredMessage,
							'URL_PREVIEW' => 'N',
						]);
					}

					self::showMenu([
						'DIALOG_ID' => $dialogId,
						'FULL_REDRAW' => true,
						'UNDELIVERED_MESSAGE' => $messageId
					]);

					if (!self::isMenuTrackFinished((string)$dialogId))
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
			&& self::allowSendStartMessage(['BOT_ID' => self::getBotId(), 'DIALOG_ID' => $messageFields['DIALOG_ID']])
		)
		{
			self::markMessageUndelivered($messageId);

			// show restriction warning message
			self::sendMessage([
				'DIALOG_ID' => $dialogId,
				'MESSAGE' => $warningRestrictionMessage,
				'URL_PREVIEW' => 'N',
			]);

			return true;
		}
		elseif ($dialogId)
		{
			self::startDialogSession([
				'BOT_ID' => self::getBotId(),
				'DIALOG_ID' => $dialogId,
				'GREETING_SHOWN' => 'Y',
			]);
			self::stopMenuTrack((string)$dialogId);
		}

		// add menu action
		if ($relatedMessages = (new \CIMHistory)->getRelatedMessages($messageId, 1, 0, false, false))
		{
			foreach ($relatedMessages['message'] as $message)
			{
				if (
					$message['system'] != 'Y'
					&& isset($message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE])
					&& $message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE] === 'Y'
				)
				{
					$messageParameters = IM\Model\MessageParamTable::getList([
						'select' => ['PARAM_VALUE'],
						'filter' => [
							'=MESSAGE_ID' => $message['id'],
							'=PARAM_NAME' => Mixin\MESSAGE_PARAM_MENU_ACTION,
						]
					]);
					if (($relatedMessageParam = $messageParameters->fetch()) && !empty($relatedMessageParam['PARAM_VALUE']))
					{
						$messageFields['PARAMS'] = $messageFields['PARAMS'] ?? [];
						$messageFields['PARAMS'][Mixin\MESSAGE_PARAM_MENU_ACTION] = $relatedMessageParam['PARAM_VALUE'];
					}
					break;
				}
			}
		}

		return parent::onMessageAdd($messageId, $messageFields);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageUpdate($messageId, $messageFields)
	{
		if (!self::checkMessageRestriction($messageFields))
		{
			return false;
		}

		if (
			self::hasBotMenu()
			&& !self::isMenuTrackFinished($messageFields['DIALOG_ID'])
		)
		{
			// don't send event of menu redrawing
			return false;
		}

		return self::clientMessageUpdate($messageId, $messageFields);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageDelete($messageId, $messageFields)
	{
		if (!self::checkMessageRestriction($messageFields))
		{
			return false;
		}

		if (
			self::hasBotMenu()
			&& !self::isMenuTrackFinished($messageFields['DIALOG_ID'])
		)
		{
			// don't send event of menu redrawing
			return false;
		}

		return self::clientMessageDelete($messageId, $messageFields);
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

		if (self::isActivePartnerSupport())
		{
			if (!self::isUserIntegrator($params['USER_ID']))
			{
				return false;
			}
		}

		if (self::getSupportLevel() === self::SUPPORT_LEVEL_FREE)
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
		else if (self::getSupportLevel() === self::SUPPORT_LEVEL_PAID)
		{
			if (!self::isActivePaidSupportForUser($params['USER_ID']))
			{
				return false;
			}
		}

		if ($params['BOT_ID'] == $params['DIALOG_ID'])
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}

		$dialogId = (string)$params['DIALOG_ID'];

		if ((int)self::instanceDialogSession(self::getBotId(), $dialogId)->getParam('CLOSED') == 1)
		{
			self::instanceDialogSession(self::getBotId(), $dialogId)->update([
				'MENU_STATE' => null,
				'GREETING_SHOWN' => 'N',
			]);
		}

		// ITR menu on before any dialog starts
		if (self::hasBotMenu())
		{
			if (!self::isMenuTrackStarted($dialogId) && !self::isMenuTrackFinished($dialogId))
			{
				self::showMenu(['DIALOG_ID' => $dialogId]);
				if (!self::isMenuTrackFinished($dialogId))
				{
					return false;//continue menu travel
				}
			}
			elseif (self::isMenuTrackStarted($dialogId) && !self::isMenuTrackFinished($dialogId))
			{
				return false;//do nothing
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
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $message,
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N'
				]);
				self::startDialogSession([
					'BOT_ID' => self::getBotId(),
					'DIALOG_ID' => $dialogId,
					'GREETING_SHOWN' => 'Y',
				]);
				self::stopMenuTrack($dialogId);
			}
		}

		return parent::onStartWriting($params);
	}


	/**
	 * @see \Bitrix\ImBot\Event::onChatRead
	 * @param $params
	 * @return bool
	 */
	public static function onChatRead($params)
	{
		return self::onUserRead($params);
	}

	/**
	 * @see \Bitrix\ImBot\Event::onUserRead
	 * @param $params
	 * @return bool
	 */
	public static function onUserRead($params)
	{
		if ($params['BY_EVENT'] === true)
		{
			return true;
		}

		if (!self::checkTypingRestriction($params))
		{
			return false;
		}

		if ($params['CHAT_ENTITY_TYPE'] == 'USER')
		{
			$dialogId = $params['USER_ID'];
		}
		elseif ($params['CHAT_ENTITY_TYPE'] == self::CHAT_ENTITY_TYPE)
		{
			$dialogId = 'chat'.$params['CHAT_ID'];
		}
		else
		{
			return false;
		}

		$session = self::instanceDialogSession((int)self::getBotId(), $dialogId);

		$sessionFinished = (
			$session->getSessionId() > 0
			&& $session->getParam('CLOSED') == 1
		);

		if ($sessionFinished)// hide closed session only
		{
			self::scheduleAction(
				$dialogId,
				self::SCHEDULE_ACTION_HIDE_DIALOG,
				$session->getSessionId(),
				self::HIDE_DIALOG_TIME
			);
		}

		return true;
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
		if (empty($params['DIALOG_ID']))
		{
			return false;
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

		self::scheduleAction($params['DIALOG_ID'], self::SCHEDULE_ACTION_HIDE_DIALOG, '', self::HIDE_DIALOG_TIME);

		return self::clientSessionVote($params);
	}

	/**
	 * @return bool
	 */
	public static function onAfterLicenseChange()
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		self::addAgent([
			'agent' => 'refreshAgent(false)',/** @see Support24::refreshAgent */
			'class' => __CLASS__,
			'delay' => 15,
			'interval' => 100,
		]);

		/*
		$previousDemoState = Option::get('imbot', self::OPTION_BOT_DEMO_ACTIVE, false);

		$previousSupportLevel = Option::get('imbot', self::OPTION_BOT_SUPPORT_LEVEL, self::SUPPORT_LEVEL_FREE);
		$currentSupportLevel = self::getSupportLevel();

		$isPreviousSupportLevelPartner = $previousSupportLevel === self::SUPPORT_LEVEL_PARTNER;

		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		$previousRegion = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentRegion = \CBitrix24::getPortalZone(\CBitrix24::LICENSE_TYPE_CURRENT);

		$currentDemoState = \CBitrix24::isDemoLicense();
		Option::set('imbot', self::OPTION_BOT_DEMO_ACTIVE, $currentDemoState);

		$isSupportLevelChanged = $previousSupportLevel != $currentSupportLevel;
		$isDemoLevelChanged = $previousDemoState != $currentDemoState;
		$isRegionChanged = $previousRegion != $currentRegion;

		if (!$isSupportLevelChanged && !$isDemoLevelChanged && !$isRegionChanged)
		{
			return true;
		}

		if ($isSupportLevelChanged)
		{
			Option::set('imbot', self::OPTION_BOT_SUPPORT_LEVEL, $currentSupportLevel);
		}

		if (self::getSupportLevel() == self::SUPPORT_LEVEL_PAID)
		{
			$previousCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, '');
			$currentCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, '');
		}
		else
		{
			$previousCode = Option::get('imbot', self::OPTION_BOT_PAID_CODE, '');
			$currentCode = Option::get('imbot', self::OPTION_BOT_FREE_CODE, '');
		}

		if ($isPreviousSupportLevelPartner)
		{
			$previousCode = Option::get("bitrix24", "partner_ol", "");
		}

		if ($isSupportLevelChanged)
		{
			(new DialogSession)->clearSessions(['BOT_ID' => self::getBotId()]);

			self::deleteScheduledAction(self::SCHEDULE_DELETE_ALL);
		}

		if ($currentDemoState)
		{
			Option::set('imbot', self::OPTION_BOT_FREE_START_DATE, \time());
		}

		self::updateBotProperties();

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_LEVEL_CHANGE' => $isSupportLevelChanged,
			'IS_DEMO_LEVEL_CHANGE' => $isDemoLevelChanged,
			'IS_SUPPORT_CODE_CHANGE' => $isRegionChanged,
		]);

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
		*/

		return true;
	}

	/**
	 * @deprected
	 * @param string $previousFreeCode
	 * @param string $previousPaidCode
	 *
	 * @return bool
	 */
	public static function onAfterSupportCodeChange($previousFreeCode = '', $previousPaidCode = '')
	{
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!Loader::includeModule('bitrix24'))
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

		(new DialogSession)->clearSessions(['BOT_ID' => self::getBotId()]);

		self::updateBotProperties();

		self::onSupportLevelChange([
			'IS_SUPPORT_CODE_CHANGE' => true,
			'PREVIOUS_BOT_CODE' => $previousCode,
			'CURRENT_BOT_CODE' => $currentCode,
			'PREVIOUS_SUPPORT_LEVEL' => $previousSupportLevel,
		]);

		/*
		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_CODE_CHANGE' => true,
		]);

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
		*/

		return true;
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	private static function onSupportLevelChange(array $params): bool
	{
		$isSupportLevelChanged = $params['IS_SUPPORT_LEVEL_CHANGE'] ?? false;
		$isDemoLevelChanged = $params['IS_DEMO_LEVEL_CHANGE'] ?? false;
		//$isRegionChanged = $params['IS_REGION_CHANGED'] ?? false;
		$isLineCodeChanged = $params['IS_SUPPORT_CODE_CHANGE'] ?? false;
		$previousCode = $params['PREVIOUS_BOT_CODE'] ?? '';
		$currentCode = $params['CURRENT_BOT_CODE'] ?? '';
		$previousSupportLevel = $params['PREVIOUS_SUPPORT_LEVEL'] ?? '';

		if (
			!$isSupportLevelChanged
			&& !$isDemoLevelChanged
			//&& !$isRegionChanged
			&& !$isLineCodeChanged
		)
		{
			return true;
		}

		self::sendNotifyAboutChangeLevel([
			'BUSINESS_USERS' => self::getBusinessUsers(),
			'IS_SUPPORT_LEVEL_CHANGE' => $isSupportLevelChanged,
			'IS_DEMO_LEVEL_CHANGE' => $isDemoLevelChanged,
			'IS_SUPPORT_CODE_CHANGE' => $isLineCodeChanged //|| $isRegionChanged,
		]);

		$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
		$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);

		$http = self::instanceHttpClient();
		$http->query(
			'clientChangeLicence',
			[
				'BOT_ID' => self::getBotId(),
				'PREVIOUS_LICENCE_TYPE' => !$isLineCodeChanged ? $previousLicence : $currentLicence,
				'PREVIOUS_LICENCE_NAME' => \CBitrix24::getLicenseName(!$isLineCodeChanged ? $previousLicence : $currentLicence),
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
				$keyboard = new Keyboard(self::getBotId());
				$keyboard->addButton([
					"DISPLAY" => "LINE",
					"TEXT" => self::getMessage('PARTNER_BUTTON_MANAGE'),
					"LINK" => self::getMessage('PARTNER_BUTTON_MANAGE_URL'),
					"CONTEXT" => "DESKTOP",
				]);
				$messageParams[self::MESSAGE_PARAM_KEYBOARD] = $keyboard;

				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);
				$attach->addMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
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
					$attach->addMessage(self::getMessage('PARTNER_REQUEST_PROCESSED'));
					$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;
				}
				elseif ($messageFields['COMMAND_PARAMS'] === self::COMMAND_DECLINE_PARTNER_REQUEST)
				{
					Partner24::declineRequest($messageFields['FROM_USER_ID']);

					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::PROBLEM);
					$attach->addMessage(self::getMessage('PARTNER_REQUEST_REJECTED'));
					$messageParams[self::MESSAGE_PARAM_ATTACH] = $attach;
				}
				$messageParams[self::MESSAGE_PARAM_KEYBOARD] = 'N';
			}

			\CIMMessageParam::set($messageId, $messageParams);
			\CIMMessageParam::sendPull($messageId, [self::MESSAGE_PARAM_ATTACH, self::MESSAGE_PARAM_KEYBOARD]);

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
					Mixin\MESSAGE_PARAM_MENU_ACTION => 'SKIP:MENU',
				],
			]);

			$userGender = Im\User::getInstance(self::getCurrentUser()->getId())->getGender();
			$forward = self::getMessage('START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
			if (!$forward)
			{
				$forward = Loc::getMessage('SUPPORT24_START_DIALOG_'. ($userGender == 'F' ? 'F' : 'M'));
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
						&& isset($message['params'], $message['params']['IMOL_SID'])/** @see MessageParameter::IMOL_SID */
						&& (int)$message['params']['IMOL_SID'] > 0 /** @see MessageParameter::IMOL_SID - SESSION_ID */
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
		elseif ($messageFields['COMMAND'] === ItrMenu::COMMAND_MENU)
		{
			if (
				self::isEnabledQuestionFunctional()
				&& !(self::instanceDialogSession(self::getBotId(), $messageFields['DIALOG_ID'])->getSessionId() > 0)
			)
			{
				if ($messageFields['COMMAND_PARAMS'] === Mixin\COMMAND_RESUME_SESSION)
				{
					self::dropMessage((int)$messageId);

					// restart itr
					if (self::hasBotMenu())
					{
						self::showMenu([
							'DIALOG_ID' => $messageFields['DIALOG_ID'],
							'FULL_REDRAW' => true,
						]);
					}

					return true;
				}

				// block only redirect on operator
				if (
					self::isQuitMenuCommand($messageFields)
					&& !self::allowAdditionalQuestion()
				)
				{
					self::resetMenuState($messageFields['DIALOG_ID']);

					static::disableMessageButtons((int)$messageId);

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
			}

			self::handleMenuCommand($messageId, $messageFields);

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
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		(new DialogSession)->clearSessions(['BOT_ID' => self::getBotId()]);

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
		$response = $http->query(
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

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function onAfterUserAuthorize($params)
	{
		$auth = \CHTTP::parseAuthRequest();
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
		if (!Loader::includeModule('im'))
		{
			return false;
		}

		if (!self::getBotId())
		{
			return false;
		}

		$botCache = Im\Bot::getCache(self::getBotId());
		if (!empty($botCache['APP_ID']) && $botCache['APP_ID'] !== self::getBotCode())
		{
			Option::delete(self::MODULE_ID, ['name' => parent::BOT_CODE.'_'.$botCache['APP_ID']."_bot_id"]);
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
			'HIDDEN' => 'Y',
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

		Im\Bot::clearCache();
		Im\Bot::update(['BOT_ID' => self::getBotId()], $botParams);

		self::registerCommands(self::getBotId());
		self::registerApps(self::getBotId());

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
				$isShown = (int)\CUserOptions::getOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, 0, $userId);
				if ($isShown == 0)
				{
					if (self::isActiveFreeSupport() && self::isActiveFreeSupportForUser($userId))
					{
						self::scheduleAction($userId, self::SCHEDULE_ACTION_WELCOME, '', 10);
					}

					\CUserOptions::setOption(self::MODULE_ID, self::OPTION_BOT_WELCOME_SHOWN, \time(), false, $userId);
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
	public static function isStagePortal(): bool
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
		$time = \time();
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
		// recent talking in depth 30 days
		foreach (self::getRecentDialogs(24 * 30) as $dialog)
		{
			if ($dialog['RECENTLY_TALK'] === 'Y')
			{
				$recentUsers[] = (int)$dialog['USER_ID'];
			}
		}
		// remove recent talking
		$notifyUsers = array_unique(array_diff($notifyUsers, $recentUsers));
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

					if (self::hasBotMenu())
					{
						self::showMenu(['DIALOG_ID' => $userId]);
					}
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

					if (self::hasBotMenu())
					{
						$messageId = self::getBotMenu()->setDialogId((string)$userId)->getMessageId();
						if ($messageId)
						{
							self::disableMessageButtons((int)$messageId);
						}
						self::resetMenuState((string)$userId);
					}
				}
			}
		}

		return true;
	}

	//endregion

	//region Schedule actions

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

		if ($action === self::SCHEDULE_ACTION_WELCOME)
		{
			if (!is_numeric($target))
			{
				// only for user
				return false;
			}
			if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			{
				return true;
			}
			if (!self::isActiveFreeSupport() || !self::isActiveFreeSupportForUser($target))
			{
				return true;
			}

			\CIMMessage::getChatId($target, self::getBotId());
		}
		elseif ($action === self::SCHEDULE_ACTION_INVOLVEMENT)
		{
			if (!is_numeric($target))
			{
				// only for user
				return false;
			}
			if (self::getSupportLevel() != self::SUPPORT_LEVEL_FREE)
			{
				return true;
			}
			if (!self::isActiveFreeSupport() || !self::isActiveFreeSupportForUser($target))
			{
				return true;
			}

			$generationDate = (int)Option::get('imbot', self::OPTION_BOT_FREE_START_DATE, 0);
			$currentDay = (int)floor((\time() - $generationDate) / 86400) + 1;

			self::scheduleAction($target, self::SCHEDULE_ACTION_INVOLVEMENT, '', 24*60);

			$message = self::getMessage((string)$currentDay);
			if ($message == '')
			{
				return false;
			}

			$lastMessageMinTime = self::INVOLVEMENT_LAST_MESSAGE_BLOCK_TIME * 60 * 60; // hour to second

			$query = "
				SELECT
					RU.USER_ID,
					RU.CHAT_ID,
					CASE WHEN unix_timestamp(MB.DATE_CREATE) > unix_timestamp(now()) - ".$lastMessageMinTime." WHEN 'Y' ELSE 'N' END AS BOT_RECENTLY_TALK,
					CASE WHEN unix_timestamp(MU.DATE_CREATE) > unix_timestamp(now()) - ".$lastMessageMinTime." WHEN 'Y' ELSE 'N' END AS USER_RECENTLY_TALK
				FROM
					b_im_relation RB LEFT JOIN b_im_message MB ON RB.LAST_ID = MB.ID,
					b_im_relation RU LEFT JOIN b_im_message MU ON RU.LAST_ID = MU.ID
				WHERE
					RB.USER_ID = ".self::getBotId()."
				and RU.USER_ID = ".$target."
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
				'DIALOG_ID' => $target,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

			return true;
		}
		elseif ($action === self::SCHEDULE_ACTION_MESSAGE)
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
				'DIALOG_ID' => $target,
				'MESSAGE' => $message,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);
		}
		elseif ($action === self::SCHEDULE_ACTION_PARTNER_JOIN)
		{
			$keyboard = new Keyboard(self::getBotId());
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
				'DIALOG_ID' => $target,
				'MESSAGE' => self::getMessage('PARTNER_REQUEST'),
				'KEYBOARD' => $keyboard,
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			]);

			return true;
		}
		elseif ($action === self::SCHEDULE_ACTION_HIDE_DIALOG)
		{
			$session = self::instanceDialogSession((int)self::getBotId(), $target);

			$sessionActive = (
				$session->getSessionId() > 0
				&& $session->getParam('STATUS') !== self::MULTIDIALOG_STATUS_CLOSE
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
		elseif ($action === self::SCHEDULE_ACTION_CHECK_STAGE)
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
	public static function getMessage(string $code, $supportLevel = null): ?string
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
	public static function replacePlaceholders($message, $userId = 0): string
	{
		if (!Loader::includeModule('im'))
		{
			return $message;
		}

		$message = parent::replacePlaceholders($message, $userId);

		if (!Loader::includeModule('bitrix24'))
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

	//endregion
}