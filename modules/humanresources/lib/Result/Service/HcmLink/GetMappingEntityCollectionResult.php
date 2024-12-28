<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Result\SuccessResult;

class GetMappingEntityCollectionResult extends SuccessResult
{
	public function __construct(
		public readonly MappingEntityCollection $collection
	)
	{
		parent::__construct();
	}
}