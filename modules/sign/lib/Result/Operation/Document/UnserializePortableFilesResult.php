<?php

namespace Bitrix\Sign\Result\Operation\Document;

use Bitrix\Sign\Item\Blank\Export\PortableFileCollection;
use Bitrix\Sign\Result\SuccessResult;

class UnserializePortableFilesResult extends SuccessResult
{
	public function __construct(
		public readonly PortableFileCollection $files,
	)
	{
		parent::__construct();
	}
}