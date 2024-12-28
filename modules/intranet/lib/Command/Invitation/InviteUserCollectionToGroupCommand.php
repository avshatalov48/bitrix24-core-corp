<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Command\Invitation;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Contract\Command;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Integration\Socialnetwork;
use Bitrix\Intranet\Service\InviteMessageFactory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Socialnetwork\Collab\Collab;
use Bitrix\Socialnetwork\Internals\Registry\GroupRegistry;

class InviteUserCollectionToGroupCommand implements Command
{
	private Socialnetwork\Group\MemberServiceFacade $memberServiceFacade;
	private bool $currentUserIsIntranet;

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct(private readonly int $groupId, private readonly UserCollection $userCollection)
	{
		$this->memberServiceFacade = new Socialnetwork\Group\MemberServiceFacade($this->groupId);
		$this->currentUserIsIntranet = (new Intranet\User((int)Intranet\CurrentUser::get()->getId()))->isIntranet();
	}

	/**
	 * @throws ArgumentException
	 */
	public function execute(): Result
	{
		$firedUserCollection = $this->userCollection->filter(
			static fn(User $user) => $user->getInviteStatus() === InvitationStatus::FIRED
		);

		if (!$firedUserCollection->empty())
		{
			$invitationPyPhoneAvailable = Loader::includeModule('bitrix24')
				&& Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y';

			if ($invitationPyPhoneAvailable)
			{
				return (new Result())->addError(new Error(
					Loc::getMessage('INTRANET_COMMAND_INVITATION_USER_COLLECTION_TO_GROUP_FIRED_WITH_PHONE')
				));
			}

			return (new Result())->addError(new Error(
				Loc::getMessage('INTRANET_COMMAND_INVITATION_USER_COLLECTION_TO_GROUP_FIRED')
			));
		}

		[$inviteCollection, $addCollection] = $this->splitUserCollection($this->userCollection);

		if (!$inviteCollection->empty())
		{
			$invitationResult = $this->memberServiceFacade->inviteUserCollection($inviteCollection);

			if (!$invitationResult->isSuccess())
			{
				return $invitationResult;
			}

			$this->sendEmailInvitationByUserCollection($inviteCollection);
		}

		if (!$addCollection->empty())
		{
			$addResult = $this->memberServiceFacade->addUserCollection($addCollection);

			if (!$addResult->isSuccess())
			{
				return $addResult;
			}
		}

		return (new Result())->setData([...$inviteCollection, ...$addCollection]);
	}

	/**
	 * @throws ArgumentException
	 * @return array{UserCollection, UserCollection}
	 */
	private function splitUserCollection(UserCollection $userCollection): array
	{
		if ($this->currentUserIsIntranet)
		{
			$invitationCollection = new UserCollection();
			$addCollection = new UserCollection();
			$userCollection->forEach(function (User $user) use ($invitationCollection, $addCollection) {
				$status = $user->getInviteStatus();

				if ($user->isIntranet())
				{
					$addCollection->add($user);
				}
				else if (
					in_array($status, [
						InvitationStatus::INVITED,
						InvitationStatus::NOT_REGISTERED,
						InvitationStatus::INVITE_AWAITING_APPROVE
					], true)
				)
				{
					$invitationCollection->add($user);
				}
				else
				{
					$addCollection->add($user);
				}
			});
		}
		else
		{
			$invitationCollection = $userCollection;
			$addCollection = new UserCollection();
		}

		return [$invitationCollection, $addCollection];
	}

	private function sendEmailInvitationByUserCollection(UserCollection $userCollection): void
	{
		$group = GroupRegistry::getInstance()->get($this->groupId);
		$extranetAvailable = Loader::includeModule('extranet');

		if ($group instanceof Collab)
		{
			$messageFactory = new InviteMessageFactory(
				Loc::getMessage('INTRANET_COMMAND_INVITATION_USER_COLLECTION_TO_GROUP_COLLAB_EMAIL_TEXT'),
				$group
			);

			foreach ($userCollection as $user)
			{
				if (
					$extranetAvailable
					&& $user->getInviteStatus() === InvitationStatus::ACTIVE
					&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById($user->getId())
				)
				{
					$messageFactory->create($user)->sendImmediately();
				}
			}
		}
	}
}
