<?php

namespace Bitrix\Sign\Service;

use Bitrix\Sign\Item\User;
use CSite;
use CUser;

class UserService
{
	public function getUserById(int $userId): ?User
	{
		return Container::instance()->getUserRepository()->getById($userId);
	}

	public function getUserName(User $user): string
	{
		return CUser::FormatName(
			CSite::GetNameFormat(false),
			[
				'LOGIN' => '',
				'NAME' => $user->name,
				'LAST_NAME' => $user->lastName,
				'SECOND_NAME' => $user->secondName,
			],
			true, false
		);
	}

	public function getUserAvatar(User $user): ?string
	{
		$fileTmp = \CFile::ResizeImageGet(
			$user->personalPhotoId,
			['width' => 42, 'height' => 42],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		if ($fileTmp !== null && isset($fileTmp['src']))
		{
			return $fileTmp['src'];
		}

		return null;
	}

	public function getUserTimezoneOffsetRelativeToServer(int $userId, bool $forced = false): int
	{
		return \CTimeZone::GetOffset($userId, $forced);
	}
}
