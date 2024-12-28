<?php

namespace Bitrix\Sign\Result\Operation\Member;

use Bitrix\Sign\Result\SuccessResult;

class MakeB2eSignedFileNameResult extends SuccessResult
{
	public function __construct(
		public readonly string $fileName,
	)
	{
		parent::__construct();
	}
}