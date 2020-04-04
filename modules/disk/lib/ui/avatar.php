<?php

namespace Bitrix\Disk\Ui;

final class Avatar
{
	public static function getPerson($avatarId, $width = 58, $height = 58)
	{
		return self::getSrc($avatarId, $width, $height)?: self::getDefaultPerson();
	}

	public static function getGroup($avatarId, $width = 58, $height = 58)
	{
		return self::getSrc($avatarId, $width, $height)?: self::getDefaultGroup();
	}

	public static function getDefaultGroup()
	{
		return '/bitrix/images/disk/default_groupe.png';
	}

	public static function getDefaultPerson()
	{
		return '/bitrix/images/disk/default_avatar.png';
	}

	public static function getSrc($avatarId, $width = 58, $height = 58)
	{
		static $cache = array();

		if(empty($avatarId))
		{
			return null;
		}

		$avatarId = (int) $avatarId;
		$key = $avatarId . " $width $height";

		if (!isset($cache[$key]))
		{
			$src = false;
			if ($avatarId > 0)
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$imageFile = \CFile::getFileArray($avatarId);
				if ($imageFile !== false)
				{
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					$fileTmp = \CFile::resizeImageGet(
						$imageFile,
						array("width" => $width, "height" => $height),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $fileTmp["src"];
				}

				$cache[$key] = $src;
			}
		}

		return $cache[$key];
	}
}