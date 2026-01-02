<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Result\SuccessResult;

final class PrepareMemberListForTemplateSendResult extends SuccessResult
{
	public function __construct(
		public readonly MemberCollection $members,
		public readonly ?int $representativeRoleId,
	)
	{
		parent::__construct();
	}
}