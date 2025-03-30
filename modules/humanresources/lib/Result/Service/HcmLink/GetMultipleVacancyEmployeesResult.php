<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Result\SuccessResult;

class GetMultipleVacancyEmployeesResult extends SuccessResult
{
	public function __construct(
		public array $employees,
	)
	{
		parent::__construct();
	}
}