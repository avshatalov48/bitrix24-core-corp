<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Service\UseCase\Invitation;

use Bitrix\Intranet;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Dto;
use Bitrix\Intranet\Command;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\Type;
use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\EmailInvitation;
use Bitrix\Intranet\Entity\Type\InvitationsContainer;
use Bitrix\Intranet\Service;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Intranet\Entity\Type\PhoneInvitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Intranet\Contract;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class BulkInviteUsersToCollabAndPortal
{
	private Contract\Repository\UserRepository $userRepository;
	private Service\InviteService $inviteService;
	private bool $currentUserIsIntranet;

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct()
	{
		$this->userRepository = ServiceContainer::getInstance()->userRepository();
		$this->inviteService = ServiceContainer::getInstance()->inviteService();
		$this->currentUserIsIntranet = (new Intranet\User((int)CurrentUser::get()->getId()))->isIntranet();
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function execute(
		int $collabId,
		Dto\Invitation\UserInvitationDtoCollection $userInvitationDtoCollection
	): Result
	{
		[
			$userInvitationDtoCollectionByEmail,
			$userInvitationDtoCollectionByPhone
		] = $this->splitUserInvitationDtoCollection($userInvitationDtoCollection);

		$userCollectionRegisteredByEmail = $this->getRegisteredUserCollectionByEmails(
			$userInvitationDtoCollectionByEmail->getEmails()
		);
		$userCollectionRegisteredByPhone = $this->getRegisteredUserCollectionByPhones(
			$userInvitationDtoCollectionByPhone->getPhones()
		);

		$invitationToPortalAndGroupCollection = new Type\Collection\InvitationCollection();
		$userCollectionForInviteOnlyToGroup = new UserCollection();

		$this->distributeEmailDtoCollection(
			$userInvitationDtoCollectionByEmail,
			$userCollectionRegisteredByEmail,
			$invitationToPortalAndGroupCollection,
			$userCollectionForInviteOnlyToGroup
		);
		$this->distributePhoneDtoCollection(
			$userInvitationDtoCollectionByPhone,
			$userCollectionRegisteredByPhone,
			$invitationToPortalAndGroupCollection,
			$userCollectionForInviteOnlyToGroup
		);

		return $this->executeInvite(
			$collabId,
			$invitationToPortalAndGroupCollection,
			$userCollectionForInviteOnlyToGroup
		);
	}

	/**
	 * @param Dto\Invitation\UserInvitationDtoCollection $collection
	 * @return array{0: Dto\Invitation\UserInvitationDtoCollection, 1: Dto\Invitation\UserInvitationDtoCollection}
	 * @throws ArgumentException
	 */
	private function splitUserInvitationDtoCollection(Dto\Invitation\UserInvitationDtoCollection $collection): array
	{
		$collectionForEmailInvitation = new Dto\Invitation\UserInvitationDtoCollection();
		$collectionForPhoneInvitation = new Dto\Invitation\UserInvitationDtoCollection();

		$collection->forEach(
			function (
				Dto\Invitation\UserInvitationDto $dto
			) use ($collectionForEmailInvitation, $collectionForPhoneInvitation) {
				if ($dto->email instanceof Email)
				{
					$collectionForEmailInvitation->add($dto);
				}
				elseif ($dto->phone instanceof Phone)
				{
					$collectionForPhoneInvitation->add($dto);
				}
			}
		);

		return [$collectionForEmailInvitation, $collectionForPhoneInvitation];
	}

	private function distributeEmailDtoCollection(
		Dto\Invitation\UserInvitationDtoCollection $userInvitationDtoCollectionByEmail,
		UserCollection $userCollectionRegisteredByEmail,
		Type\Collection\InvitationCollection $invitationToPortalAndGroupCollection,
		UserCollection $userCollectionForInviteOnlyToGroup,
	): void
	{
		$userInvitationDtoCollectionByEmail->forEach(
			function (
				Dto\Invitation\UserInvitationDto $userInvitationDto
			) use ($userCollectionRegisteredByEmail, $invitationToPortalAndGroupCollection, $userCollectionForInviteOnlyToGroup) {
				$foundUser = null;

				$userCollectionRegisteredByEmail->forEach(
					function (User $user) use ($userInvitationDto, &$foundUser) {
						if (mb_strtolower($user->getEmail()) === mb_strtolower($userInvitationDto->email->toLogin()))
						{
							$userInvitationDto->invitationStatus = $user->getInviteStatus();
							$foundUser = $user;
						}
					}
				);

				if (!$foundUser || $this->shouldInviteByUser($foundUser))
				{
					$invitationToPortalAndGroupCollection->add(new EmailInvitation(
						$userInvitationDto->email->toLogin(),
						$userInvitationDto->name,
						$userInvitationDto->lastName
					));
				}
				else
				{
					$userCollectionForInviteOnlyToGroup->add($foundUser);
				}
			}
		);
	}

	private function distributePhoneDtoCollection(
		Dto\Invitation\UserInvitationDtoCollection $userInvitationDtoCollectionByPhone,
		UserCollection $userCollectionRegisteredByPhone,
		Type\Collection\InvitationCollection $invitationToPortalAndGroupCollection,
		UserCollection $userCollectionForInviteOnlyToGroup,
	): void
	{
		$userInvitationDtoCollectionByPhone->forEach(
			function (
				Dto\Invitation\UserInvitationDto $userInvitationDto
			) use ($userCollectionRegisteredByPhone, $invitationToPortalAndGroupCollection, $userCollectionForInviteOnlyToGroup) {
				$foundUser = null;

				$userCollectionRegisteredByPhone->forEach(
					function (User $user) use ($userInvitationDto, &$foundUser) {
						if ($user->getAuthPhoneNumber() === $userInvitationDto->phone->defaultFormat())
						{
							$userInvitationDto->invitationStatus = $user->getInviteStatus();
							$foundUser = $user;
						}
					}
				);

				if (!$foundUser || $this->shouldInviteByUser($foundUser))
				{
					$invitationToPortalAndGroupCollection->add(new PhoneInvitation(
						$userInvitationDto->phone->defaultFormat(),
						$userInvitationDto->name,
						$userInvitationDto->lastName
					));
				}
				else
				{
					$userCollectionForInviteOnlyToGroup->add($foundUser);
				}
			}
		);
	}

	private function shouldInviteByUser(User $user): bool
	{
		$status = $user->getInviteStatus();

		if (in_array($user->getExternalAuthId(), ['email', 'shop']))
		{
			return true;
		}

		if (!$this->currentUserIsIntranet)
		{
			if ($user->isIntranet())
			{
				return false;
			}

			return $status === InvitationStatus::INVITED || $status === InvitationStatus::NOT_REGISTERED;
		}

		if ($user->isIntranet())
		{
			return $status === InvitationStatus::NOT_REGISTERED;
		}

		return $status === InvitationStatus::INVITED || $status === InvitationStatus::NOT_REGISTERED;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function executeInvite(
		int $collabId,
		Type\Collection\InvitationCollection $invitationToPortalAndGroupCollection,
		UserCollection $userCollectionForInviteOnlyToGroup,
	): Result
	{
		$usersInvitedPortalAndGroup = [];
		$usersInvitedOnlyToGroup = [];

		if (!$invitationToPortalAndGroupCollection->empty())
		{
			$invitationContainer = new InvitationsContainer($invitationToPortalAndGroupCollection);
			$inviteToPortalAndGroupResult = $this->inviteService->inviteUsersToGroup($collabId, $invitationContainer);

			if (!$inviteToPortalAndGroupResult->isSuccess())
			{
				return $inviteToPortalAndGroupResult;
			}

			$usersInvitedPortalAndGroup = $inviteToPortalAndGroupResult->getData();
		}

		if (!$userCollectionForInviteOnlyToGroup->empty())
		{
			$commandToInviteOnlyToGroup = new Command\Invitation\InviteUserCollectionToGroupCommand(
				groupId: $collabId,
				userCollection: $userCollectionForInviteOnlyToGroup,
			);
			$commandInviteOnlyToGroupResult = $commandToInviteOnlyToGroup->execute();

			if (!$commandInviteOnlyToGroupResult->isSuccess())
			{
				return $commandInviteOnlyToGroupResult;
			}

			$usersInvitedOnlyToGroup = $commandInviteOnlyToGroupResult->getData();
		}

		$allUsersInvitedCollection = new UserCollection(...$usersInvitedPortalAndGroup, ...$usersInvitedOnlyToGroup);

		return (new Result())->setData($allUsersInvitedCollection->getIds());
	}

	/**
	 * @param array<string> $emails
	 * @return UserCollection
	 */
	private function getRegisteredUserCollectionByEmails(array $emails): UserCollection
	{
		if (empty($emails))
		{
			return new UserCollection();
		}

		if (Option::get('main', 'new_user_email_uniq_check', 'N') === 'Y')
		{
			$userCollection = $this->userRepository->findUsersByLoginsAndEmails($emails);
		}
		else
		{
			$userCollection = $this->userRepository->findUsersByLogins($emails);
		}

		return $this->filterRegisteredUserCollectionByExternalAuthId($userCollection);
	}

	/**
	 * @param array<string> $phones
	 * @return UserCollection
	 */
	private function getRegisteredUserCollectionByPhones(array $phones): UserCollection
	{
		if (empty($phones))
		{
			return new UserCollection();
		}

		if (Option::get('main', 'new_user_email_uniq_check', 'N') === 'Y')
		{
			$userCollection = $this->userRepository->findUsersByLoginsAndPhoneNumbers($phones);
		}
		else
		{
			$userCollection = $this->userRepository->findUsersByLogins($phones);
		}

		return $this->filterRegisteredUserCollectionByExternalAuthId($userCollection);
	}

	private function filterRegisteredUserCollectionByExternalAuthId(UserCollection $userCollection): UserCollection
	{
		return $userCollection->filter(
			function (User $user) {
				return !in_array(
					$user->getExternalAuthId(),
					array_diff(UserTable::getExternalUserTypes(), ['email', 'shop']),
					true
				);
			}
		);
	}
}
