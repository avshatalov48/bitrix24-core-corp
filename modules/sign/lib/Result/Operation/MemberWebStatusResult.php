<?php

namespace Bitrix\Sign\Result\Operation;

use Bitrix\Sign\Result\SuccessResult;

class MemberWebStatusResult extends SuccessResult
{
	public function __construct(
		public string $status
	)
	{
		parent::__construct();
	}
}