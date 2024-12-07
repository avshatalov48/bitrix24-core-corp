<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Main\UserTable;
use Bitrix\Main\Loader;

class UserRepository
{
	public static function getDefaultFieldsForSelect(): array
	{
		return [
			'ID',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'LOGIN',
			'PERSONAL_PHOTO',
			'WORK_POSITION',
			'EMAIL',
			'WORK_PHONE',
		];
	}

	/**
	 * @param int[] $userIds
	 * @return CommonUserDto[]
	 */
	public static function getByIds(array $userIds, ?array $selectByFields = null): array
	{
		$userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

		if (empty($userIds))
		{
			return [];
		}

		$selectByFields ??= static::getDefaultFieldsForSelect();

		$usersData = [];
		$userResult = UserTable::getList([
			'select' => $selectByFields,
			'filter' => ['ID' => $userIds],
		]);
		while ($user = $userResult->fetch())
		{
			$usersData[] = self::createUserDto($user);
		}

		return $usersData;
	}

	static private function isAdmin($userId)
	{
		if (
			Loader::IncludeModule('bitrix24')
			&& class_exists('CBitrix24')
			&& method_exists('CBitrix24', 'IsPortalAdmin')
		)
		{
			return \CBitrix24::IsPortalAdmin($userId);
		}

		$userGroups = \CUser::GetUserGroup($userId);

		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($userGroups);

		return in_array(1, $userGroups, true);
	}

	public static function createUserDto(array $user): CommonUserDto
	{
		$userId = (int)$user['ID'];
		$originalAvatar = null;
		$resizedAvatar100 = null;

		if (!empty($user['PERSONAL_PHOTO']))
		{
			[$originalAvatar, $resizedAvatar100] = self::getAvatar($user['PERSONAL_PHOTO']);
		}

		// todo: remove mock
		$user['actions'] = ['delete', 'fire'];

		return new CommonUserDto(
			id: $userId,
			login: $user['LOGIN'] ?? null,
			name: $user['NAME'] ?? null,
			lastName: $user['LAST_NAME'] ?? null,
			secondName: $user['SECOND_NAME'] ?? null,
			fullName: self::getUserFullName($user),
			email: $user['EMAIL'] ?? null,
			workPhone: $user['WORK_PHONE'] ?? null,
			workPosition: $user['WORK_POSITION'] ?? null,
			link: "/company/personal/user/$userId/",
			avatarSizeOriginal: $originalAvatar,
			avatarSize100: $resizedAvatar100,
			isAdmin: self::isAdmin($userId),
			personalMobile: $user['PERSONAL_MOBILE'] ?? null,
			personalPhone: $user['PERSONAL_PHONE'] ?? null,
		);
	}

	private static function getUserFullName(array $user): string
	{
		return \CUser::FormatName(
			\CSite::GetNameFormat(),
			[
				'LOGIN' => $user['LOGIN'] ?? '',
				'NAME' => $user['NAME'] ?? '',
				'LAST_NAME' => $user['LAST_NAME'] ?? '',
				'SECOND_NAME' => $user['SECOND_NAME'] ?? '',
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

	private static function is2faEnabled()
	{
		return Loader::includeModule("security") && \Bitrix\Security\Mfa\Otp::isOtpEnabled();
	}
}
