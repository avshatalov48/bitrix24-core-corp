<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\HumanResources\Result\SuccessResult;

class GetMatchesForMappingResult extends SuccessResult
{
	/**
	 * @param MappingEntity $items
	 */
	public function __construct(
		public array $items,
	)
	{
		parent::__construct();
	}
}