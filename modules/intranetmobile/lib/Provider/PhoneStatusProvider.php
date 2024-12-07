<?php

namespace Bitrix\IntranetMobile\Provider;

use Bitrix\Intranet\User\UserManager;
use Bitrix\IntranetMobile\Dto\PhoneNumberDto;
use Bitrix\IntranetMobile\Repository\UserRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

final class PhoneStatusProvider
{
	private UserManager $userManager;

	public function __construct()
	{
		$this->userManager = new UserManager('IntranetMobile/PhoneStatusProvider', []);
	}


	/**
	 * @param PhoneNumberDto[] $phoneNumbers
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUsersByPhones(array $phoneNumbers): array
	{
		$phoneNumbersFilter = [];

		foreach ($phoneNumbers as $phoneNumber)
		{
			$formattedPhoneNumber = $phoneNumber->formattedPhoneNumber;

			if (!empty($formattedPhoneNumber))
			{
				$phoneNumbersFilter[] = $phoneNumber->formattedPhoneNumber;
			}
		}

		if (empty($phoneNumbersFilter))
		{
			return [];
		}

		$params = [
			'select' => $this->getSelect(),
			'filter' => ['PHONE_MOBILE' => $phoneNumbersFilter],
		];

		return $this->convertUsers($this->userManager->getList($params), $phoneNumbers);
	}

	/**
	 * @param array $users
	 * @param PhoneNumberDto[] $phoneNumbers
	 * @return array
	 */
	private function convertUsers(array $users, array $phoneNumbers): array
	{
		$usersInfo = [];

		foreach ($users as $user)
		{
			$userData = $user['data'];

			if (isset($phoneNumbers[$userData['PERSONAL_MOBILE_FORMATTED']]))
			{
				$usersInfo[] = UserRepository::createUserPhoneStatusDto($userData, $phoneNumbers[$userData['PERSONAL_MOBILE_FORMATTED']]);
			}
		}

		return $usersInfo;
	}

	private function getSelect(): array
	{
		return [
			'ID',
			'ACTIVE',
			'CONFIRM_CODE',
			'PERSONAL_MOBILE_FORMATTED',
		];
	}
}