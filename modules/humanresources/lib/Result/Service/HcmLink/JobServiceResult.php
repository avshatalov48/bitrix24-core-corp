<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Result\SuccessResult;

class JobServiceResult extends SuccessResult
{
	public function __construct(
		public Job $job,
	)
	{
		parent::__construct();
	}
}