<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\Blank\Export\PortableField;
use Bitrix\Sign\Result\SuccessResult;

class ExportFieldResult extends SuccessResult
{
	public function __construct(
		public readonly PortableField $field
	)
	{
		parent::__construct();
	}
}