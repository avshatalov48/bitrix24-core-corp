<?php

namespace Bitrix\Tasks\Flow\User;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Util\User as TasksUserUtil;

final class Tool
{
	public function resizePhoto(int $photo, int $width, int $height): array
	{
		$preview = \CFile::resizeImageGet(
			$photo,
			['width' => $width, 'height' => $height],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		return $preview ?: [];
	}

	// todo typify $userData
	public function formatName(array $userData): string
	{
		return TasksUserUtil::formatName($userData);
	}

	public function getPathToProfile(int $userId): string
	{
		return str_replace(
			['#user_id#'],
			$userId,
			Option::get('main', 'TOOLTIP_PATH_TO_USER', false, SITE_ID)
		);
	}
}
