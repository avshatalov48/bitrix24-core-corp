<?php

namespace Bitrix\Sign\Result\Service\Integration\HumanResources;

use Bitrix\Sign\Result\SuccessResult;

class HcmLinkFieldRequestResult extends SuccessResult
{
	public function __construct(
		public readonly int $jobId,
	)
	{
		parent::__construct();
	}
}