<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Result\PropertyResult;

class GetMappingEntityCollectionResult extends PropertyResult
{
	public function __construct(
		public readonly MappingEntityCollection $collection
	)
	{
		parent::__construct();
	}
}