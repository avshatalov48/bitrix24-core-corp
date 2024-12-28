<?php

namespace Bitrix\HumanResources\Result\Operation\HcmLink\Member;

use Bitrix\HumanResources\Result\SuccessResult;

class RequestMemberResult extends SuccessResult
{
	public int $jobId;
	public bool $isDone = false;
}