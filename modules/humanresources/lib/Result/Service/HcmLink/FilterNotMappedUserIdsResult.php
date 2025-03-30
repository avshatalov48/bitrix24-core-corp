<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Result\SuccessResult;

class FilterNotMappedUserIdsResult extends SuccessResult
{
	/**
	 * @param array<int, int> $userIds
	 */
	public function __construct(
		public array $userIds,
	)
	{
		parent::__construct();
	}
}