<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Result\PropertyResult;

class GetMultipleVacancyEmployeesResult extends PropertyResult
{
	public function __construct(
		public array $employees,
	)
	{
		parent::__construct();
	}
}