<?php

namespace Bitrix\Sign\Service\Cache\Memory\Sign;

use Bitrix\Sign\Item;

class MemberService extends \Bitrix\Sign\Service\Sign\MemberService
{
	private array $userNames = [];
	private array $memberUserIds = [];

	public function getUserRepresentedName(int $userId): string
	{
		if (isset($this->userNames[$userId]))
		{
			return $this->userNames[$userId];
		}

		$userName = parent::getUserRepresentedName($userId);
		$this->userNames[$userId] = $userName;
		return $userName;
	}

	public function getUserIdForMember(Item\Member $member, ?Item\Document $document = null): ?int
	{
		if (isset($this->memberUserIds[$member->id]))
		{
			return $this->memberUserIds[$member->id];
		}

		$userId = parent::getUserIdForMember($member, $document);

		if ($userId)
		{
			$this->memberUserIds[$member->id] = $userId;
		}

		return $userId;
	}
}
