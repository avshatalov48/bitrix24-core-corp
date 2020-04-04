<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Imopenlines\Widget;

use Bitrix\ImOpenLines\BasicError;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\Localization\Loc;

class Dialog
{
	const MODULE_ID = 'imopenlines';
	const EXTERNAL_AUTH_ID = 'imconnector';

	const VOTE_NONE = 'none';
	const VOTE_LIKE = 'like';
	const VOTE_DISLIKE = 'dislike';

	static private $error = null;

	public static function register($userId, $configId)
	{
		global $USER, $APPLICATION;

		self::clearError();

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			self::setError(__METHOD__, 'IM_NOT_FOUND', Loc::getMessage('IMOL_WIDGET_CONFIG_IM_NOT_FOUND'));
			return false;
		}

		$chat = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID', 'ENTITY_DATA_1', 'ENTITY_DATA_2', 'ENTITY_DATA_3'],
			'filter' => array(
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $configId.'|'.$userId
			),
			'limit' => 1
		))->fetch();
		if($chat)
		{
			return [
				'CHAT_ID' => $chat['ID']
			];
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
			'CHAT_ID' => $id
		];
	}

	public static function get($userId, $configId)
	{
		$userId = intval($userId);
		$configId = intval($configId);

		self::clearError();

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			self::setError(__METHOD__, 'IM_NOT_FOUND', Loc::getMessage('IMOL_WIDGET_CONFIG_IM_NOT_FOUND'));
			return false;
		}

		$config = \Bitrix\Imopenlines\Model\ConfigTable::getById($configId)->fetch();
		if (!$config)
		{
			self::setError(__METHOD__, 'CONFIG_ERROR', Loc::getMessage('IMOL_WIDGET_CHAT_ERROR_CONFIG_NOT_FOUND'));
			return false;
		}

		$chat = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID', 'DISK_FOLDER_ID', 'ENTITY_DATA_1', 'ENTITY_DATA_2', 'ENTITY_DATA_3'],
			'filter' => array(
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $configId.'|'.$userId
			),
			'limit' => 1
		))->fetch();
		if (!$chat)
		{
			self::setError(__METHOD__, 'CHAT_ERROR', Loc::getMessage('IMOL_WIDGET_CHAT_NOT_FOUND'), []);
			return false;
		}

		$chatId = $chat['ID'];
		$diskFolderId = $chat['DISK_FOLDER_ID'];
		$sessionId = 0;
		$sessionClosed = true;
		$sessionStatus = Session::ACTION_NONE;
		$userVote = self::VOTE_NONE;

		$operator = [
			'ID' => 0,
			'NAME' => '',
			'FIRST_NAME' => '',
			'LAST_NAME' => '',
			'WORK_POSITION' => '',
			'GENDER' => 'F',
			'AVATAR' => '',
			'AVATAR_ID' => 0,
			'ONLINE' => false
		];

		$sessionData =\Bitrix\Imopenlines\Model\SessionTable::getList(array(
			'select' => [
				'ID',
				'CLOSED',
				'VOTE',
				'STATUS',
				'CHAT_OPERATOR_ID' => 'CHAT.AUTHOR_ID',
				'OPERATOR_AVATAR' => 'CHAT.AUTHOR.PERSONAL_PHOTO',
				'OPERATOR_ONLINE' => 'CHAT.AUTHOR.IS_ONLINE'
			],
			'filter' => array(
				'=USER_CODE' => 'livechat|'.$configId.'|'.$chatId.'|'.$userId
			),
			'order' => array('ID' => 'DESC'),
			'limit' => 1
		))->fetch();
		if ($sessionData)
		{
			$sessionId = $sessionData['ID'];
			$sessionStatus = (int)$sessionData['STATUS'];

			$sessionData['VOTE'] = (int)$sessionData['VOTE'];

			if ($sessionData['VOTE'] === \Bitrix\Imopenlines\Session::VOTE_LIKE)
			{
				$userVote = self::VOTE_LIKE;
			}
			else if ($sessionData['VOTE'] === \Bitrix\Imopenlines\Session::VOTE_DISLIKE)
			{
				$userVote = self::VOTE_DISLIKE;
			}

			$sessionClosed = $sessionData['CLOSED'] == 'Y';

			if ($sessionData['CHAT_OPERATOR_ID'])
			{
				$operator = \Bitrix\ImOpenLines\Queue::getUserData($configId, $sessionData['CHAT_OPERATOR_ID']);
			}
		}

		$userConsent = false;
		if ($config['AGREEMENT_MESSAGE'] == 'Y')
		{
			$userConsent = \Bitrix\Main\UserConsent\Consent::getByContext(intval($config['AGREEMENT_ID']), 'imopenlines/livechat', $chatId);
		}

		return [
			'DIALOG_ID' => 'chat'.$chatId,
			'CHAT_ID' => (int)$chatId,
			'DISK_FOLDER_ID' => (int)$diskFolderId,
			'SESSION_ID' => (int)$sessionId,
			'SESSION_CLOSE' => $sessionClosed,
			'SESSION_STATUS' => $sessionStatus,
			'USER_VOTE' => $userVote,
			'USER_CONSENT' => (bool)$userConsent,
			'OPERATOR' => $operator,
		];
	}

	/**
	 * @return BasicError
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
		static::$error = new BasicError($method, $code, $msg, $params);
		return true;
	}

	private static function clearError()
	{
		static::$error = new BasicError(null, '', '');
		return true;
	}
}
