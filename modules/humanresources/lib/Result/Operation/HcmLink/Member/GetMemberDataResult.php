<?php

namespace Bitrix\HumanResources\Result\Operation\HcmLink\Member;

use Bitrix\HumanResources\Item\Collection\HcmLink\FieldValueCollection;
use Bitrix\Sign\Result\SuccessResult;

class GetMemberDataResult extends SuccessResult
{
	public int $jobId;
	public FieldValueCollection $result;
}