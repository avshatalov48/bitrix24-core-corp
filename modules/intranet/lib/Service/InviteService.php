<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Command\AddToGroupCommand;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\EmailCollection;
use Bitrix\Intranet\Entity\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\Collection\PhoneCollection;
use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\InvitationsContainer;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Intranet\Entity\Type\PhoneInvitation;
use Bitrix\Intranet\Entity\Invitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Contract\Repository\UserRepository as UserRepositoryContract;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Error;
use Bitrix\Intranet\Invitation\Register;

class InviteService
{
	private UserRepositoryContract $userRepository;

	public function __construct()
	{
		$this->userRepository = ServiceContainer::getInstance()->userRepository();
	}

	public function inviteUser(User $user, InvitationType $type, string $formType): Invitation
	{
		$invitationRepository = ServiceContainer::getInstance()->invitationRepository();

		return $invitationRepository->save(new Invitation(
			userId: $user->getId(),
			initialized: false,
			isMass: $formType === 'mass',
			isDepartment: $formType === 'group',
			isIntegrator: false,
			isRegister: false,
			id: null,
			originatorId: CurrentUser::get()->getId(),
			type: $type,
		));
	}

	public function inviteUsersToGroup(int $groupId, InvitationsContainer $inviteData): Result
	{
		$result = new Result();

		if (!Loader::includeModule('socialnetwork'))
		{
			throw new SystemException('Module "socialnetwork" is not installed');
		}

		if (\CSocNetUserToGroup::GetById($groupId) === false)
		{
			$result->addError(new Error('', 'socnetgroup_not_found'));

			return $result;
		}

		$result = Register::inviteNewUsers(
			SITE_ID,
			$inviteData->backwardsCompatibility(),
			'email'
		);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$userCollection = $this->userRepository->findUsersByIds($result->getData());
//		\CSocNetUserToGroup::addUniqueUsersToGroup($groupId, $result->getData());
		(new AddToGroupCommand($groupId, $userCollection))->execute();

		$result->setData($userCollection->all());

		return $result;
	}

	public function checkInvitationsStatusByEmailCollection(EmailCollection $emailCollection): Result
	{
		$userCollection = $this->userRepository->findUsersByEmails(
			$emailCollection->map(fn(Email $email) => $email->toLogin())
		);

		$statuses = $emailCollection->map(
			function(Email $email) use ($userCollection)
			{
				$user = $userCollection->filter(
					fn (User $user) => mb_strtolower($user->getEmail()) === mb_strtolower($email->toLogin())

				)
					->first()
				;
				return [
					'email' => $email,
					'user' => $user,
				];
			}
		);

		return (new Result())->setData($statuses);
	}

	public function checkInvitationsStatusByPhoneCollection(PhoneCollection $phoneCollection): Result
	{
		$userCollection = $this->userRepository->findUsersByPhoneNumbers(
			$phoneCollection->map(fn(Phone $phone) => $phone->defaultFormat())
		);

		$statuses = $phoneCollection->map(
			function(Phone $phone) use ($userCollection)
			{
				$user = $userCollection
					->filter(fn(User $user) => $user->getAuthPhoneNumber() === $phone->defaultFormat())
					->first();

				return [
					'phone' => $phone,
					'user' => $user,
				];
			}
		);

		return (new Result())->setData($statuses);
	}
}