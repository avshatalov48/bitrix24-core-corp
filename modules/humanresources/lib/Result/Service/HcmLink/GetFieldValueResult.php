<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\FieldValueCollection;

class GetFieldValueResult
{
	public function __construct(
		public FieldValueCollection $collection,
		public bool $isActual,
	)
	{}
}