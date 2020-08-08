<?php
namespace Bitrix\Timeman\Helper;

use Bitrix\Main\Config\Option;

class UserHelper
{
	const TYPE_EMPLOYEE = 'employee';
	protected static $instance;
	private static $currentUserId;

	protected function __construct()
	{
	}

	/**
	 * @return UserHelper
	 */
	public static function getInstance()
	{
		if (!static::$instance)
		{
			static::$instance = new static();
		}
		return static::$instance;
	}

	public static function getCurrentUserId()
	{
		if (static::$currentUserId)
		{
			return static::$currentUserId;
		}
		global $USER;
		$userId = 0;
		if ($USER && is_object($USER) && $USER->isAuthorized() && $USER->getId() > 0)
		{
			$userId = $USER->getId();
		}
		return $userId;
	}

	public function getFormattedName($userFields)
	{
		return \CUser::formatName(
			\CSite::getNameFormat(false),
			[
				'USER_ID' => $userFields['ID'],
				'NAME' => $userFields['NAME'],
				'LAST_NAME' => $userFields['LAST_NAME'],
				'SECOND_NAME' => $userFields['SECOND_NAME'],
				'LOGIN' => $userFields['LOGIN'],
				'EMAIL' => $userFields['EMAIL'],
			],
			true,
			false
		);
	}

	public function getPhotoPath($photoId = null, $width = 100, $height = 100)
	{
		if ($photoId > 0)
		{
			$photo = \CIntranetUtils::initImage($photoId, $width, $height, BX_RESIZE_IMAGE_EXACT);
			$path = $photo['CACHE']['src'];
		}
		else
		{
			$path = null;
		}

		return $path;
	}

	public function getManagerIds($userId)
	{
		return \CTimeMan::getUserManagers($userId);
	}

	public function getProfilePath($id)
	{
		$url = Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/');

		return str_replace(['#ID#', '#USER_ID#'], $id, $url);
	}
}