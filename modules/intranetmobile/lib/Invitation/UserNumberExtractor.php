<?php

namespace Bitrix\IntranetMobile\Invitation;

use Bitrix\IntranetMobile\Dto\PhoneNumberDto;
use Bitrix\IntranetMobile\Provider\PhoneStatusProvider;
use Bitrix\IntranetMobile\Repository\UserRepository;

class UserNumberExtractor
{
	private array $phoneUsers;
	private array $busyNumbers;

	public function __construct()
	{
		$this->phoneUsers = [];
		$this->busyNumbers = [];
	}

	/**
	 * @param PhoneNumberDto[] $phoneNumbers
	 * @return UserNumberExtractor
	 */
	public function addPhoneUsersByNumbers(array $phoneNumbers): self
	{
		$this->extractBusyNumbers($phoneNumbers);
		$this->extractNotRegisterNumbers($phoneNumbers);

		return $this;
	}

	public function getPhoneUsers(): array
	{
		return $this->phoneUsers;
	}

	/**
	 * @param PhoneNumberDto[] $phoneNumbers
	 * @return void
	 */
	private function extractBusyNumbers(array $phoneNumbers): void
	{
		foreach((new PhoneStatusProvider())->getUsersByPhones($phoneNumbers) as $existingUser)
		{
			$this->phoneUsers[] = $existingUser;
			$this->busyNumbers[] = $existingUser->phoneNumber->formattedPhoneNumber;
		}
	}

	/**
	 * @param PhoneNumberDto[] $phoneNumbers
	 * @return void
	 */
	private function extractNotRegisterNumbers(array $phoneNumbers): void
	{
		foreach ($phoneNumbers as $phoneNumber)
		{
			if (!in_array($phoneNumber->formattedPhoneNumber, $this->busyNumbers))
			{
				$this->phoneUsers[] = UserRepository::createUserPhoneStatusDto([], $phoneNumber);
			}
		}
	}
}