<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

Loc::loadMessages(__FILE__);

class Properties extends Base
{
	const BOT_CODE = "properties";

	const ORGANIZATION = "ORGANIZATION";
	const IP = "IP";

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
				'NAME' => Loc::getMessage('IMBOT_PROPERTIES_BOT_NAME'),
				'COLOR' => Loc::getMessage('IMBOT_PROPERTIES_BOT_COLOR'),
				//'EMAIL' => Loc::getMessage('IMBOT_PROPERTIES_BOT_EMAIL'),
				'WORK_POSITION' => Loc::getMessage('IMBOT_PROPERTIES_BOT_WORK_POSITION'),
				'PERSONAL_PHOTO' => self::uploadAvatar(),
			)
		));
		if ($botId)
		{
			self::setBotId($botId);
		}

		return $agentMode? "": $botId;
	}

	public static function unRegister()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => self::getBotId()));
		if ($result)
		{
			self::setBotId(0);
		}

		return $result;
	}

	public static function onChatStart($dialogId, $joinFields)
	{
		if ($joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$message = Loc::getMessage('IMBOT_PROPERTIES_WELCOME_MESSAGE');
		}
		else
		{
			$message = Loc::getMessage('IMBOT_PROPERTIES_WELCOME_MESSAGE_CHAT');
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

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);

		$messageText = $messageFields['MESSAGE'];
		if(static::validateRequest($messageText))
		{
			self::sendMessage($messageFields['DIALOG_ID'], $messageId, $messageText);
			return true;
		}
		else
		{
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => Loc::getMessage('IMBOT_REQUEST_INVALID')
			));
			return false;
		}
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

		if ($messageFields['MESSAGE_ANSWER'])
		{
			$answer = $messageFields['MESSAGE_ANSWER'];
			$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);

			if(isset($answer['ORGANIZATION']))
			{
				$attach = static::convertOrganizationToAttach($answer['ORGANIZATION']);
			}
			else if(isset($answer['IP']))
			{
				$attach = static::convertIpToAttach($answer['IP']);
			}
			else if(isset($answer['SEARCH']))
			{
				$attach = static::convertSearchResultsToAttach($answer['SEARCH'], $messageFields);
			}

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'ATTACH' => $attach
			));
		}
		else
		{
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => isset($messageFields['ANSWER'])? $messageFields['ANSWER']: Loc::getMessage('IMBOT_PROPERTIES_NOT_FOUND_MESSAGE')
			));
		}

		return true;
	}

	/**
	 * @param array $organizationFields
	 * @return \CIMMessageParamAttach
	 */
	private static function convertOrganizationToAttach(array $organizationFields)
	{
		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attachFields[] = array(
				"NAME" => $organizationFields['NAME_SHORT'],
				"DISPLAY" => "BLOCK",
				"VALUE" => " "
		);
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_NAME'),
				"VALUE" => $organizationFields["NAME"],
				"DISPLAY" => "BLOCK"
		);
		if($organizationFields['TERMINATION_DATE'])
		{
			$terminationDate = new Date($organizationFields['TERMINATION_DATE'], 'Y-m-d');
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_TERMINATION_DATE'),
					"VALUE" => $terminationDate->toString(),
					"DISPLAY" => "BLOCK"
			);
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_TERMITATION_METHOD_NAME'),
					"VALUE" => $organizationFields["TERMITATION_METHOD_NAME"],
					"DISPLAY" => "BLOCK"
			);
		}
		if($organizationFields['STATUS'])
		{
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_STATUS'),
					"VALUE" => $organizationFields["STATUS"],
					"DISPLAY" => "BLOCK"
			);
		}
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_INN_KPP'),
				"VALUE" => $organizationFields["INN"]."/".$organizationFields["KPP"],
				"DISPLAY" => "COLUMN"
		);
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_OGRN'),
				"VALUE" => $organizationFields["OGRN"],
				"DISPLAY" => "COLUMN"
		);
		if($organizationFields['OKVED_CODE'])
		{
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_OKVED'),
					"VALUE" => $organizationFields["OKVED_CODE"],
					"DISPLAY" => "COLUMN"
			);
		}
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_MANAGER'),
				"VALUE" => $organizationFields["MANAGER"],
				"DISPLAY" => "BLOCK"
		);
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_ADDRESS'),
				"VALUE" => $organizationFields["ADDRESS"],
				"DISPLAY" => "BLOCK"
		);
		$attach->AddGrid($attachFields);
		return $attach;
	}

	/**
	 * @param array $ipFields
	 * @return \CIMMessageParamAttach
	 */
	private static function convertIpToAttach(array $ipFields)
	{
		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_IP'),
				"VALUE" => $ipFields["FIO"],
				"DISPLAY" => "BLOCK"
		);
		if($ipFields['TERMINATION_DATE'])
		{
			$terminationDate = new Date($ipFields['TERMINATION_DATE'], 'Y-m-d');
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_TERMINATION_DATE'),
					"VALUE" => $terminationDate->toString(),
					"DISPLAY" => "BLOCK"
			);
		}
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_INN'),
				"VALUE" => $ipFields["INN"],
				"DISPLAY" => "COLUMN"
		);
		$attachFields[] = array(
				"NAME" => Loc::getMessage('IMBOT_PROPERTIES_OGRNIP'),
				"VALUE" => $ipFields["OGRNIP"],
				"DISPLAY" => "COLUMN"
		);
		if($ipFields['OKVED_CODE'])
		{
			$attachFields[] = array(
					"NAME" => Loc::getMessage('IMBOT_PROPERTIES_OKVED'),
					"VALUE" => $ipFields["OKVED_CODE"],
					"DISPLAY" => "COLUMN"
			);
		}
		$attach->AddGrid($attachFields);
		return $attach;
	}

	/**
	 * @param array $searchResults
	 * @return \CIMMessageParamAttach
	 */
	private static function convertSearchResultsToAttach(array $searchResults, array $messageFields)
	{
		$requestFields = static::parseRequest($messageFields['MESSAGE']);

		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attach->AddMessage(Loc::getMessage('IMBOT_PROPERTIES_SEARCH_RESULTS'));
		foreach($searchResults['RESULTS'] as $searchResult)
		{
			$attach->AddMessage('[SEND='.$searchResult['OGRN'].']'.$searchResult['NAME_SHORT'].' ('.Loc::getMessage('IMBOT_PROPERTIES_INN').': '.$searchResult['INN'].')[/SEND]');
		}

		if(isset($searchResults['MORE']))
		{
			$newOffset = (int)$requestFields['OFFSET'] + count($searchResults['RESULTS']);
			$attach->addMessage('[SEND='.$requestFields['REQUEST'].' /more '.$newOffset.']'.Loc::getMessage('IMBOT_PROPERTIES_SHOW_MORE').'[/SEND]');
		}

		return $attach;
	}

	protected static function parseRequest($request)
	{
		if(preg_match('/^(.+?)\s\/more\s(\d+)$/', $request, $matches))
		{
			$result = array(
				'REQUEST' => $matches[1],
				'OFFSET' => $matches[2]
			);
		}
		else
		{
			$result = array(
				'REQUEST' => $request,
				'OFFSET' => 0
			);
		}
		return $result;
	}

	private static function sendMessage($dialogId, $messageId, $messageText)
	{
		$params = Array(
			'DIALOG_ID' => $dialogId,
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageText
		);
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

	private static function validateRequest($request)
	{
		return true;
	}

	public static function getLangMessage($messageCode = '')
	{
		return Loc::getMessage($messageCode);
	}
}