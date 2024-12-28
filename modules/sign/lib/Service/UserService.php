<?php

namespace Bitrix\Sign\Service;

use Bitrix\Sign\Item\User;
use Bitrix\Sign\Type\User\Gender;
use CSite;
use CUser;
use Bitrix\Main\UserTable;

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

	public function getUserLanguage(null|int $userId): null|string
	{
		if (!$userId)
		{
			return null;
		}

		$res = UserTable::query()
			->where('ID', $userId)
			->setSelect([
				'NOTIFICATION_LANGUAGE_ID',
			])
			->exec()
			->fetchObject()
		;

		return ($res)
			? $res->getNotificationLanguageId()
			: null
		;
	}

	public function getGender(int $userId): Gender
	{
		$profileGender = UserTable::getById($userId)?->fetchObject()?->getPersonalGender();
		return match ($profileGender)
		{
			'M' => Gender::MALE,
			'F' => Gender::FEMALE,
			default => Gender::DEFAULT,
		};
	}

	public function getUserTimezoneOffsetRelativeToServer(int $userId, bool $forced = false): int
	{
		return \CTimeZone::GetOffset($userId, $forced);
	}
}
