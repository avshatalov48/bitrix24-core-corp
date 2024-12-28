<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Result\SuccessResult;

class ImportBlankResult extends SuccessResult
{
	public function __construct(
		public readonly int $blankId,
	)
	{
		parent::__construct();
	}
}