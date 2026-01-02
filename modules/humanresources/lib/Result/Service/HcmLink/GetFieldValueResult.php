<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\FieldValueCollection;
use Bitrix\HumanResources\Result\PropertyResult;

class GetFieldValueResult extends PropertyResult
{
	public function __construct(
		public FieldValueCollection $collection,
		public bool $isActual,
	)
	{
		parent::__construct();
	}
}