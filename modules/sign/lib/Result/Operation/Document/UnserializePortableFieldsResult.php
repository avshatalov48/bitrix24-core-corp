<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\Blank\Export\PortableFieldCollection;
use Bitrix\Sign\Result\SuccessResult;

class UnserializePortableFieldsResult  extends SuccessResult
{
	public function __construct(
		public readonly PortableFieldCollection $fields,
	)
	{
		parent::__construct();
	}
}
