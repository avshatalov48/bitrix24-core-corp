<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\UserTable;

class UserRepository
{
	static private ?array $intranetUsers = null;
	static private array $usersData = [];

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
			'UF_DEPARTMENT',
		];
	}

	/**
	 * @param int[] $userIds
	 * @return CommonUserDto[]
	 */
	public static function getByIds(array $userIds): array
	{
		$userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

		if (empty($userIds))
		{
			return [];
		}

		$userIdsToFetch = array_diff($userIds, array_keys(static::$usersData));
		if (!empty($userIdsToFetch))
		{
			$userResult = UserTable::getList([
				'select' => static::getDefaultFieldsForSelect(),
				'filter' => ['ID' => $userIdsToFetch],
			]);
			while ($user = $userResult->fetch())
			{
				static::$usersData[(int)$user['ID']] = $user;
			}
		}

		$users = [];
		foreach ($userIds as $userId)
		{
			if (isset(static::$usersData[$userId]))
			{
				$users[] = static::createUserDto(static::$usersData[$userId]);
			}
		}

		return $users;
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
		$mobileContext = new \Bitrix\Mobile\Context();

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
			isCollaber: self::isCollaber($userId),
			isExtranet: self::isExtranet($userId),
			personalMobile: $user['PERSONAL_MOBILE'] ?? null,
			personalPhone: $user['PERSONAL_PHONE'] ?? null,
		);
	}

	private static function isCollaber(int $userId): bool
	{
		if (!Loader::includeModule('extranet') || $userId <= 0)
		{
			return false;
		}

		$container = class_exists(ServiceContainer::class) ? ServiceContainer::getInstance() : null;

		return $container?->getCollaberService()?->isCollaberById($userId) ?? false;
	}

	private static function isExtranet(int $userId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		if (!is_array(self::$intranetUsers))
		{
			self::$intranetUsers = \CExtranet::GetIntranetUsers();
			Collection::normalizeArrayValuesByInt(self::$intranetUsers);
		}

		return !in_array($userId, self::$intranetUsers, true);
	}

	private static function isAdmin($userId): bool
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
		Collection::normalizeArrayValuesByInt($userGroups);

		return in_array(1, $userGroups, true);
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
