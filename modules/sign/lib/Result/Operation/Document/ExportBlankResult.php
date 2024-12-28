<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Result\SuccessResult;

class ExportBlankResult extends SuccessResult
{
	public function __construct(
		public readonly PortableBlank $blank
	)
	{
		parent::__construct();
	}
}