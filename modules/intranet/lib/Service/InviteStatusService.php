<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Dto\Invitation\EmailToUserStatusDto;
use Bitrix\Intranet\Dto\Invitation\EmailToUserStatusDtoCollection;
use Bitrix\Intranet\Dto\Invitation\PhoneToUserStatusDto;
use Bitrix\Intranet\Dto\Invitation\PhoneToUserStatusDtoCollection;
use Bitrix\Intranet\Entity\Collection\EmailCollection;
use Bitrix\Intranet\Entity\Collection\PhoneCollection;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Main\ArgumentException;

class InviteStatusService
{
	private UserRepository $userRepository;

	public function __construct()
	{
		$this->userRepository = ServiceContainer::getInstance()->userRepository();
	}

	/**
	 * @throws ArgumentException
	 */
	public function getInviteStatusesByEmailCollection(EmailCollection $emailCollection): EmailToUserStatusDtoCollection
	{
		$userCollection = $this->userRepository->findUsersByEmails(
			$emailCollection->map(fn(Email $email) => $email->toLogin())
		);
		$emailToUserMap = $this->getEmailToUserMapByCollections($emailCollection, $userCollection);

		return $this->createEmailToUserStatusDtoCollection($emailToUserMap);
	}

	/**
	 * @throws ArgumentException
	 */
	public function getInviteStatusesByPhoneCollection(PhoneCollection $phoneCollection): PhoneToUserStatusDtoCollection
	{
		$userCollection = $this->userRepository->findUsersByPhoneNumbers(
			$phoneCollection->map(fn(Phone $phone) => $phone->defaultFormat())
		);
		$phoneToUserMap = $this->getPhoneToUserMapByCollections($phoneCollection, $userCollection);

		return $this->createPhoneToUserStatusDtoCollection($phoneToUserMap);
	}

	/**
	 * @param EmailCollection $emailCollection
	 * @param UserCollection $userCollection
	 * @return array<array{email: Email, user: User|null}>
	 */
	private function getEmailToUserMapByCollections(
		EmailCollection $emailCollection,
		UserCollection $userCollection
	): array
	{
		return $emailCollection->map(fn(Email $email) => [
			'email' => $email,
			'user' => $userCollection
				->filter(fn(User $user) =>
					mb_strtolower($user->getEmail()) === mb_strtolower($email->toLogin())
					|| mb_strtolower($user->getLogin()) === mb_strtolower($email->toLogin())
				)
				->first(),
		]);
	}

	/**
	 * @param PhoneCollection $phoneCollection
	 * @param UserCollection $userCollection
	 * @return array<array{phone: Phone, user: User|null}>
	 */
	private function getPhoneToUserMapByCollections(
		PhoneCollection $phoneCollection,
		UserCollection $userCollection
	): array
	{
		return $phoneCollection->map(fn(Phone $phone) => [
			'phone' => $phone,
			'user' => $userCollection
				->filter(fn(User $user) => $user->getAuthPhoneNumber() === $phone->defaultFormat())
				->first(),
		]);
	}

	/**
	 * @param $emailToUserMap array<array{email: Email, user: User|null}>
	 * @return EmailToUserStatusDtoCollection
	 * @throws ArgumentException
	 */
	private function createEmailToUserStatusDtoCollection(array $emailToUserMap): EmailToUserStatusDtoCollection
	{
		$emailToUserStatusDtoCollection = new EmailToUserStatusDtoCollection();

		foreach ($emailToUserMap as $emailToUser)
		{
			$userId = null;
			$inviteStatus = InvitationStatus::NOT_REGISTERED->value;

			if (!empty($emailToUser['user']))
			{
				$userId = $emailToUser['user']->getId();
				$inviteStatus = $emailToUser['user']->getInviteStatus()->value;
			}

			$emailToUserStatusDtoCollection->add(new EmailToUserStatusDto(
				email: $emailToUser['email']->toLogin(),
				inviteStatus: $inviteStatus,
				isValidEmail: $emailToUser['email']->isValid(),
				userId: $userId,
			));
		}

		return $emailToUserStatusDtoCollection;
	}

	/**
	 * @param array<array{phone: Phone, user: User|null}> $phoneToUserMap
	 * @return PhoneToUserStatusDtoCollection
	 * @throws ArgumentException
	 */
	private function createPhoneToUserStatusDtoCollection(array $phoneToUserMap): PhoneToUserStatusDtoCollection
	{
		$phoneToUserStatusDtoCollection = new PhoneToUserStatusDtoCollection();

		foreach ($phoneToUserMap as $phoneToUser)
		{
			$userId = null;
			$inviteStatus = InvitationStatus::NOT_REGISTERED->value;

			if (!empty($phoneToUser['user']))
			{
				$userId = $phoneToUser['user']->getId();
				$inviteStatus = $phoneToUser['user']->getInviteStatus()->value;
			}

			$isValidPhoneNumber = $phoneToUser['phone']->isValid();
			$formattedPhone = null;

			if ($isValidPhoneNumber)
			{
				$formattedPhone = $phoneToUser['phone']->defaultFormat();
			}

			$phoneToUserStatusDtoCollection->add(new PhoneToUserStatusDto(
				phone: $phoneToUser['phone']->getRawNumber(),
				countryCode: $phoneToUser['phone']->getCountryCode(),
				inviteStatus: $inviteStatus,
				isValidPhoneNumber: $isValidPhoneNumber,
				formattedPhone: $formattedPhone,
				userId: $userId,
			));
		}

		return $phoneToUserStatusDtoCollection;
	}
}
