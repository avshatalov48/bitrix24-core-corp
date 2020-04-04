<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

Loc::loadMessages(__FILE__);

class PropertiesUa extends Base
{
	const BOT_CODE = "propertiesua";

	const UO = "UO";
	const FO = "FO";

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
				'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_BOT_NAME'),
				'COLOR' => Loc::getMessage('IMBOT_PROPERTIESUA_BOT_COLOR'),
				//'EMAIL' => Loc::getMessage('IMBOT_PROPERTIESUA_BOT_EMAIL'),
				'WORK_POSITION' => Loc::getMessage('IMBOT_PROPERTIESUA_BOT_WORK_POSITION'),
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
			$message = Loc::getMessage('IMBOT_PROPERTIESUA_WELCOME_MESSAGE');
		}
		else
		{
			$message = Loc::getMessage('IMBOT_PROPERTIESUA_WELCOME_MESSAGE_CHAT');
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
				'MESSAGE' => Loc::getMessage('IMBOT_PROPERTIESUA_REQUEST_INVALID')
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

			if(isset($answer[static::UO]))
			{
				$attach = static::convertUoToAttach($answer[static::UO]);
			}
			else if(isset($answer[static::FO]))
			{
				$attach = static::convertFoToAttach($answer[static::FO]);
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
				'MESSAGE' => isset($messageFields['ANSWER'])? $messageFields['ANSWER']: Loc::getMessage('IMBOT_PROPERTIESUA_NOT_FOUND_MESSAGE')
			));
		}

		return true;
	}

	/**
	 * @param array $uoFields
	 * @return \CIMMessageParamAttach
	 */
	private static function convertUoToAttach(array $uoFields)
	{
		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attachFields[] = array(
				'NAME' => isset($uoFields['COMPANY_NAME']) ? $uoFields['COMPANY_NAME'] : '',
				'DISPLAY' => 'BLOCK',
				'VALUE' => ' '
		);
		$attachFields[] = array(
				'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_COMPANY_FULL_NAME'),
				'VALUE' => isset($uoFields['COMPANY_FULL_NAME']) ? $uoFields['COMPANY_FULL_NAME'] : '',
				'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_EDRPOU'),
			'VALUE' => isset($uoFields['EDRPOU']) ? $uoFields['EDRPOU'] : '',
			'DISPLAY' => 'COLUMN'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_ADDRESS'),
			'VALUE' => isset($uoFields['ADDRESS']) ? $uoFields['ADDRESS'] : '',
			'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_CEO_NAME'),
			'VALUE' => isset($uoFields['CEO_NAME']) ? $uoFields['CEO_NAME'] : '',
			'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_PRIMARY_ACTIVITY'),
			'VALUE' => isset($uoFields['PRIMARY_ACTIVITY']) ? $uoFields['PRIMARY_ACTIVITY'] : '',
			'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
				'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_STATUS'),
				'VALUE' => isset($uoFields['STATUS']) ? $uoFields['STATUS'] : '',
				'DISPLAY' => 'BLOCK'
		);
		$attach->AddGrid($attachFields);
		return $attach;
	}

	/**
	 * @param array $foFields
	 * @return \CIMMessageParamAttach
	 */
	private static function convertFoToAttach(array $foFields)
	{
		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attachFields[] = array(
			'NAME' => isset($foFields['NAME']) ? $foFields['NAME'] : '',
			'DISPLAY' => 'BLOCK',
			'VALUE' => ' '
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_ADDRESS'),
			'VALUE' => isset($foFields['ADDRESS']) ? $foFields['ADDRESS'] : '',
			'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_PRIMARY_ACTIVITY'),
			'VALUE' => isset($foFields['PRIMARY_ACTIVITY']) ? $foFields['PRIMARY_ACTIVITY'] : '',
			'DISPLAY' => 'BLOCK'
		);
		$attachFields[] = array(
			'NAME' => Loc::getMessage('IMBOT_PROPERTIESUA_STATUS'),
			'VALUE' => isset($foFields['STATUS']) ? $foFields['STATUS'] : '',
			'DISPLAY' => 'BLOCK'
		);
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
		$attach->AddMessage(Loc::getMessage('IMBOT_PROPERTIESUA_SEARCH_RESULTS'));
		foreach($searchResults['RESULTS'] as $searchResult)
		{
			$companyName = $searchResult['ENTITY_TYPE'].'.'.$searchResult['ID'];
			if (isset($searchResult['NAME']) && !empty($searchResult['NAME']))
				$companyName = $searchResult['NAME'];
			else if (isset($searchResult['COMPANY_NAME']) && !empty($searchResult['COMPANY_NAME']))
				$companyName = $searchResult['COMPANY_NAME'];
			else if (isset($searchResult['COMPANY_FULL_NAME']) && !empty($searchResult['COMPANY_FULL_NAME']))
				$companyName = $searchResult['COMPANY_FULL_NAME'];
			$msgBody = '[SEND='.$searchResult['ENTITY_TYPE'].'.'.$searchResult['ID'].']'.$companyName;
			if (isset($searchResult['EDRPOU']) && !empty($searchResult['EDRPOU']))
				$msgBody .= ' ('.Loc::getMessage('IMBOT_PROPERTIESUA_EDRPOU').': '.$searchResult['EDRPOU'].')';
			$msgBody .= '[/SEND]';
			$attach->AddMessage($msgBody);
		}

		if(isset($searchResults['MORE']))
		{
			$newOffset = (int)$requestFields['OFFSET'] + count($searchResults['RESULTS']);
			$attach->AddMessage('[SEND='.$requestFields['REQUEST'].' /more '.$newOffset.']'.Loc::getMessage('IMBOT_PROPERTIESUA_SHOW_MORE').'[/SEND]');
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