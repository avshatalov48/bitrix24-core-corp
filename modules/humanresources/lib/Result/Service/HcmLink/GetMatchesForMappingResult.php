<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\MappingEntity;
use Bitrix\HumanResources\Result\PropertyResult;

class GetMatchesForMappingResult extends PropertyResult
{
	/**
	 * @param MappingEntity[] $items
	 */
	public function __construct(
		public array $items,
	)
	{
		parent::__construct();
	}
}