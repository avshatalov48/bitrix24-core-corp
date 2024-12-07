<?php

namespace Bitrix\Sign\Result\Operation\Member\Reminder;

use Bitrix\Sign\Result\SuccessResult;

class CheckForgottenReminderResult extends SuccessResult
{
	public function __construct(
		public readonly bool $isForgotten,
	)
	{
		parent::__construct();
	}
}