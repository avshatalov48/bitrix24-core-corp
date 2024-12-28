<?php

namespace Bitrix\Sign\Result\Operation\SigningService;

use Bitrix\Sign\Result\SuccessResult;

class HcmLinkFieldLoadResult extends SuccessResult
{
	public function __construct(
		public readonly bool $shouldWait = false,
	)
	{
		parent::__construct();
	}
}