<?php

namespace Bitrix\Tasks\Internals\Member;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Config\BaseConfig;
use Bitrix\Tasks\Member\Config\ConfigInterface;
use Bitrix\Tasks\Member\Result\MemberResult;
use Bitrix\Tasks\Member\Type\Member;
use Bitrix\Tasks\Member\Type\MemberCollection;

class MemberFacade
{
	private AbstractMemberService $service;

	final public function __construct(AbstractMemberService $service)
	{
		$this->service = $service;
	}

	public function getResponsibleMemberId(): ?int
	{
		return $this->getMemberId(RoleDictionary::ROLE_RESPONSIBLE);
	}

	public function getCreatedByMemberId(): ?int
	{
		return $this->getMemberId(RoleDictionary::ROLE_DIRECTOR);
	}

	public function getAccompliceMembersIds(): array
	{
		return $this->getMemberIds(RoleDictionary::ROLE_ACCOMPLICE);
	}

	public function getAuditorMembersIds(): array
	{
		return $this->getMemberIds(RoleDictionary::ROLE_AUDITOR);
	}

	public function getResponsibleMember(): ?Member
	{
		return $this->getMember(RoleDictionary::ROLE_RESPONSIBLE);
	}

	public function getCreatedByMember(): ?Member
	{
		return $this->getMember(RoleDictionary::ROLE_DIRECTOR);
	}

	public function getAccompliceMembers(): ?MemberCollection
	{
		return $this->getMembers(RoleDictionary::ROLE_ACCOMPLICE);
	}

	public function getAuditorMembers(): ?MemberCollection
	{
		return $this->getMembers(RoleDictionary::ROLE_AUDITOR);
	}

	public function load(): MemberResult
	{
		return $this->service->get(RoleDictionary::getAvailableRoles(), $this->getDefaultConfig());
	}

	public function getMemberIds(string $role): array
	{
		return (array)$this->getMembers($role)?->getUserIds();
	}

	private function getMemberId(string $role): ?int
	{
		return $this->getMember($role)?->getUserId();
	}

	private function getMember(string $role): ?Member
	{
		$result = $this->service->get([$role], $this->getDefaultConfig());
		if (!$result->isSuccess() || $result->isEmpty($role))
		{
			return null;
		}

		return $result->getMembers($role)->pop();
	}

	private function getMembers(string $role = ''): ?MemberCollection
	{
		$result = $this->service->get([$role], $this->getDefaultConfig());
		if (!$result->isSuccess() || $result->isEmpty($role))
		{
			return null;
		}

		return $result->getMembers($role);
	}

	private function getDefaultConfig(): ConfigInterface
	{
		return new BaseConfig();
	}
}
