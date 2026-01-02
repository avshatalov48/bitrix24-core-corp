<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Result\SuccessResult;

class SetupDocumentSignersResult extends SuccessResult
{
	public function __construct(
		public readonly bool $shouldCheckDepartmentSync,
	)
	{
		parent::__construct();
	}
}