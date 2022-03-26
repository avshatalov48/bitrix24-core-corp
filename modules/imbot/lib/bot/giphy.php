<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Giphy extends Base
{
	const BOT_CODE = "giphy";

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		if (self::getBotId())
			return $agentMode? "": self::getBotId();

		$botId = \Bitrix\Im\Bot::register(Array(
			'APP_ID' => isset($params['APP_ID'])? $params['APP_ID']: "",
			'CODE' => self::BOT_CODE,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => Array(
				'NAME' => Loc::getMessage('IMBOT_GIPHY_BOT_NAME'),
				'COLOR' => Loc::getMessage('IMBOT_GIPHY_BOT_COLOR'),
				'WORK_POSITION' => Loc::getMessage('IMBOT_GIPHY_BOT_WORK_POSITION'),
				'PERSONAL_GENDER' => Loc::getMessage('IMBOT_GIPHY_BOT_GENDER'),
				'PERSONAL_PHOTO' => self::uploadAvatar(),
			)
		));
		if ($botId)
		{
			self::setBotId($botId);

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'giphy',
				'COMMON' => 'Y',
				'HIDDEN' => 'N',
				'SONET_SUPPORT' => 'Y',
				'EXTRANET_SUPPORT' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onCommandAdd',
				'METHOD_LANG_GET' => 'onCommandLang'
			));

			\Bitrix\Im\App::register(Array(
				'MODULE_ID' => 'imbot',
				'BOT_ID' => $botId,
				'CODE' => 'browse',
				'REGISTERED' => 'N',
				'ICON_ID' => self::uploadIcon('browse'),
				'IFRAME' => self::getIframeUrl(),
				'IFRAME_WIDTH' => '270',
				'IFRAME_HEIGHT' => '370',
				'EXTRANET_SUPPORT' => 'Y',
				'LIVECHAT_SUPPORT' => 'Y',
				'CONTEXT' => 'all',
				'CLASS' => __CLASS__,
				'METHOD_LANG_GET' => 'onAppLang',
			));
		}

		return $agentMode? "": $botId;
	}

	public static function unRegister()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$appList = Array();
		$apps = \Bitrix\Im\App::getListCache();
		foreach ($apps as $app)
		{
			if ($app['BOT_ID'] != self::getBotId())
				continue;

			$appList[] = $app['HASH'];
		}

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => self::getBotId()));
		if ($result)
		{
			self::setBotId(0);

			$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
			foreach ($appList as $hash)
			{
				$http->query(
					'UnregisterIframe',
					Array('HASH' => $hash)
				);
			}
		}

		return $result;
	}

	public static function onChatStart($dialogId, $joinFields)
	{
		if ($joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$message = Loc::getMessage('IMBOT_GIPHY_WELCOME_MESSAGE');
		}
		else
		{
			$message = Loc::getMessage('IMBOT_GIPHY_WELCOME_MESSAGE_CHAT');
		}

		if ($message)
		{
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

		if (
			$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
			$messageFields['FROM_USER_ID'] == self::getBotId() ||
			$messageFields['TO_USER_ID'] == self::getBotId()
		)
		{
			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);
		}

		self::sendMessage(Array(
			'BOT_ID' => self::getBotId(),
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'DIALOG_WITH_BOT' => $messageFields['TO_USER_ID'] == self::getBotId()? 'Y': 'N',
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TYPE' => $messageFields['MESSAGE_TYPE'],
			'MESSAGE_TEXT' => $messageFields['MESSAGE']
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
				'MESSAGE_ANSWER' => $params['MESSAGE_ANSWER'],
				'MESSAGE_ANSWER_ALTER' => $params['MESSAGE_ANSWER_ALTER'],
				'ANSWER_URL' => $params['MESSAGE_URL']? $params['MESSAGE_URL']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'MESSAGE_ID' => $params['MESSAGE_ID']? intval($params['MESSAGE_ID']): 0,
				'COMMAND_ID' => $params['COMMAND_ID']? intval($params['COMMAND_ID']): 0,
				'COMMAND_CONTEXT' => $params['COMMAND_CONTEXT']? $params['COMMAND_CONTEXT']: 'TEXTAREA',
			));
			$result = Array('RESULT' => 'OK');
		}
		else
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command isnt found');
		}

		return $result;
	}

	public static function sendAnswer($messageId, $messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

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
			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard, Array("#RETRY#" => Loc::getMessage("IMBOT_GIPHY_COMMAND_GIPHY_RETRY")));
		}

		if ($messageFields['MESSAGE_ANSWER_ALTER'] == 'Y' && $messageFields['MESSAGE_ANSWER'])
		{
			$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			$attach->AddImages(Array(
				Array(
					"NAME" => $messageFields['MESSAGE'].' ('.$messageFields['MESSAGE_ANSWER']["fixed_height_small_width"].'x'.$messageFields['MESSAGE_ANSWER']["fixed_height_small_height"].')',
					"LINK" => $messageFields['MESSAGE_ANSWER']["fixed_height_small_url"]
				)
			));
			$messageParams = Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => Loc::getMessage('IMBOT_GIPHY_FOUND_ALTER_MESSAGE'),
				'ATTACH' => $attach,
			);
			if ($messageFields['COMMAND_ID'] > 0)
			{
				\Bitrix\Im\Command::addMessage(Array('MESSAGE_ID' => $messageFields['MESSAGE_ID'], 'COMMAND_ID' => $messageFields['COMMAND_ID']), $messageParams);
			}
			else
			{
				\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), $messageParams);
			}
		}
		else if ($messageFields['MESSAGE_ANSWER'])
		{
			$messageParams = Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => $messageFields['MESSAGE_ANSWER']["fixed_height_small_url"],
				'KEYBOARD' => $keyboard
			);
			if ($messageFields['COMMAND_ID'] > 0)
			{
				if ($messageFields['COMMAND_CONTEXT'] == 'KEYBOARD')
				{
					\CIMMessenger::Update($messageFields['MESSAGE_ID'], $messageParams['MESSAGE'], true, false, self::getBotId());
					\CIMMessageParam::Set($messageFields['MESSAGE_ID'], Array('KEYBOARD' => $keyboard? $keyboard: 'N'));
					\CIMMessageParam::SendPull($messageFields['MESSAGE_ID'], Array('KEYBOARD'));
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
		else
		{
			$messageParams = Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => isset($messageFields['ANSWER'])? $messageFields['ANSWER']: Loc::getMessage('IMBOT_GIPHY_NOT_FOUND_MESSAGE'),
			);
			if ($messageFields['COMMAND_ID'] > 0)
			{
				\Bitrix\Im\Command::addMessage(Array('MESSAGE_ID' => $messageFields['MESSAGE_ID'], 'COMMAND_ID' => $messageFields['COMMAND_ID']), $messageParams);
			}
			else
			{
				\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), $messageParams);
			}
		}

		return true;
	}

	private static function sendMessage($params)
	{
		$params['USER_LANG'] = LANGUAGE_ID;

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

		self::sendMessage(Array(
			'BOT_ID' => self::getBotId(),
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'DIALOG_WITH_BOT' => $messageFields['TO_USER_ID'] == self::getBotId()? 'Y': 'N',
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

	public static function onCommandLang($command, $lang = null)
	{
		$title = Loc::getMessage('IMBOT_GIPHY_COMMAND_'.mb_strtoupper($command).'_TITLE', null, $lang);
		$params = Loc::getMessage('IMBOT_GIPHY_COMMAND_'.mb_strtoupper($command).'_PARAMS', null, $lang);

		$result = false;
		if ($title <> '')
		{
			$result = Array(
				'TITLE' => $title,
				'PARAMS' => $params
			);
		}

		return $result;
	}

	public static function onAppLang($icon, $lang = null)
	{
		$title = Loc::getMessage('IMBOT_GIPHY_ICON_'.mb_strtoupper($icon).'_TITLE', null, $lang);
		$description = Loc::getMessage('IMBOT_GIPHY_ICON_'.mb_strtoupper($icon).'_DESCRIPTION', null, $lang);
		$copyright = Loc::getMessage('IMBOT_GIPHY_ICON_COPYRIGHT', null, $lang);

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

	public static function getLangMessage($messageCode = '')
	{
		return Loc::getMessage($messageCode);
	}

	public static function getIframeUrl()
	{
		$controllerUrl = 'https://marta.bitrix.info/iframe/giphy.php';

		if (defined('BOT_IFRAME_URL'))
		{
			$controllerUrl = BOT_IFRAME_URL.'giphy.php';
		}

		return $controllerUrl;
	}
}