<?php

namespace Bitrix\Tasks\Member\Result;

use Bitrix\Main\Result;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\Type\MemberCollection;

class MemberResult extends Result
{
	private MemberCollection $members;

	public function __construct()
	{
		parent::__construct();
		$this->init();
	}

	public function getMembers(string $role): MemberCollection
	{
		return $this->members->get($role);
	}

	public function getAllMembers(): MemberCollection
	{
		return (new MemberCollection())
			->merge($this->getMembers(RoleDictionary::ROLE_DIRECTOR))
			->merge($this->getMembers(RoleDictionary::ROLE_RESPONSIBLE))
			->merge($this->getMembers(RoleDictionary::ROLE_ACCOMPLICE))
			->merge($this->getMembers(RoleDictionary::ROLE_AUDITOR));
	}

	public function setMembers(MemberCollection $members): void
	{
		$this->members = $members;
	}

	public function isEmpty(string $key = ''): bool
	{
		return $this->members->isEmpty($key);
	}

	private function init()
	{
		$this->members = new MemberCollection();
	}
}
