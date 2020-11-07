<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

if (\CModule::IncludeModule('bitrix24'))
{
	class Support extends \Bitrix\Bitrix24\SupportBot
	{
	}
}
else
{
	Loc::loadMessages(__FILE__);

	class Support extends Network
	{
		const BOT_CODE = "support";

		public static function register(array $params = Array())
		{
			global $APPLICATION;

			if (!\Bitrix\Main\Loader::includeModule('im'))
				return false;


			$botId = parent::join(self::getCode());

			if ($botId)
			{
				\Bitrix\Im\Bot::update(Array('BOT_ID' => $botId), Array(
					'CLASS' => __CLASS__,
					'METHOD_MESSAGE_ADD' => 'onMessageAdd',
					'METHOD_WELCOME_MESSAGE' => 'onChatStart',
					'METHOD_MESSAGE_ADD' => 'onMessageAdd',
				));
				$user = new \CUser;
				$user->Update($botId, array('PERSONAL_PHOTO' => self::uploadAvatar('https://helpdesk.bitrix24.com/images/support/bot.png')));

				$rs = \CUser::GetList($by = 'id', $order = 'asc', $arFilter = array('ACTIVE' => 'Y', 'GROUPS_ID' => 1));
				while($f = $rs->Fetch())
					\CIMMessage::GetChatId($f['ID'], $botId);

				\Bitrix\ImBot\Bot\Network::removeFdc(0);
			}
			elseif ($e = self::$lastError)
			{
				$APPLICATION->ThrowException($e->msg);
			}

			return $botId;
		}

		public static function checkPublicUrl($publicUrl = null)
		{
			if (!$url = \Bitrix\Main\Config\Option::get(self::MODULE_ID, "portal_url", ""))
				return false;
			if (!$ar = parse_url($url))
				return false;
			$host = $ar['host'];
			$port = $ar['port'];
			$ssl = $ar['scheme'] == 'https';

			if (!$port)
				$port = $ssl ? 443 : 80;

			if (preg_match('#^(127|10|172\.16|192\.168)\.#', $host))
				return false;

			$sc = new \CSiteCheckerTest;
			$httpClient = new \Bitrix\Main\Web\HttpClient(array(
				"socketTimeout" => 5,
				"streamTimeout" => 5,
				"disableSslVerification" => true,
			));
			$httpClient->setHeader('User-Agent', 'Bitrix Support Bot');
			$checker = 'http://checker.internal.bitrix24.com';
			$url = $checker.'/check/?license_hash='.LICENSE_HASH.'&host='.urlencode($host).'&port='.$port.'&https='.($ssl ? 'Y' : 'N');
			$result = $httpClient->get($url);

			return preg_match('#^Status: [23]0#m', $result);

		}

		public static function unRegister($code = '', $serverRequest = true)
		{
			if (!\Bitrix\Main\Loader::includeModule('im'))
				return false;

			$result = parent::unRegister(self::getCode());

			return $result;
		}

		public static function onChatStart($dialogId, $joinFields)
		{
			if (!$GLOBALS['USER']->IsAdmin())
				return true;

			$messageFields = $joinFields;
			$messageFields['DIALOG_ID'] = $dialogId;

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $messageFields['BOT_ID']), Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => Loc::getMessage('SUPPORT_WELCOME_MESSAGE', array('#USERNAME#' => \Bitrix\Im\User::getInstance($messageFields['USER_ID'])->getFullName(false))),
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N'
			));
			return true;
		}

		public static function onMessageAdd($messageId, $messageFields)
		{
			if (!$GLOBALS['USER']->IsAdmin())
				return true;

			return parent::onMessageAdd($messageId, $messageFields);
		}

		public static function getBotId()
		{
			return \Bitrix\ImBot\Bot\Network::getNetworkBotId(self::getCode());
		}

		public static function isEnabled()
		{
			return self::getBotId();
		}

		public static function getCode()
		{
			if ($f = \Bitrix\Main\Localization\CultureTable::getList(array('filter' => array('=CODE' => 'ru')))->fetch())
				$CODE = "4df232699a9e1d0487c3972f26ea8d25";
			else
				$CODE = "1a146ac74c3a729681c45b8f692eab73";
			return $CODE;
		}
	}
}
