<?php

namespace Bitrix\HumanResources\Result\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Result\PropertyResult;

class JobServiceResult extends PropertyResult
{
	public function __construct(
		public Job $job,
	)
	{
		parent::__construct();
	}
}