<?php

namespace Bitrix\Sign\Service\Result\Sign\Block;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\B2e\RequiredFieldsCollection;

class B2eRequiredFieldsResult extends Result
{
	public function __construct(
		public readonly RequiredFieldsCollection $collection
	) {
		parent::__construct();
	}
}