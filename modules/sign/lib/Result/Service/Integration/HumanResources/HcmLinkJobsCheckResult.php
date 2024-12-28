<?php

namespace Bitrix\Sign\Result\Service\Integration\HumanResources;

use Bitrix\Sign\Result\SuccessResult;

class HcmLinkJobsCheckResult extends SuccessResult
{
	public function __construct(
		public readonly bool $isDone = false,
	)
	{
		parent::__construct();
	}
}