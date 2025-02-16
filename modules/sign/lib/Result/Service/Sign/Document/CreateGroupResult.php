<?php

namespace Bitrix\Sign\Result\Service\Sign\Document;

use Bitrix\Sign\Item\Document\Group;
use Bitrix\Sign\Result\SuccessResult;

class CreateGroupResult extends SuccessResult
{
	public function __construct(
		public readonly Group $group,
	)
	{
		parent::__construct();
	}
}