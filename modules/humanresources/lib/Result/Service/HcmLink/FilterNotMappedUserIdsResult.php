<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Result\PropertyResult;

class FilterNotMappedUserIdsResult extends PropertyResult
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