<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\Blank\Export\PortableBlockCollection;
use Bitrix\Sign\Result\SuccessResult;

class UnserializePortableBlocksResult extends SuccessResult
{
	public function __construct(
		public readonly PortableBlockCollection $blocks,
	)
	{
		parent::__construct();
	}
}