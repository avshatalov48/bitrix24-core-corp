<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Result\SuccessResult;

final class PrepareMembersResult extends SuccessResult
{
	public function __construct(
		public readonly MemberCollection $members,
		public readonly ?int $representativeRoleId,
	)
	{
		parent::__construct();
	}
}