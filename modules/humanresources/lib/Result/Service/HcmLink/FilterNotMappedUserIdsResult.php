<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Result\SuccessResult;

class FilterNotMappedUserIdsResult extends SuccessResult
{
	public function __construct(
		public array $userIds,
	)
	{
		parent::__construct();
	}
}