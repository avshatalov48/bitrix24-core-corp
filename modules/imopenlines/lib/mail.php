<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Localization\Loc;

class Mail
{
	public static function addSessionToMailQueue($sessionId, $withCheck = true)
	{
		if ($withCheck)
		{
			$orm = Model\SessionCheckTable::getList(Array(
				'select' => Array('SESSION_ID', 'SOURCE'),
				'filter' => Array('=SESSION_ID' => $sessionId)
			));
			$session = $orm->fetch();

			if (!$session || $session['SOURCE'] != 'livechat' || $session['SPAM'] == 'Y')
			{
				return false;
			}
		}

		$mailData = new \Bitrix\Main\Type\DateTime();
		$mailData->add('1 MINUTE');

		\Bitrix\ImOpenlines\Model\SessionCheckTable::update($sessionId, Array(
			'DATE_MAIL' => $mailData
		));

		$event = new \Bitrix\Main\Event("imopenlines", "OnSessionToMailQueueAdd", Array('SESSION_ID' => $sessionId));
		$event->send();

		return true;
	}

	public static function removeSessionFromMailQueue($sessionId, $withCheck = true)
	{
		if ($withCheck)
		{
			$orm = Model\SessionCheckTable::getList(Array(
				'select' => Array('SESSION_ID'),
				'filter' => Array('=SESSION_ID' => $sessionId)
			));
			if (!$orm->fetch())
			{
				return false;
			}
		}

		\Bitrix\ImOpenlines\Model\SessionCheckTable::update($sessionId, Array(
			'DATE_MAIL' => null
		));

		return true;
	}

	public static function sendOperatorAnswerAgent($sessionId)
	{
		self::sendOperatorAnswer($sessionId);

		return "";
	}

	public static function sendOperatorAnswer($sessionId)
	{
		$sessionId = intval($sessionId);
		if ($sessionId <= 0 || !\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$select =  Model\SessionTable::getSelectFieldsPerformance();
		$select['CONFIG_LINE_NAME'] = 'CONFIG.LINE_NAME';
		$select['CONFIG_LANGUAGE_ID'] = 'CONFIG.LANGUAGE_ID';
		$select['LIVECHAT_URL_CODE'] = 'LIVECHAT.URL_CODE';
		$select['LIVECHAT_URL_CODE_PUBLIC'] = 'LIVECHAT.URL_CODE_PUBLIC';

		$orm = Model\SessionTable::getList(Array(
			'select' => $select,
			'filter' => Array('=ID' => $sessionId)
		));
		if ($session = $orm->fetch())
		{
			\Bitrix\ImOpenlines\Model\SessionCheckTable::update($session['ID'], Array(
				'DATE_MAIL' => null
			));
		}

		if (!$session || $session['SOURCE'] != 'livechat' || $session['SPAM'] == 'Y')
		{
			return false;
		}

		$email = \Bitrix\Im\User::getInstance($session['USER_ID'])->getEmail();
		if (!$email)
		{
			return false;
		}

		$messages = self::prepareOperatorAnswerForTemplate($session['ID'], false);
		if ($messages <= 0)
		{
			return false;
		}

		$mess = Loc::loadLanguageFile(__FILE__, $session['CONFIG_LANGUAGE_ID']? $session['CONFIG_LANGUAGE_ID']: null);

		$lineName = $session['CONFIG_LINE_NAME'];
		$widgetUrl = $session['EXTRA_URL'];
		if (empty($widgetUrl))
		{
			if (!empty($session['LIVECHAT_URL_CODE_PUBLIC']))
			{
				$widgetUrl = \Bitrix\ImOpenLines\Common::getServerAddress().'/online/'.$session['LIVECHAT_URL_CODE_PUBLIC'];
			}
			else if (!empty($session['LIVECHAT_URL_CODE']))
			{
				$widgetUrl = \Bitrix\ImOpenLines\Common::getServerAddress().'/online/'.$session['LIVECHAT_URL_CODE'];
			}
		}
		$widgetUrlParsed = parse_url($widgetUrl);

		$title = str_replace(
			Array('#SITE_URL#', '#SESSION_ID#'),
			Array($widgetUrlParsed['host'], $sessionId),
			$mess['IMOL_MAIL_HISTORY_TITLE']
		);
		$actionTitle = $mess['IMOL_MAIL_ANSWER_ACTION_TITLE'];
		$actionDesc = str_replace(
			Array('#SESSION_ID#'),
			Array($sessionId),
			$mess['IMOL_MAIL_HISTORY_ACTION_DESC']
		);

		$arFields = array(
			"EMAIL_TO" => $email,
			"EMAIL_TITLE" => $title,
			"TEMPLATE_SERVER_ADDRESS" => \Bitrix\ImOpenLines\Common::getServerAddress(),
			"TEMPLATE_CONFIG_ID" => $session['CONFIG_ID'],
			"TEMPLATE_SESSION_ID" => $sessionId,
			"TEMPLATE_ACTION_TITLE" => $actionTitle,
			"TEMPLATE_ACTION_DESC" => $actionDesc,
			"TEMPLATE_WIDGET_DOMAIN" => $widgetUrlParsed['host'],
			"TEMPLATE_WIDGET_URL" => $widgetUrl,
			"TEMPLATE_LINE_NAME" => $lineName,
		);

		$event = new \CEvent;
		$event->Send("IMOL_OPERATOR_ANSWER", SITE_ID, $arFields, "N", "", array(), $session['CONFIG_LANGUAGE_ID']);

		return true;
	}

	public static function prepareOperatorAnswerForTemplate($sessionId, $setSendFlag = true)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$sessionId = intval($sessionId);
		if (!$sessionId)
			return false;

		$select =  Model\SessionTable::getSelectFieldsPerformance();
		$select['CONFIG_LANGUAGE_ID'] = 'CONFIG.LANGUAGE_ID';

		$orm = Model\SessionTable::getList(Array(
			'select' => $select,
			'filter' => Array('=ID' => $sessionId)
		));
		$session = $orm->fetch();
		if (!$session || $session['SOURCE'] != 'livechat')
		{
			return false;
		}
		$parsedUserCode = Session\Common::parseUserCode($session['USER_CODE']);
		$chatId = $parsedUserCode['EXTERNAL_CHAT_ID'];

		$CIMChat = new \CIMChat($session['USER_ID']);
		$result = $CIMChat->GetLastMessageLimit($chatId, $session['START_ID'], $session['END_ID'], false, false);
		if (!$result)
		{
			return false;
		}

		$messages = Array();
		$findClientMessage = false;
		$findOperatorMessage = false;
		$lastMessageId = null;
		foreach ($result['message'] as $messageId => $messageData)
		{
			if (!$lastMessageId)
			{
				$lastMessageId = $messageId;
			}
			if (count($messages) == 0 && $messageId == $session['LAST_SEND_MAIL_ID'])
			{
				break;
			}
			if ($messageId < $session['LAST_SEND_MAIL_ID'])
			{
				if ($findClientMessage && $messageData['senderId'] != $session['USER_ID'])
				{
					if ($findClientMessage)
					{
						break;
					}
				}
			}
			if ($messageData['senderId'] == $session['USER_ID'])
			{
				$findClientMessage = true;
			}
			else
			{
				$findOperatorMessage = true;
			}
			$messages[$messageId] = $messageData;
		}

		if (!$findOperatorMessage)
			return false;

		\Bitrix\Main\Type\Collection::sortByColumn($messages, Array('id' => SORT_ASC), '', null, true);
		$result['message'] = $messages;

		$messages = self::prepareMessagesForTemplate($session, $result, $session['CONFIG_LANGUAGE_ID']);

		if ($setSendFlag)
		{
			Session::setLastSendMailId($session, $lastMessageId);
		}

		return $messages;
	}

	public static function sendSessionHistory($sessionId, $email)
	{
		$sessionId = intval($sessionId);
		if ($sessionId <= 0 || $email == '')
		{
			return false;
		}

		$select = Model\SessionTable::getSelectFieldsPerformance();
		$select['CONFIG_LINE_NAME'] = 'CONFIG.LINE_NAME';
		$select['CONFIG_LANGUAGE_ID'] = 'CONFIG.LANGUAGE_ID';
		$select['LIVECHAT_URL_CODE'] = 'LIVECHAT.URL_CODE';
		$select['LIVECHAT_URL_CODE_PUBLIC'] = 'LIVECHAT.URL_CODE_PUBLIC';

		$orm = Model\SessionTable::getList(Array(
			'select' => $select,
			'filter' => Array('=ID' => $sessionId)
		));
		$session = $orm->fetch();
		if (!$session)
		{
			return false;
		}

		$mess = Loc::loadLanguageFile(__FILE__, $session['CONFIG_LANGUAGE_ID']? $session['CONFIG_LANGUAGE_ID']: null);

		Log::write(Array(
			'LANG' => $select['CONFIG_LANGUAGE_ID']? $select['CONFIG_LANGUAGE_ID']: null,
			'MESS' => $mess
		));

		$lineName = $session['CONFIG_LINE_NAME'];
		$widgetUrl = $session['EXTRA_URL'];
		if (empty($widgetUrl))
		{
			if (!empty($session['LIVECHAT_URL_CODE_PUBLIC']))
			{
				$widgetUrl = \Bitrix\ImOpenLines\Common::getServerAddress().'/online/'.$session['LIVECHAT_URL_CODE_PUBLIC'];
			}
			else if (!empty($session['LIVECHAT_URL_CODE']))
			{
				$widgetUrl = \Bitrix\ImOpenLines\Common::getServerAddress().'/online/'.$session['LIVECHAT_URL_CODE'];
			}
		}
		$widgetUrlParsed = parse_url($widgetUrl);

		$title = str_replace(
			Array('#SITE_URL#', '#SESSION_ID#'),
			Array($widgetUrlParsed['host'], $sessionId),
			$mess['IMOL_MAIL_HISTORY_TITLE']
		);
		$actionTitle = $mess['IMOL_MAIL_HISTORY_ACTION_TITLE'];
		$actionDesc = str_replace(
			Array('#SESSION_ID#'),
			Array($sessionId),
			$mess['IMOL_MAIL_HISTORY_ACTION_DESC']
		);

		$arFields = array(
			"EMAIL_TO" => $email,
			"EMAIL_TITLE" => $title,
			"TEMPLATE_SERVER_ADDRESS" => \Bitrix\ImOpenLines\Common::getServerAddress(),
			"TEMPLATE_SESSION_ID" => $sessionId,
			"TEMPLATE_ACTION_TITLE" => $actionTitle,
			"TEMPLATE_ACTION_DESC" => $actionDesc,
			"TEMPLATE_WIDGET_DOMAIN" => $widgetUrlParsed['host'],
			"TEMPLATE_WIDGET_URL" => $widgetUrl,
			"TEMPLATE_LINE_NAME" => $lineName,
		);

		$event = new \CEvent;
		$event->Send("IMOL_HISTORY_LOG", SITE_ID, $arFields, "N", "", Array(), $session['CONFIG_LANGUAGE_ID']);

		\CEvent::ExecuteEvents();

		Model\SessionTable::update($session['ID'], Array('SEND_HISTORY' => 'Y'));

		return true;
	}

	public static function prepareSessionHistoryForTemplate($sessionId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$sessionId = intval($sessionId);
		if (!$sessionId)
			return false;

		$select =  Model\SessionTable::getSelectFieldsPerformance();
		$select['CONFIG_LANGUAGE_ID'] = 'CONFIG.LANGUAGE_ID';

		$orm = Model\SessionTable::getList(Array(
			'select' => $select,
			'filter' => Array('=ID' => $sessionId)
		));
		$session = $orm->fetch();
		if (!$session)
		{
			return false;
		}

		$CIMChat = new \CIMChat($session['USER_ID']);
		$result = $CIMChat->GetLastMessageLimit($session['CHAT_ID'], $session['START_ID'], $session['END_ID'], false, false, 'ASC');
		if (!$result)
			return false;

		$messages = self::prepareMessagesForTemplate($session, $result, $session['CONFIG_LANGUAGE_ID']);

		return $messages;
	}


	private static function prepareMessagesForTemplate($session, $history, $language = null)
	{
		$language = $language? $language: null;
		$mess = Loc::loadLanguageFile(__FILE__, $language);

		$userTzOffset = \Bitrix\Im\User::getInstance($session['USER_ID'])->getTzOffset();

		$messages = Array();
		foreach ($history['message'] as $messageId => $message)
		{
			if (empty($message['params']['CONNECTOR_MID']))
				continue;

			$isYou = $message['senderId'] == $session['USER_ID'];

			if ($message['senderId'] > 0)
			{
				if ($isYou)
				{
					$authorName = $mess['IMOL_MAIL_AUTHOR_YOU'];
				}
				else
				{
					$authorName = \Bitrix\ImOpenLines\Connector::getOperatorName($session['CONFIG_ID'], $message['senderId'], $session['USER_CODE']);
				}

				$authorAvatar = \Bitrix\ImOpenLines\Connector::getOperatorAvatar($session['CONFIG_ID'], $message['senderId'], $session['USER_CODE']);
				if ($authorAvatar)
				{
					$authorAvatar = mb_substr($authorAvatar, 0, 4) != 'http'? \Bitrix\ImOpenLines\Common::getServerAddress().$authorAvatar: $authorAvatar;
				}
				else
				{
					$authorAvatar = '';
				}

				$systemFlag = 'N';
			}
			else
			{
				$authorName = '';
				$authorAvatar = '';
				$systemFlag = 'Y';
			}

			$currentDate = new \Bitrix\Main\Type\DateTime();
			if (is_object($message['date']))
			{
				$date = $message['date'];
			}
			else
			{
				$date = \Bitrix\Main\Type\DateTime::createFromTimestamp($message['date']);
			}

			if ($date->format('Ymd') == $currentDate->format('Ymd'))
			{
				$messageDate = \FormatDate($mess['IMOL_MAIL_TIME_FORMAT'], $message['date']->getTimestamp()+intval($userTzOffset));
			}
			else
			{
				$messageDate = \FormatDate($mess['IMOL_MAIL_DATETIME_FORMAT'], $message['date']->getTimestamp()+intval($userTzOffset));
			}

			if (isset($message['params']['IMOL_VOTE']))
			{
				if ($message['params']['IMOL_VOTE'] == 'like')
				{
					$messageText = $message['params']['IMOL_VOTE_LIKE'];
				}
				else if ($message['params']['IMOL_VOTE'] == 'dislike')
				{
					$messageText = $message['params']['IMOL_VOTE_DISLIKE'];
				}
				else
				{
					$messageText = $message['params']['IMOL_VOTE_TEXT'];
				}
			}
			else
			{
				$messageText = $message['textLegacy'];

				if (isset($message['params']['FILE_ID']))
				{
					$messageText .= ' ['.$mess['IMOL_MAIL_FILE'].']';

					$messageText = trim($messageText);
				}
			}

			$messages[$messageId] = Array(
				'NAME' => htmlspecialcharsbx($authorName),
				'AVATAR' => $authorAvatar,
				'DATE' => $messageDate,
				'TEXT' => $messageText,
				'CLIENT' => $isYou? 'Y': 'N',
				'SYSTEM' => $systemFlag,
			);
		}

		return $messages;
	}

	public static function installEventsAgent()
	{
		$orm = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(array(
			'select' => array('ID'),
			'filter' => Array(
				'=EVENT_NAME' => Array('IMOL_HISTORY_LOG', 'IMOL_OPERATOR_ANSWER')
			)
		));

		if(!$orm->fetch())
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php");
		}

		return "";
	}
}