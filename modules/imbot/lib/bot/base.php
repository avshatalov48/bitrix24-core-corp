<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\ImBot\Error;
use Bitrix\Main\Config\Option;

abstract class Base implements ChatBot
{
	const MODULE_ID = "imbot";
	const BOT_CODE = "";

	/** @var Error  */
	protected static $lastError;

	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	abstract public static function register(array $params = []);

	/**
	 * Unregister bot at portal.
	 *
	 * @return bool
	 */
	abstract public static function unRegister();

	/**
	 * Returns registered bot Id.
	 *
	 * @return bool|int
	 */
	public static function getBotId(): int
	{
		$class = self::getClassName();
		if (!$class::BOT_CODE)
		{
			return 0;
		}

		return (int)Option::get(self::MODULE_ID, $class::BOT_CODE."_bot_id", 0);
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

		$optionId = $class::BOT_CODE. '_bot_id';
		if ($id > 0)
		{
			Option::set(self::MODULE_ID, $optionId, $id);
		}
		else
		{
			Option::delete(self::MODULE_ID, ['name' => $optionId]);
		}

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
	public static function onBotDelete($bodId = null)
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

		$avatarUrl = $avatarUrl? \CFile::makeFileArray($avatarUrl): '';

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
			$iconId = \CFile::saveFile(\CFile::makeFileArray($iconId), 'imbot');
		}

		return $iconId;
	}

	/**
	 * @return \Bitrix\ImBot\Bot\Base|string
	 */
	protected static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @return Error
	 */
	public static function getError()
	{
		if (!(self::$lastError instanceof Error))
		{
			self::$lastError = new Error(null, '', '');
		}
		return self::$lastError;
	}

	/**
	 * @param Error $error
	 * @return void
	 */
	public static function addError($error): void
	{
		self::$lastError = $error;
	}

	/**
	 * Tells true if error has occurred.
	 *
	 * @return boolean
	 */
	public static function hasError(): bool
	{
		return
			(self::$lastError instanceof Error)
			&& self::$lastError->error;
	}

}