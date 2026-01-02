<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Item\Document\Template\TemplateCreatedDocumentCollection;
use Bitrix\Sign\Result\SuccessResult;

class CreateDocumentsResult extends SuccessResult
{
	public function __construct(
		public readonly TemplateCreatedDocumentCollection $createdDocuments,
	)
	{
		parent::__construct();
	}
}