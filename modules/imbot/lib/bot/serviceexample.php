<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;
use Bitrix\ImBot\Itr as Itr;

Loc::loadMessages(__FILE__);

class ServiceExample
{
	const MODULE_ID = "imbot";
	const BOT_CODE = "serviceexample";

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		if (self::getBotId())
			return $agentMode? "": self::getBotId();

		$botId = \Bitrix\Im\Bot::register(Array(
			'CODE' => self::BOT_CODE,
			'TYPE' => \Bitrix\Im\Bot::TYPE_SUPERVISOR,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_MESSAGE_UPDATE' => 'onMessageUpdate',
			'METHOD_MESSAGE_DELETE' => 'onMessageDelete',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => Array(
				'NAME' => "Service Bot for logging messages (example)",
				'WORK_POSITION' => "Collect and process messages from chats",
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
		\Bitrix\ImBot\Log::writeToFile(self::BOT_CODE.'.log', [$dialogId, $joinFields], 'BOT: START CHAT');

		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		\Bitrix\ImBot\Log::writeToFile(self::BOT_CODE.'.log', [$messageId, $messageFields], 'BOT: MESSAGE ADDED');

		return true;
	}

	public static function onMessageUpdate($messageId, $messageFields)
	{
		\Bitrix\ImBot\Log::writeToFile(self::BOT_CODE.'.log', [$messageId, $messageFields], 'BOT: MESSAGE UPDATED');

		return true;
	}

	public static function onMessageDelete($messageId, $messageFields)
	{
		\Bitrix\ImBot\Log::writeToFile(self::BOT_CODE.'.log', [$messageId, $messageFields], 'BOT: MESSAGE DELETED');

		return true;
	}

	public static function onBotDelete($bodId)
	{
		return self::setBotId(0);
	}

	public static function getBotId()
	{
		return \Bitrix\Main\Config\Option::get(self::MODULE_ID, self::BOT_CODE."_bot_id", 0);
	}

	public static function setBotId($id)
	{
		\Bitrix\Main\Config\Option::set(self::MODULE_ID, self::BOT_CODE."_bot_id", $id);
		return true;
	}
}


