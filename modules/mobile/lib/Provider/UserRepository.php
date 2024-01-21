<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Main\UserTable;

final class UserRepository
{
	/**
	 * @param int[] $userIds
	 * @return UserDTO[]
	 */
	public static function getByIds(array $userIds): array
	{
		$userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

		if (empty($userIds))
		{
			return [];
		}

		$usersData = [];
		$userResult = UserTable::getList([
			'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION'],
			'filter' => ['ID' => $userIds],
		]);
		while ($user = $userResult->fetch())
		{
			$userId = (int)$user['ID'];

			$userDTO = new UserDTO();
			$userDTO->id = $userId;
			$userDTO->login = $user['LOGIN'];
			$userDTO->name = $user['NAME'];
			$userDTO->lastName = $user['LAST_NAME'];
			$userDTO->secondName = $user['SECOND_NAME'];

			$userFullName = self::getUserFullName($user);
			$userDTO->fullName = $userFullName;

			$userDTO->workPosition = $user['WORK_POSITION'];
			$userDTO->link = "/company/personal/user/{$userId}/";

			if (!empty($user['PERSONAL_PHOTO']))
			{
				[$originalAvatar, $resizedAvatar100] = self::getAvatar($user['PERSONAL_PHOTO']);
				$userDTO->avatarSizeOriginal = $originalAvatar;
				$userDTO->avatarSize100 = $resizedAvatar100;
			}

			$usersData[] = $userDTO;
		}

		return $usersData;
	}

	private static function getUserFullName(array $user): string
	{
		return \CUser::FormatName(
			\CSite::GetNameFormat(),
			[
				'LOGIN' => $user['LOGIN'],
				'NAME' => $user['NAME'],
				'LAST_NAME' => $user['LAST_NAME'],
				'SECOND_NAME' => $user['SECOND_NAME'],
			],
			true,
			false
		);
	}

	private static function getAvatar(int $avatarId): array
	{
		static $cache = [];

		if (!isset($cache[$avatarId]))
		{
			$src = [];

			if ($avatarId > 0)
			{
				$originalFile = \CFile::getFileArray($avatarId);

				if ($originalFile !== false)
				{
					$resizedFile = \CFile::resizeImageGet(
						$originalFile,
						['width' => 100, 'height' => 100],
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true,
					);
					$src = [
						$originalFile['SRC'],
						$resizedFile['src'],
					];
				}

				$cache[$avatarId] = $src;
			}
		}

		return $cache[$avatarId];
	}
}
