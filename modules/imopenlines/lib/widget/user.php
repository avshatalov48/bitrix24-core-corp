<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Imopenlines\Widget;

use Bitrix\Main\Application;
use Bitrix\ImOpenLines\BasicError;
use Bitrix\Main\Localization\Loc;

class User
{
	const MODULE_ID = 'imopenlines';
	const EXTERNAL_AUTH_ID = 'imconnector';

	static private $error = null;

	public static function register($userFields = [])
	{
		self::clearError();

		$fields = [];

		$fields['NAME'] = isset($userFields['NAME']) && trim($userFields['NAME'])? $userFields['NAME']: '';
		$fields['LAST_NAME'] = isset($userFields['LAST_NAME'])? trim($userFields['LAST_NAME']): '';

		if (isset($userFields['AVATAR']) && trim($userFields['AVATAR']))
		{
			$userFields['AVATAR'] = self::getPersonalPhoto($userFields['AVATAR']);
			if ($userFields['AVATAR'])
			{
				$fields['PERSONAL_PHOTO'] = $userFields['AVATAR'];
			}
		}
		if (isset($userFields['EMAIL']) && trim($userFields['EMAIL']))
		{
			$fields['EMAIL'] = trim($userFields['EMAIL']);
		}
		if (isset($userFields['PERSONAL_WWW']) && trim($userFields['PERSONAL_WWW']))
		{
			$fields['PERSONAL_WWW'] = trim($userFields['PERSONAL_WWW']);
		}
		if (isset($userFields['PERSONAL_GENDER']) && trim($userFields['PERSONAL_GENDER']))
		{
			$fields['PERSONAL_GENDER'] = $userFields['PERSONAL_GENDER'] == 'F'? 'F': 'M';
		}
		if (isset($userFields['WORK_POSITION']) && trim($userFields['WORK_POSITION']))
		{
			$fields['WORK_POSITION'] = trim($userFields['WORK_POSITION']);
		}
		if (isset($userFields['USER_HASH']) && trim($userFields['USER_HASH']) && preg_match("/^[a-fA-F0-9]{32}$/i", $userFields['USER_HASH']))
		{
			$userCode =  $userFields['USER_HASH'];

			$userData = \Bitrix\Main\UserTable::getList([
				'select' => ['ID', 'EXTERNAL_AUTH_ID'],
				'filter' => ['=XML_ID' => 'livechat|'.$userCode]
			])->fetch();
			if ($userData && $userData['EXTERNAL_AUTH_ID'] == self::EXTERNAL_AUTH_ID)
			{
				return [
					'ID' => $userData['ID'],
					'HASH' => $userCode,
				];
			}
		}
		else
		{
			$userCode = self::getUserCode();
		}

		$fields['LOGIN'] = self::MODULE_ID . '_wgt_' . rand(1000,9999) . randString(5);
		$fields['PASSWORD'] = md5($fields['LOGIN'].'|'.rand(1000,9999).'|'.time());
		$fields['CONFIRM_PASSWORD'] = $fields['PASSWORD'];
		$fields['EXTERNAL_AUTH_ID'] = self::EXTERNAL_AUTH_ID;
		$fields['XML_ID'] =  'livechat|'.$userCode;
		$fields['ACTIVE'] = 'Y';

		$userManager = new \CUser;
		$userId = $userManager->Add($fields);
		if (!$userId)
		{
			$errorCode = '';
			$errorMessage = '';

			global $APPLICATION;
			if ($exception = $APPLICATION->GetException())
			{
				$errorCode = $exception->GetID();
				$errorMessage = $exception->GetString();
			}

			self::setError(__METHOD__, 'USER_REGISTER_ERROR', Loc::getMessage('IMOL_WIDGET_USER_ERROR_CREATE'), ['CODE' => $errorCode, 'MSG' => $errorMessage]);
			return false;
		}

		return [
			'ID' => $userId,
			'HASH' => $userCode,
		];
	}

	public static function get($userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return [];
		}

		$userData = \Bitrix\Main\UserTable::getById($userId)->fetch();

		$avatar = '';
		if ($userData['PERSONAL_PHOTO'])
		{
			$resizedImage = \CFile::ResizeImageGet(
				$userData["PERSONAL_PHOTO"],
				array('width' => 100, 'height' => 100),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if (!empty($resizedImage['src']))
			{
				$avatar = $resizedImage['src'];
			}
		}

		if ($userData['NAME'] || $userData['LAST_NAME'])
		{
			$name = \Bitrix\Im\User::formatFullNameFromDatabase($userData);
			$firstName = \Bitrix\Im\User::formatNameFromDatabase($userData);
		}
		else
		{
			$name = '';
			$firstName = '';
		}

		return [
			'ID' => (int)$userData['ID'],
			'HASH' => mb_substr($userData['XML_ID'], mb_strlen(Auth::AUTH_TYPE) + 1),
			'NAME' => $name,
			'FIRST_NAME' => $firstName,
			'LAST_NAME' => $userData['LAST_NAME'],
			'AVATAR' => $avatar,
			'EMAIL' => $userData['EMAIL'],
			'PHONE' => $userData['PERSONAL_MOBILE'],
			'WWW' => $userData['PERSONAL_WWW'],
			'GENDER' => $userData['PERSONAL_GENDER'],
			'POSITION' => $userData['WORK_POSITION'],
		];
	}

	public static function getDefaultName()
	{
		return Loc::getMessage('IMOL_WIDGET_USER_NAME');
	}

	public static function getPersonalPhoto($avatarUrl = '')
	{
		if (!$avatarUrl)
			return '';

		if (!in_array(mb_strtolower(\GetFileExtension($avatarUrl)), Array('png', 'jpg', 'jpeg', 'gif')))
			return '';

		$recordFile = \CFile::MakeFileArray($avatarUrl);
		if (!\CFile::IsImage($recordFile['name'], $recordFile['type']))
			return '';

		if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0 && $recordFile['size'] < 1000000)
		{
			$recordFile = array_merge($recordFile, array('MODULE_ID' => 'imopenlines'));
		}
		else
		{
			$recordFile = '';
		}

		return $recordFile;
	}

	public static function getUserCode()
	{
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$licence = BX24_HOST_NAME;
		}
		else
		{
			$licence = Application::getInstance()->getLicense()->getKey();
		}

		return md5(time().bitrix_sessid().$licence.uniqid());
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
