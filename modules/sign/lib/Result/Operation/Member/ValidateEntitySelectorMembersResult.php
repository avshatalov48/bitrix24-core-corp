<?php

namespace Bitrix\Sign\Result\Operation\Member;

use Bitrix\Sign\Item\Member\SelectorEntityCollection;
use Bitrix\Sign\Result\SuccessResult;

class ValidateEntitySelectorMembersResult extends SuccessResult
{
	public function __construct(
		public readonly SelectorEntityCollection $entities,
	)
	{
		parent::__construct();
	}
}