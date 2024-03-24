<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Tasks\Internals\Member\MemberFacade;

trait MemberTrait
{
	protected ?MemberFacade $memberFacade = null;

	public function getResponsibleMemberId(): ?int
	{
		return $this->getFacade()->getResponsibleMemberId();
	}

	public function getCreatedByMemberId(): ?int
	{
		return $this->getFacade()->getCreatedByMemberId();
	}

	public function getAccompliceMembersIds(): array
	{
		return $this->getFacade()->getAccompliceMembersIds();
	}

	public function getAuditorMembersIds(): array
	{
		return $this->getFacade()->getAuditorMembersIds();
	}

	public function getMembersIdsByRole(string $role): array
	{
		return $this->getFacade()->getMemberIds($role);
	}

	protected function getFacade(): MemberFacade
	{
		if (is_null($this->memberFacade))
		{
			$this->memberFacade = new MemberFacade($this->getMemberService());
		}

		return $this->memberFacade;
	}
}
