<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class Marta extends Base
{
	const BOT_CODE = "marta";
	const EDIT_PHRASE = '*EDIT*';

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$language = null;
		if (isset($params['LANG']))
		{
			$language = $params['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}
		$language = in_array($language, Array('ru', 'en'))? $language: 'ru';

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		if (self::getBotId())
			return $agentMode? "": self::getBotId();

		$birthday = new \Bitrix\Main\Type\DateTime(Loc::getMessage('IMBOT_BOT_BIRTHDAY', null, $language).' 19:45:00', 'Y-m-d H:i:s');
		$birthday = $birthday->format(\Bitrix\Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT')));

		$botId = \Bitrix\Im\Bot::register(Array(
			'CODE' => self::BOT_CODE,
			'TYPE' => \Bitrix\Im\Bot::TYPE_BOT,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'LANG' => $language,
			'OPENLINE' => 'Y',
			'INSTALL_TYPE' => \Bitrix\Im\Bot::INSTALL_TYPE_SILENT,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => Array(
				'NAME' => Loc::getMessage('IMBOT_BOT_NAME', null, $language),
				'COLOR' => Loc::getMessage('IMBOT_BOT_COLOR', null, $language),
				//'EMAIL' => Loc::getMessage('IMBOT_BOT_EMAIL', null, $language),
				'PERSONAL_BIRTHDAY' => $birthday,
				'WORK_POSITION' => Loc::getMessage('IMBOT_BOT_WORK_POSITION', null, $language),
				'PERSONAL_WWW' => Loc::getMessage('IMBOT_BOT_SITE', null, $language),
				'PERSONAL_GENDER' => Loc::getMessage('IMBOT_BOT_GENDER', null, $language),
				'PERSONAL_PHOTO' => self::uploadAvatar($language),
			)
		));
		if ($botId)
		{
			self::setBotId($botId);

			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayStart", self::MODULE_ID, __CLASS__,  "onAfterTmDayStart");

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'tictactoe',
				'CLASS' => __CLASS__,
				'HIDDEN' => 'Y',
				'METHOD_COMMAND_ADD' => 'onCommandAdd'
			));

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'lang',
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onLocalCommandAdd'
			));

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'disable',
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onSettingsCommandAdd'
			));

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'enable',
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onSettingsCommandAdd'
			));

			if (\Bitrix\Main\Loader::includeModule('bitrix24'))
			{
				\Bitrix\Im\Command::register(Array(
					'MODULE_ID' => "bitrix24",
					'BOT_ID' => $botId,
					'HIDDEN' => 'Y',
					'COMMAND' => 'supportAccess',
					'CLASS' => "CBitrix24EventHandlers",
					'METHOD_COMMAND_ADD' => 'OnSupportAccess'
				));

				if (!\CBitrix24::isDomainChanged())
				{
					RegisterModuleDependences("bitrix24", "OnDomainChange", self::MODULE_ID, __CLASS__, "onRenamePortalDomainChange");
					\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addRenameMessageAgent();', "imbot", "N", 86400, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+86400, "FULL"));
				}
			}

			\Bitrix\Im\App::register(Array(
				'MODULE_ID' => 'imbot',
				'BOT_ID' => $botId,
				'CODE' => 'play',
				'ICON_ID' => self::uploadIcon('play'),
				'CLASS' => __CLASS__,
				'METHOD_LANG_GET' => 'onAppLang',
				'JS' => 'BXIM.sendMessage("/play")',
				'CONTEXT' => 'bot',
			));
		}

		return $agentMode? "": $botId;
	}

	/**
	 * Agent for deferred bot registration.
	 * @return string
	 */
	public static function delayRegister(): string
	{
		if (!Option::get('imbot', 'marta_bot_register_in_progress', 0))
		{
			Option::set('imbot', 'marta_bot_register_in_progress', 1);

			if (self::register())
			{
				Option::delete('imbot', ['name' => 'marta_bot_register_in_progress']);

				return '';
			}
		}

		return __METHOD__ . '();';
	}

	public static function unRegister()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => self::getBotId()));
		if ($result)
		{
			self::setBotId(0);

			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayStart", self::MODULE_ID, __CLASS__, "onAfterTmDayStart");
		}

		return $result;
	}

	public static function onChatStart($dialogId, $joinFields)
	{
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		$language = null;
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}

		if ($joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$message = Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE_2', Array('#HELLO#' => self::getHelloMessage($dialogId, $language)), $language);
			\CUserOptions::SetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', time(), false, $dialogId);
		}
		else
		{
			if ($joinFields['CHAT_ENTITY_TYPE'] == 'LINES')
			{
				$message = Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE_LINES_2', null, $language);
			}
			else
			{
				$message = Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE_CHAT_2', null, $language);
			}
		}

		if ($message)
		{
			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $dialogId);
			self::sendAnswer(0, Array(
				'DIALOG_ID' => $dialogId,
				'ANSWER' => $message
			));
		}

		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		if ($messageFields['CHAT_ENTITY_TYPE'] != 'LINES')
		{
			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);
		}

		$userName = \Bitrix\Im\User::getInstance($messageFields['FROM_USER_ID'])->getName();

		$dateNow = new \Bitrix\Main\Type\DateTime();
		self::setBotOption($messageFields['FROM_USER_ID'], 'last_message', $dateNow->format('Ymd'));

		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());

		if ($messageFields['MESSAGE'] == '0' && $messageFields['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			self::sendAnswer(0, Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'ANSWER' => Loc::getMessage('IMBOT_BOT_WELCOME_LINES_REDIRECT')
			));
			return true;
		}

		if (mb_strpos($messageFields['MESSAGE'], self::EDIT_PHRASE) !== false && \Bitrix\Im\User::getInstance($messageFields['FROM_USER_ID'])->isExtranet())
		{
			$messageFields['MESSAGE'] = str_replace(self::EDIT_PHRASE, '', $messageFields['MESSAGE']);
		}

		self::sendMessage(Array(
			'BOT_ID' => self::getBotId(),
			'BOT_LANG' => $botData['LANG'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageFields['MESSAGE'],
			'MESSAGE_TYPE' => $messageFields['MESSAGE_TYPE'],
			'USER_NAME' => htmlspecialcharsback($userName),
			'USER_AGE' => 30,
		));

		return true;
	}

	public static function onSettingsCommandAdd($messageId, $messageFields)
	{
		 if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
  		    return false;

		$userName = \Bitrix\Im\User::getInstance()->getName();

		if ($messageFields['COMMAND'] == 'enable')
		{
			if ($messageFields['COMMAND_PARAMS'] == 'welcome')
			{
				$message = Loc::getMessage('IMBOT_BOT_ENABLE_WELCOME', Array('#USER_NAME#' => $userName));
				$dateNow = new \Bitrix\Main\Type\DateTime();
				self::setBotOption($messageFields['DIALOG_ID'], 'planner_message', $dateNow->format('Ymd'));
			}
			else
			{
				return false;
			}
		}
		else if ($messageFields['COMMAND'] == 'disable')
		{
			if ($messageFields['COMMAND_PARAMS'] == 'welcome')
			{
				$message = Loc::getMessage('IMBOT_BOT_DISABLE_WELCOME', Array('#USER_NAME#' => $userName));
				self::setBotOption($messageFields['DIALOG_ID'], 'planner_message', '20290219');
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $messageFields['TO_USER_ID']), $messageFields['DIALOG_ID']);

		\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $messageFields['TO_USER_ID']), Array(
		   'DIALOG_ID' => $messageFields['DIALOG_ID'],
		   'MESSAGE' => $message,
		));
	}

	public static function onLocalCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		if ($messageFields['COMMAND_CONTEXT'] != 'TEXTAREA')
			return false;

		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
			return false;

		if ($messageFields['COMMAND'] != 'lang')
			return false;

		$messageFields['COMMAND_PARAMS'] = trim($messageFields['COMMAND_PARAMS']);
		if (!in_array($messageFields['COMMAND_PARAMS'], Array('en', 'ru')))
			return false;

		global $GLOBALS;
		$grantAccess = \IsModuleInstalled('bitrix24')? $GLOBALS['USER']->CanDoOperation('bitrix24_config'): $GLOBALS["USER"]->IsAdmin();
		if (!$grantAccess)
			return false;

		$language = $messageFields['COMMAND_PARAMS'];
		Loc::loadLanguageFile(__FILE__, $language);

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);

		\Bitrix\Im\Bot::update(Array('BOT_ID' => self::getBotId()), Array(
			'LANG' => $language,
			'PROPERTIES' => Array(
				'NAME' => Loc::getMessage('IMBOT_BOT_NAME', null, $language),
				'COLOR' => Loc::getMessage('IMBOT_BOT_COLOR', null, $language),
				'EMAIL' => Loc::getMessage('IMBOT_BOT_EMAIL', null, $language),
				'WORK_POSITION' => Loc::getMessage('IMBOT_BOT_WORK_POSITION', null, $language),
				'PERSONAL_WWW' => Loc::getMessage('IMBOT_BOT_SITE', null, $language),
				'PERSONAL_GENDER' => Loc::getMessage('IMBOT_BOT_GENDER', null, $language),
				'PERSONAL_PHOTO' => self::uploadAvatar($language),
			)
		));

		self::sendAnswer(0, Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'ANSWER' => Loc::getMessage('IMBOT_BOT_CHANGE_LANG', null, $language)
		));

		return true;
	}

	public static function onCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		if ($messageFields['COMMAND_CONTEXT'] == 'TEXTAREA')
		{
			if (
				$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
				$messageFields['FROM_USER_ID'] == self::getBotId() ||
				$messageFields['TO_USER_ID'] == self::getBotId()
			)
			{
				\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);
			}
		}

		$dateNow = new \Bitrix\Main\Type\DateTime();
		self::setBotOption($messageFields['FROM_USER_ID'], 'last_message', $dateNow->format('Ymd'));

		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());

		self::sendCommand(Array(
			'BOT_ID' => self::getBotId(),
			'BOT_LANG' => $botData['LANG'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageFields['MESSAGE'],
			'MESSAGE_TYPE' => $messageFields['MESSAGE_TYPE'],
			'COMMAND' => $messageFields['COMMAND'],
			'COMMAND_ID' => $messageFields['COMMAND_ID'],
			'COMMAND_PARAMS' => $messageFields['COMMAND_PARAMS'],
			'COMMAND_CONTEXT' => $messageFields['COMMAND_CONTEXT'],
		));

		return true;
	}

	public static function onAnswerAdd($command, $params)
	{
		if($command == "AnswerMessage")
		{
			self::sendAnswer($params['MESSAGE_ID'], Array(
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'ANSWER' => $params['MESSAGE_ANSWER'],
				'RICH' => $params['MESSAGE_RICH'],
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'ANSWER_URL' => $params['MESSAGE_URL']? $params['MESSAGE_URL']: '',
			));
			$result = Array('RESULT' => 'OK');
		}
		else if($command == "AnswerCommand")
		{
			self::sendAnswerCommand($params['MESSAGE_ID'], Array(
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'MESSAGE_ANSWER' => $params['MESSAGE_ANSWER'],
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'MESSAGE_ID' => $params['MESSAGE_ID']? intval($params['MESSAGE_ID']): 0,
				'COMMAND_ID' => $params['COMMAND_ID']? intval($params['COMMAND_ID']): 0,
				'COMMAND_CONTEXT' => $params['COMMAND_CONTEXT']? $params['COMMAND_CONTEXT']: 'TEXTAREA',
			));
			$result = Array('RESULT' => 'OK');
		}
		else
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command not found');
		}

		return $result;
	}

	public static function onAfterUserAuthorize($params)
	{
		return true;
	}

	public static function onAfterTmDayStart($params)
	{
		//$dateNow = new \Bitrix\Main\Type\DateTime();
		//if (self::getBotOption($params['USER_ID'], 'planner_message', 0) < $dateNow->format('Ymd'))
		//{
		//	\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addPlannerMessageAgent('.$params['USER_ID'].');', "imbot", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		//}
		self::notifyAboutPlans($params['USER_ID'], $params['USER_ID']);
	}

	public static function addPlannerMessageAgent($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return "";

		self::notifyAboutPlans($userId, $userId);

		return "";
	}

	public static function addRenameMessageAgent($userId = null)
	{
		if (!\Bitrix\Main\Loader::includeModule('im') || !\Bitrix\Main\Loader::includeModule('bitrix24'))
			return "";

		if (\CBitrix24::isDomainChanged())
			return "";

		return '';

		$language = null;
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}

		$option = Option::get(self::MODULE_ID, 'marta_rename_message', serialize(Array()));
		$messages = unserialize($option, ['allowed_classes' => false]);

		$userId = intval($userId);
		if ($userId)
		{
			$users = Array($userId);
		}
		else
		{
			$users = \CBitrix24::getAllAdminId();
		}
		foreach ($users as $userId)
		{
			$messages[] = self::sendAnswer(0, Array(
				'DIALOG_ID' => $userId,
				'ANSWER' => Loc::getMessage('IMBOT_BOT_RENAME_TEXT', null, $language),
				'KEYBOARD' => Array(
					Array(
						"TEXT" => Loc::getMessage('IMBOT_BOT_RENAME_BUTTON_NOW', null, $language),
						"FUNCTION" => "BX.MessengerCommon.openRenamePortal(this);",
						"BG_COLOR" => "#e98fa6",
						"TEXT_COLOR" => "#FFF",
						"DISPLAY" => "LINE",
					),
					Array(
						"TEXT" => Loc::getMessage('IMBOT_BOT_RENAME_BUTTON_LATER', null, $language),
						"COMMAND" => "renamePortalLater",
						"DISPLAY" => "LINE",
					),
				)
			));
		}

		Option::set(self::MODULE_ID, 'marta_rename_message', serialize($messages));

		RegisterModuleDependences("bitrix24", "OnDomainChange", self::MODULE_ID, __CLASS__, "onRenamePortalDomainChange");

		$commandId = Option::get(self::MODULE_ID, 'marta_rename_command', 0);
		if ($commandId <= 0)
		{
			$commandId = \Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => self::getBotId(),
				'COMMAND' => 'renamePortalLater',
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onRenamePortalLaterCommand'
			));
			Option::set(self::MODULE_ID, 'marta_rename_command', $commandId);
		}

		return "";
	}

	public static function onRenamePortalDomainChange($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('im') || !\Bitrix\Main\Loader::includeModule('bitrix24'))
			return true;

		$language = null;
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}

		$option = Option::get(self::MODULE_ID, 'marta_rename_message', serialize(Array()));
		$messages = unserialize($option, ['allowed_classes' => false]);
		foreach ($messages as $messageId)
		{
			\Bitrix\Im\Bot::updateMessage(Array('BOT_ID' => self::getBotId()), Array(
				'MESSAGE_ID' => $messageId,
				'KEYBOARD' => 'N',
			));
		}

		Option::set(self::MODULE_ID, 'marta_rename_message', serialize(Array()));

		$newDomain = '[url=http://'.$params['new_domain'].']'.$params['new_domain'].'[/url]';

		$users = \CBitrix24::getAllAdminId();
		foreach ($users as $userId)
		{
			$messages[] = self::sendAnswer(0, Array(
				'DIALOG_ID' => $userId,
				'ANSWER' => Loc::getMessage('IMBOT_BOT_RENAME_DONE', Array('#NEW_DOMAIN#' => $newDomain), $language),
				'RICH' => 'N'
			));
		}

		UnRegisterModuleDependences("bitrix24", "OnDomainChange", self::MODULE_ID, __CLASS__, "onRenamePortalDomainChange");

		$commandId = Option::get(self::MODULE_ID, 'marta_rename_command', 0);
		if ($commandId)
		{
			\Bitrix\Im\Command::unRegister(Array(
				'COMMAND_ID' => $commandId
			));
			Option::set(self::MODULE_ID, 'marta_rename_command', 0);
		}

		return true;
	}

	public static function onRenamePortalLaterCommand($messageId, $messageFields)
	{
		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
  		    return false;

		if (!\Bitrix\Main\Loader::includeModule('bitrix24') || !\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (\CBitrix24::isDomainChanged())
			return false;

		\Bitrix\Im\Bot::updateMessage(Array('BOT_ID' => self::getBotId()), Array(
			'MESSAGE_ID' => $messageId,
			'KEYBOARD' => 'N',
		));

		$language = null;
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}

		$messages[] = self::sendAnswer(0, Array(
			'DIALOG_ID' => $messageFields['FROM_USER_ID'],
			'ANSWER' => Loc::getMessage('IMBOT_BOT_RENAME_LATER', null, $language),
		));

		\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addRenameMessageAgent('.$messageFields['FROM_USER_ID'].');', "imbot", "N", 86400*7, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+(86400*7), "FULL"));

		return true;
	}

	public static function notifyAboutPlans($dialogId, $userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$userData = \Bitrix\Im\User::getInstance($userId);
		if (!$userData || $userData->isExtranet())
			return false;

		$dateNow = new \Bitrix\Main\Type\DateTime();
		if (self::getBotOption($userId, 'planner_message', 0) < $dateNow->format('Ymd'))
		{
			self::setBotOption($userId, 'planner_message', $dateNow->format('Ymd'));
		}
		else
		{
			return false;
		}

		$language = null;
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}

		$welcomeMessage = '';

		$dateNow = new \Bitrix\Main\Type\DateTime();
		if (self::getBotOption($userId, 'last_message', 0) < $dateNow->format('Ymd'))
		{
			$welcomeMessage = self::getHelloMessage($userId, $language).' :) [br]';
		}

		$answer = '';
		$attaches = Array();
		if (\Bitrix\Main\Loader::includeModule('intranet') && \Bitrix\Main\Loader::includeModule('calendar'))
		{
			$calendarEventUrl = \CCalendar::GetPath('user', $userId, true);
			$attach = new \CIMMessageParamAttach(1, \CIMMessageParamAttach::CHAT);
			$events = \CCalendarEventHandlers::OnPlannerInit(Array(
				'FULL' => true,
				'USER_ID' => $userId
			));
			$eventsForMessage = [];
			if (is_array($events['DATA']['EVENTS']))
			{
				foreach ($events['DATA']['EVENTS'] as $event)
				{
					if ($event['TIME_FROM'] == $event['TIME_TO'] && $event['TIME_FROM'] == '00:00')
					{
						$eventTimeFormatted = Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_4', null, $language);
					}
					else
					{
						$eventTimeFormatted = Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_3', Array('#TIME_1#' => $event['TIME_FROM'], '#TIME_2#' => $event['TIME_TO']), $language);
					}
					// if (count($eventsForMessage) <= 10)
					// {
					// 	$eventsForMessage[] = $eventTimeFormatted . ' - ' .$event['NAME'];
					// }
					$attach->AddGrid(Array(
						Array(
							"VALUE" => $eventTimeFormatted,
							"LINK" => \CHTTP::urlAddParams(
								$calendarEventUrl,
								[
									'EVENT_ID' => (int)$event['ID'],
									'EVENT_DATE' => urlencode($event['DATE_FROM'])
								]),
							"DISPLAY" => "LINE",
							"WIDTH" => 120,
						),
						Array(
							"VALUE" => $event['NAME'],
							"DISPLAY" => "LINE"
						),
					));
				}
			}
			if (!$attach->IsEmpty())
			{
				$answer .= Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_1_'.mt_rand(1, 4), null, $language);
				// if (!empty($eventsForMessage))
				// {
				// 	$attach->SetDescription(implode(', ', $eventsForMessage));
				// }
				$attaches[] = $attach;
			}
		}

		if (\Bitrix\Main\Loader::includeModule('tasks'))
		{
			try
			{
				$tasksCounter = \CTaskListCtrl::getMainCounterForUser($userId);
				if ($tasksCounter > 0)
				{
					$tasksUrl = \CTaskCountersNotifier::getTasksListLink($userId);

					$pluralForm = \CTasksTools::getPluralForm($tasksCounter, true);
					if ($pluralForm !== false)
						$taskMessage = 'IMBOT_BOT_MESSAGE_TASKS_'.($pluralForm+1);
					else
						$taskMessage = 'IMBOT_BOT_MESSAGE_TASKS_2';

					$answer = $answer.Loc::getMessage($taskMessage, Array('#TASKS_COUNT#' => $tasksCounter, '#URL_START#' => '[URL='.$tasksUrl.']', '#URL_END#' => '[/URL]'), $language);
				}
			}
			catch (\Exception $e)
			{}
		}

		if ($answer)
		{
			$answer = $welcomeMessage.'[br]'.$answer;

			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $dialogId);

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
				'DIALOG_ID' => $dialogId,
				'MESSAGE' => $answer,
				'ATTACH' => $attaches,
				'URL_PREVIEW' => 'N'
			));
		}

		return true;
	}

	public static function getHelloMessage($userId, $language = null)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$userName = \Bitrix\Im\User::getInstance($userId)->getName(false);
		if (!$userName)
			return "";

		if ($language)
		{
			Loc::loadLanguageFile(__FILE__, $language);
		}

		$dateNow = new \Bitrix\Main\Type\DateTime();
		$dateNow->add(\CTimeZone::GetOffset().' SECOND');
		$hour = $dateNow->format('H');

		if ($hour >= 18 && $hour <= 23 || $hour >= 0 && $hour < 5)
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_EVENING', Array('#USER_NAME#' => $userName), $language);
		}
		else if ($hour >= 5 && $hour < 12)
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_MORNING', Array('#USER_NAME#' => $userName), $language);
		}
		else
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_DAY', Array('#USER_NAME#' => $userName), $language);
		}

		return $message;
	}

	public static function sendAnswer($messageId, $messageFields)
	{
		$attach = Array();
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = Array();
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = Array('BOT_ID' => self::getBotId());
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard, Array(), Array('ENABLE_FUNCTIONS' => 'Y'));
		}

		if ($messageFields['ANSWER_URL'])
		{
			$messageFields['ANSWER'] = ' '.$messageFields['ANSWER_URL'];
		}

		$messageId = \Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['ANSWER'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard,
			'PARAMS' => isset($messageFields['PARAMS'])? $messageFields['PARAMS']: Array(),
			'URL_PREVIEW' => isset($messageFields['RICH'])? $messageFields['RICH']: "Y"
		));

		return $messageId;
	}

	public static function sendAnswerCommand($messageId, $messageFields)
	{
		$attach = Array();
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = Array();
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = Array('BOT_ID' => self::getBotId());
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
		}

		$messageParams = Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE_ANSWER'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard
		);

		if ($messageFields['COMMAND_ID'] > 0)
		{
			if ($messageFields['COMMAND_CONTEXT'] == 'KEYBOARD')
			{
				\CIMMessageParam::Set($messageFields['MESSAGE_ID'], Array('KEYBOARD' => $keyboard? $keyboard: 'N', 'ATTACH' => $attach? $attach: Array()));

				if (!empty($messageParams['MESSAGE']))
				{
					\CIMMessenger::Update($messageFields['MESSAGE_ID'], $messageParams['MESSAGE'], true, false, self::getBotId());
				}

				\CIMMessageParam::SendPull($messageFields['MESSAGE_ID'], Array('KEYBOARD', 'ATTACH'));
			}
			else
			{
				\Bitrix\Im\Command::addMessage(Array('MESSAGE_ID' => $messageFields['MESSAGE_ID'], 'COMMAND_ID' => $messageFields['COMMAND_ID']), $messageParams);
			}
		}
		else
		{
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), $messageParams);
		}
	}

	private static function sendMessage($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'SendMessage',
			$params
		);
		if (isset($query->error))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	private static function sendCommand($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'SendCommand',
			$params
		);
		if (isset($query->error))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public static function getLangMessage($messageCode = '')
	{
		$botData = \Bitrix\Im\Bot::getCache(self::getBotId());
		$language = null;
		if ($botData['LANG'])
		{
			$language = $botData['LANG'];
			Loc::loadLanguageFile(__FILE__, $language);
		}
		return Loc::getMessage($messageCode, null, $language);
	}

	public static function onAppLang($icon, $lang = null)
	{
		$title = Loc::getMessage('IMBOT_ICON_'.mb_strtoupper($icon).'_TITLE', null, $lang);
		$description = Loc::getMessage('IMBOT_ICON_'.mb_strtoupper($icon).'_DESCRIPTION', null, $lang);
		$copyright = '';

		$result = false;
		if ($title <> '')
		{
			$result = Array(
				'TITLE' => $title,
				'DESCRIPTION' => $description,
				'COPYRIGHT' => $copyright
			);
		}

		return $result;
	}

	public static function addWelcomeMessageAgent()
	{
		return "";
	}

	/**
	 * Returns per user option value.
	 *
	 * @param int $userId
	 * @param string $name
	 * @param bool $value
	 *
	 * @return bool|mixed
	 */
	protected static function getBotOption($userId, $name, $value = false)
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
			return false;

		return \CUserOptions::GetOption(self::MODULE_ID, $class::BOT_CODE.'_'.$name, $value, $userId);
	}

	/**
	 * Saves option value for certain user.
	 *
	 * @param int $userId
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected static function setBotOption($userId, $name, $value)
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
			return false;

		\CUserOptions::SetOption(self::MODULE_ID, $class::BOT_CODE.'_'.$name, $value, false, $userId);

		return true;
	}
}