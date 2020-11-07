<?php

namespace Bitrix\ImBot\Bot;

abstract class Base
{
	const MODULE_ID = "imbot";
	const BOT_CODE = "";

	/** @var \Bitrix\ImBot\Error  */
	protected static $lastError;

	/** @var \Bitrix\ImBot\Http */
	protected static $httpClient;

	/**
	 * Returns registered bot Id.
	 *
	 * @return bool|int
	 */
	public static function getBotId()
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
		{
			return false;
		}

		return \Bitrix\Main\Config\Option::get(self::MODULE_ID, $class::BOT_CODE."_bot_id", 0);
	}

	/**
	 * Saves new Id of the registered bot.
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public static function setBotId($id)
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set(self::MODULE_ID, $class::BOT_CODE."_bot_id", $id);

		return true;
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
	public static function getBotOption($userId, $name, $value = false)
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
	public static function setBotOption($userId, $name, $value)
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
			return false;

		\CUserOptions::SetOption(self::MODULE_ID, $class::BOT_CODE.'_'.$name, $value, false, $userId);

		return true;
	}

	/**
	 * Event handler when bot join to chat.
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields)
	{
		return true;
	}

	/**
	 * Event handler on message add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		return true;
	}

	/**
	 * Event handler on answer add.
	 *
	 * @param string $command
	 * @param array $params
	 *
	 * @return array
	 */
	public static function onAnswerAdd($command, $params)
	{
		return null;
	}

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
		return true;
	}

	/**
	 * Returns title and description for app or command list.
	 *
	 * @param string $command App or command code.
	 * @param string $lang Language Id.
	 *
	 * @return bool|array
	 */
	public static function onCommandLang($command, $lang = null)
	{
		return false;
	}

	/**
	 * Event handler on bot remove.
	 *
	 * @param int|null $bodId
	 *
	 * @return bool
	 */
	public static function onBotDelete($bodId)
	{
		return self::setBotId(0);
	}

	/**
	 * @param string $lang
	 *
	 * @return array|bool|string
	 */
	public static function uploadAvatar($lang = LANGUAGE_ID)
	{
		$avatarUrl = '';

		$class = self::getClassName();
		if (!$class::BOT_CODE)
			return $avatarUrl;

		if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/avatar/'.$class::BOT_CODE.'/'.$lang.'.png'))
		{
			$avatarUrl = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/avatar/'.$class::BOT_CODE.'/'.$lang.'.png';
		}
		else if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/avatar/'.$class::BOT_CODE.'/default.png'))
		{
			$avatarUrl = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/avatar/'.$class::BOT_CODE.'/default.png';
		}

		$avatarUrl = $avatarUrl? \CFile::MakeFileArray($avatarUrl): '';

		return $avatarUrl;
	}

	/**
	 * @param $iconName
	 *
	 * @return bool|int
	 */
	public static function uploadIcon($iconName)
	{
		if ($iconName == '')
			return false;
		
		$iconId = false;

		$class = self::getClassName();
		if (!$class::BOT_CODE)
			return $iconId;

		if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/icon/'.$class::BOT_CODE.'/'.$iconName.'.png'))
		{
			$iconId = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/install/icon/'.$class::BOT_CODE.'/'.$iconName.'.png';
		}

		if ($iconId)
		{
			$iconId = \CFile::SaveFile(\CFile::MakeFileArray($iconId), 'imbot');
		}

		return $iconId;
	}

	/**
	 * @return \Bitrix\ImBot\Bot\Base
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @return \Bitrix\ImBot\Error
	 */
	public static function getError()
	{
		if (!self::$lastError)
		{
			self::$lastError = new \Bitrix\ImBot\Error(null, '', '');
		}
		return self::$lastError;
	}


	/**
	 * Returns web client.
	 *
	 * @param string $botCode Bot code Id.
	 *
	 * @return \Bitrix\ImBot\Http
	 */
	protected static function instanceHttpClient($botCode)
	{
		if (!(self::$httpClient instanceof \Bitrix\ImBot\Http))
		{
			self::$httpClient = new \Bitrix\ImBot\Http($botCode);
		}

		return self::$httpClient;
	}
}