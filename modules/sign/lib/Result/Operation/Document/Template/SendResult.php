<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Result\SuccessResult;

class SendResult extends SuccessResult
{
	public function __construct(
		public Document $newDocument,
		public Member $employeeMember,
	)
	{
		parent::__construct();
	}
}