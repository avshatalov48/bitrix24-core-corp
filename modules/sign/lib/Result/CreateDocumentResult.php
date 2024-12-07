<?php

namespace Bitrix\Sign\Result;

use Bitrix\Sign\Item;

final class CreateDocumentResult extends SuccessResult
{
	public function __construct(
		public Item\Document $document,
	)
	{
		parent::__construct();
	}
}