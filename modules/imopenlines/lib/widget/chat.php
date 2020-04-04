<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Imopenlines\Widget;

use Bitrix\ImOpenLines\Error;
use Bitrix\Main\Localization\Loc;

class Chat
{
	const MODULE_ID = 'imopenlines';
	const EXTERNAL_AUTH_ID = 'imconnector';

	static private $error = null;

	public static function get($userId, $configId)
	{
		global $USER, $APPLICATION;

		self::clearError();

		$orm = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID', 'ENTITY_DATA_1', 'ENTITY_DATA_2', 'ENTITY_DATA_3'],
			'filter' => array(
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $configId.'|'.$userId
			),
			'limit' => 1
		));
		if($chat = $orm->fetch())
		{
			return $chat;
		}

		if ($userId == $USER->GetID())
		{
			$avatarId = $USER->GetParam('PERSONAL_PHOTO');
		}
		else
		{
			$user = \Bitrix\Main\UserTable::getById($userId)->fetch();
			if ($user)
			{
				$avatarId = $user['PERSONAL_PHOTO'];
			}
			else
			{
				self::setError(__METHOD__, 'USER_ERROR', Loc::getMessage('IMOL_WIDGET_CHAT_ERROR_USER_NOT_FOUND'));
				return false;
			}
		}

		$config = \Bitrix\Imopenlines\Model\ConfigTable::getById($configId)->fetch();
		if (!$config)
		{
			self::setError(__METHOD__, 'CONFIG_ERROR', Loc::getMessage('IMOL_WIDGET_CHAT_ERROR_CONFIG_NOT_FOUND'));
			return false;
		}

		$userName = \Bitrix\Im\User::getInstance($userId)->getFullName(false);
		$chatColorCode = \Bitrix\Im\Color::getCodeByNumber($userId);
		if (\Bitrix\Im\User::getInstance($userId)->getGender() == 'M')
		{
			$replaceColor = \Bitrix\Im\Color::getReplaceColors();
			if (isset($replaceColor[$chatColorCode]))
			{
				$chatColorCode = $replaceColor[$chatColorCode];
			}
		}

		$addChat['TITLE'] = Loc::getMessage('IMOL_WIDGET_CHAT_NAME', Array(
			"#USER_NAME#" => $userName,
			"#LINE_NAME#" => $config['LINE_NAME']
		));

		$addChat['TYPE'] = IM_MESSAGE_CHAT;
		$addChat['COLOR'] = $chatColorCode;
		$addChat['AVATAR_ID'] = $avatarId;
		$addChat['ENTITY_TYPE'] = 'LIVECHAT';
		$addChat['ENTITY_ID'] = $configId.'|'.$userId;
		$addChat['SKIP_ADD_MESSAGE'] = 'Y';
		$addChat['AUTHOR_ID'] = $userId;
		$addChat['USERS'] =[$userId];

		$chat = new \CIMChat(0);
		$id = $chat->Add($addChat);
		if (!$id)
		{
			$errorCode = '';
			$errorMessage = '';

			if ($exception = $APPLICATION->GetException())
			{
				$errorCode = $exception->GetID();
				$errorMessage = $exception->GetString();
			}

			self::setError(__METHOD__, 'CHAT_ERROR', Loc::getMessage('IMOL_WIDGET_CHAT_ERROR_CREATE'), ['CODE' => $errorCode, 'MSG' => $errorMessage]);
			return false;
		}

		return [
			'ID' => $id,
			'ENTITY_DATA_1' => '',
			'ENTITY_DATA_2' => '',
			'ENTITY_DATA_3' => ''
		];
	}

	/**
	 * @return Error
	 */
	public static function getError()
	{
		if (is_null(static::$error))
		{
			self::clearError();
		}

		return static::$error;
	}

	/**
	 * @param $method
	 * @param $code
	 * @param $msg
	 * @param array $params
	 * @return bool
	 */
	private static function setError($method, $code, $msg, $params = Array())
	{
		static::$error = new Error($method, $code, $msg, $params);
		return true;
	}

	private static function clearError()
	{
		static::$error = new Error(null, '', '');
		return true;
	}
}
