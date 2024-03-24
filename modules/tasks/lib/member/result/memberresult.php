<?php

namespace Bitrix\Tasks\Member\Result;

use Bitrix\Main\Result;
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
