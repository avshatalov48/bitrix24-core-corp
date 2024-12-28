<?php

namespace Bitrix\Intranet\Integration\Socialnetwork\Group;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Socialnetwork\Control\Member\AbstractMemberService;
use Bitrix\Socialnetwork\Control\Member\Command\AddCommand;
use Bitrix\Socialnetwork\Control\Member\Command\MembersCommand;
use Bitrix\Socialnetwork\Control\ServiceFactory;

class MemberServiceFacade
{
	private AbstractMemberService $memberService;
	private readonly int $userId;

	public function __construct(
		private readonly int $groupId,
		?int $userId = null
	)
	{
		$this->memberService = ServiceFactory::getInstance()->getMemberService($this->groupId);
		$userId = (int)$userId <= 0 ? (int)CurrentUser::get()->getId() : $userId;
		if ($userId <= 0)
		{
			throw new ArgumentNullException('userId');
		}
		$this->userId = $userId;
	}

	public function addUser(User $user): Result
	{
		return $this->addByAccessCodes([$user->getAccessCode()]);
	}

	public function addUserCollection(UserCollection $userCollection): Result
	{
		return $this->addByAccessCodes($userCollection->mapToAccessCodes());
	}

	public function inviteUserCollection(UserCollection $userCollection): Result
	{
		return $this->inviteByAccessCodes($userCollection->mapToAccessCodes());
	}

	private function addByAccessCodes(array $members): Result
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return (new Result())->addError(new Error('Module "socialnetwork" is not installed'));
		}

		$addMemberCommand = (new MembersCommand())
			->setGroupId($this->groupId)
			->setInitiatorId($this->userId)
			->setMembers($members)
		;

		return $this->memberService->add($addMemberCommand);
	}

	private function inviteByAccessCodes(array $members): Result
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return (new Result())->addError(new Error('Module "socialnetwork" is not installed'));
		}

		$inviteCommand = (new MembersCommand())
			->setGroupId($this->groupId)
			->setInitiatorId($this->userId)
			->setMembers($members)
		;

		return $this->memberService->invite($inviteCommand);
	}
}
