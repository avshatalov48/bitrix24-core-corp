<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\Config\BaseConfig;
use Bitrix\Tasks\Member\Type\Member;

trait MemberTrait
{
	public function getResponsibleMemberId(): ?int
	{
		return $this->getMemberId(RoleDictionary::ROLE_RESPONSIBLE);
	}

	public function getCreatedByMemberId(): ?int
	{
		return $this->getMemberId(RoleDictionary::ROLE_DIRECTOR);
	}

	private function getMemberId(string $role): ?int
	{
		$result = $this->getMemberService()->get([$role], new BaseConfig());
		if (!$result->isSuccess() || empty($result->getData()[$role]))
		{
			return null;
		}

		/** @var Member $member */
		$member = array_pop($result->getData()[$role]);

		return $member->getUserId();
	}

	public function getAccompliceMembersIds(): array
	{
		return $this->getMembersIdsByRole(RoleDictionary::ROLE_ACCOMPLICE);
	}

	public function getAuditorMembersIds(): array
	{
		return $this->getMembersIdsByRole(RoleDictionary::ROLE_AUDITOR);
	}

	private function getMembersIdsByRole(string $role): array
	{
		$result = $this->getMemberService()->get([$role], new BaseConfig());
		if (!$result->isSuccess() || empty($result->getData()[$role]))
		{
			return [];
		}

		return array_map(static fn(Member $member): int => $member->getUserId(), $result->getData()[$role]);
	}
}