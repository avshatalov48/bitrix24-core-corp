<?php

namespace Bitrix\Intranet\Public\Service;

use Bitrix\Intranet\Command;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Internal\Entity\Invitation;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\Type\InvitationsContainer;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Exception\InvitationFailedException;
use Bitrix\Intranet;
use Bitrix\Intranet\Public\Facade\Invitation\CollabInvitationFacade;
use Bitrix\Intranet\Internal\Integration\Socialnetwork\ExternalAuthType;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Error;
use Bitrix\SocialNetwork\Collab\Analytics\CollabAnalytics;
use Bitrix\Socialnetwork\Internals\Registry\GroupRegistry;
use Bitrix\Socialnetwork\Item\Workgroup\Type;

class InvitationService
{
	private bool $isMassInviteStarted = false;

	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly RegistrationService $registrationService,
	)
	{}

	/**
	 * @throws \Exception
	 */
	public function inviteByCollection(InvitationCollection $collection): UserCollection
	{
		$userCollection = new UserCollection();
		$errorCollection = new ErrorCollection();
		$this->isMassInviteStarted = true;
		foreach ($collection as $invitation)
		{
			try
			{
				$user = $this->invite($invitation);
				$userCollection->add($user);
			}
			catch (\Exception $exception)
			{
				$errorCollection->setError(new Error($exception->getMessage(), $exception->getCode()));
			}
		}

		$event = new Event(
			'intranet',
			'onUserInvited',
			[
				'originatorId' => CurrentUser::get()->getId(),
				'userId' =>$userCollection->getIds(), //is backward compatibility
				'invitedUsers' => $userCollection,
			],
		);
		$event->send();

		$this->isMassInviteStarted = false;

		if (!$errorCollection->isEmpty())
		{
			throw new InvitationFailedException($errorCollection);
		}

		return $userCollection;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function invite(BaseInvitation $invitation): User
	{
		try
		{
			Application::getConnection()->startTransaction();

			$event = new Event(
				'intranet',
				'onBeforeInviteUser',
				[
					'invitation' => $invitation,
				]
			);
			$event->send();

			$user = $this->process($invitation);

			$invitationRepository = ServiceContainer::getInstance()->invitationRepository();
			if (!$invitationRepository->getUserById($user->getId()))
			{
				$invitationRepository->save(
					new Invitation(
						userId: $user->getId(),
						initialized: false,
						isMass: $invitation->getFormType() === 'mass',
						isDepartment: $invitation->getFormType() === 'group',
						isIntegrator: $invitation->getFormType() === 'integrator',
						isRegister: $invitation->getFormType() === 'register',
						id: null,
						originatorId: CurrentUser::get()->getId(),
						type: $invitation->getType(),
					)
				);
			}

			if (!$this->isMassInviteStarted)
			{
				$event = new Event(
					'intranet',
					'onUserInvited',
					[
						'originatorId' => CurrentUser::get()->getId(),
						'userId' => [$user->getId()], //is backward compatibility
						'invitedUsers' => new UserCollection($user),
					],
				);
				$event->send();
			}
			Application::getConnection()->commitTransaction();

			return $user;
		}
		catch (\Exception $exception)
		{
			Application::getConnection()->rollbackTransaction();

			throw $exception;
		}
	}

	private function getFakeUserTypeList(): array
	{
		return (new ExternalAuthType())->getNotUserTypeList();
	}

	/**
	 * @param BaseInvitation $invitation
	 * @return User|null
	 * @throws \Exception
	 */
	private function process(BaseInvitation $invitation): ?User
	{
		$userCollection = $this->userRepository->findRealUsersByLogins(
			[$invitation->getLogin()],
			$this->getFakeUserTypeList(),
		);

		$isFirstInvitation = false;
		if (!($user = $userCollection->first()))
		{
			$isFirstInvitation = true;
			$user = (new Intranet\Internal\Repository\Mapper\UserMapper())->convertFromArray($invitation->toArray());
			$user = $this->registrationService->register($user);
		}
		else
		{
			if (
				!$user->isEmail()
				&& !$user->isShop()
				&& $user->getInviteStatus() === Intranet\Enum\InvitationStatus::ACTIVE
			)
			{
				throw new SystemException('User ('.$user->getLogin().') already exists.');
			}
		}

		$event = new Event(
			'intranet',
			'onSendInviteUser',
			[
				'invitedUser' => $user,
				'invitation' => $invitation,
				'isFirstInvitation' => $isFirstInvitation,
			]
		);
		$event->send();

		return $user;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function inviteUsersToGroup(int $groupId, InvitationsContainer $inviteData): Result
	{
		$result = new Result();

		if (!Loader::includeModule('socialnetwork'))
		{
			throw new SystemException('Module "socialnetwork" is not installed');
		}

		$group = GroupRegistry::getInstance()->get($groupId);

		if ($group === null)
		{
			$result->addError(new Error('', 'socnetgroup_not_found'));

			return $result;
		}

		$invitationItems = $inviteData->backwardsCompatibility();
		if ($group->getType() === Type::Collab)
		{
			$invitationItems['COLLAB_GROUP'] = $group;
		}

		try
		{
			$collabInvitation = new CollabInvitationFacade($group);
			$userCollection = $collabInvitation->inviteByCollection($inviteData->getInvitationCollection());

		}
		catch (InvitationFailedException $exception)
		{
			$result->addErrors($exception->getErrors()->getValues());
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		$inviteCommand = new Command\Invitation\InviteUserCollectionToGroupCommand(
			groupId: $groupId,
			userCollection: $userCollection,
		);
		$inviteToGroupResult = $inviteCommand->execute();

		if (!$inviteToGroupResult->isSuccess())
		{
			return $inviteToGroupResult;
		}

		if ($group->getType() === Type::Collab)
		{
			static::sendAnalyticsInvitationsCollabs($groupId, $inviteToGroupResult, $invitationItems);
		}

		$result->setData($userCollection->all());

		return $result;
	}

	private static function sendAnalyticsInvitationsCollabs(int $groupId, Result $inviteToGroupResult, array $invitationItems): void
	{
		/** @var InvitationType $type */
		$type = null;

		foreach ($inviteToGroupResult->getData() as $user)
		{
			$index = array_search($user->getEmail(), array_column($invitationItems['ITEMS'], 'EMAIL'));

			$type = InvitationType::PHONE;
			if ($index !== false)
			{
				$type = InvitationType::EMAIL;
			}

			CollabAnalytics::getInstance()->onUserInvitation($user->getId(), $groupId, $type->value);
		}
	}
}