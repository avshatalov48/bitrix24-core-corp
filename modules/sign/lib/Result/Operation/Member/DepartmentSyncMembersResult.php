<?php

namespace Bitrix\Sign\Result\Operation\Member;

use Bitrix\Sign\Item\Hr\EntitySelector\EntityCollection;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Result\SuccessResult;

class DepartmentSyncMembersResult extends SuccessResult
{
	public function __construct(
		public readonly MemberCollection $members,
		public readonly EntityCollection $departments,
		public readonly string $assigneeEntityType = '',
	)
	{
		parent::__construct();
	}
}