<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Careteam extends Base
{
	const MODULE_ID = 'imbot';
	const BOT_CODE = 'careteam';
	const BOT_COLOR = 'AZURE';
	const BOT_GENDER = 'F';

	public static function register(array $params = [])
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';
		if (self::getBotId())
		{
			return $agentMode ? "" : self::getBotId();
		}

		$botId = \Bitrix\Im\Bot::register([
			'CODE' => self::BOT_CODE,
			'TYPE' => \Bitrix\Im\Bot::TYPE_BOT,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => [
				'NAME' => Loc::getMessage('IMBOT_CARETEAM_BOT_NAME'),
				'WORK_POSITION' => Loc::getMessage('IMBOT_CARETEAM_WORK_POSITION'),
				'COLOR' => self::BOT_COLOR,
				'GENDER' => self::BOT_GENDER,
				'PERSONAL_PHOTO' => self::uploadAvatar(),
			]
		]);
		if ($botId)
		{
			self::setBotId($botId);

			\Bitrix\Im\Command::register([
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'careButton',
				'COMMON' => 'N',
				'HIDDEN' => 'N',
				'SONET_SUPPORT' => 'N',
				'EXTRANET_SUPPORT' => 'N',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onCommandAdd',
			]);
		}

		return $agentMode ? "" : $botId;
	}

	public static function unRegister(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$result = \Bitrix\Im\Bot::unRegister(['BOT_ID' => self::getBotId()]);
		if ($result)
		{
			self::setBotId(0);
		}

		return $result;
	}

	public static function onCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
		{
			return false;
		}

		if (
			$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
			$messageFields['FROM_USER_ID'] == self::getBotId() ||
			$messageFields['TO_USER_ID'] == self::getBotId()
		)
		{
			\Bitrix\Im\Bot::startWriting(['BOT_ID' => self::getBotId()], $messageFields['DIALOG_ID']);
		}

		$sender = new \Bitrix\ImBot\Sender\Careteam();
		$sender->sendKeyboardCommand($messageFields);

		return true;
	}

	public static function onMessageAdd($messageId, $messageFields): bool
	{
		if ($messageFields['SYSTEM'] == 'Y')
		{
			return false;
		}

		if (
			$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
			$messageFields['FROM_USER_ID'] == self::getBotId() ||
			$messageFields['TO_USER_ID'] == self::getBotId()
		)
		{
			\Bitrix\Im\Bot::startWriting(['BOT_ID' => self::getBotId()], $messageFields['DIALOG_ID']);
		}

		$sender = new \Bitrix\ImBot\Sender\Careteam();
		$sender->sendMessage($messageFields);

		return true;
	}
}
