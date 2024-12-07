<?php

namespace Bitrix\IntranetMobile\Repository;

use Bitrix\IntranetMobile\Dto\PhoneNumberDto;
use Bitrix\IntranetMobile\Dto\UserDto;
use Bitrix\Main\Type\DateTime;

class UserRepository
{
	public static function createUserDto(array $user): UserDto
	{
		$installedApps = \Bitrix\Intranet\Util::getAppsInstallationConfig((int)$user['ID']);;

		$installedAppsDto = new \Bitrix\IntranetMobile\Dto\InstalledAppsDto(
			windows: $installedApps['APP_WINDOWS_INSTALLED'],
			linux: $installedApps['APP_LINUX_INSTALLED'],
			mac: $installedApps['APP_MAC_INSTALLED'],
			ios: $installedApps['APP_IOS_INSTALLED'],
			android: $installedApps['APP_ANDROID_INSTALLED'],
		);

		try
		{
			$timestamp = (new DateTime($user['DATE_REGISTER']))->getTimestamp();
		}
		catch (\Exception)
		{
			$timestamp = null;
		}

		$userId = (int)$user['ID'];
		$phoneNumber = !empty($user['PERSONAL_MOBILE']) ? new PhoneNumberDto($user['PERSONAL_MOBILE']) : null;

		return new UserDto(
			id: $userId,
			department: $user['UF_DEPARTMENT'],
			isExtranetUser: \Bitrix\Intranet\Util::isExtranetUser($userId),
			installedApps: $installedAppsDto,
			employeeStatus: self::getEmployeeStatus($user),
			dateRegister: $timestamp,
			actions: $user['ACTIONS'],
			email: $user['EMAIL'],
			phoneNumber: $phoneNumber,
		);
	}

	public static function createUserPhoneStatusDto(array $user, PhoneNumberDto $phoneNumber): UserDto
	{
		$userId = $user['ID'] ? (int)$user['ID'] : 0;
		$employeeStatus = $userId ? self::getEmployeeStatus($user) : UserDto::NOT_REGISTERED;

		return new UserDto(
			id: $userId,
			employeeStatus: $employeeStatus,
			phoneNumber: $phoneNumber,
		);
	}

	private static function getEmployeeStatus(array $user): int
	{
		if ($user['ACTIVE'] === 'Y')
		{
			if ($user['CONFIRM_CODE'] === null || $user['CONFIRM_CODE'] === '')
			{
				return UserDto::ACTIVE;
			}

			return UserDto::INVITED;
		}

		if ($user['CONFIRM_CODE'] === null || $user['CONFIRM_CODE'] === '')
		{
			return UserDto::FIRED;
		}

		return UserDto::INVITE_AWAITING_APPROVE;
	}
}