<?php

namespace Bitrix\Sign\Result\Service\Sign\Template;

use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Result\SuccessResult;

class CreateTemplateFolderRelationResult extends SuccessResult
{
	public function __construct(
		public readonly TemplateFolderRelation $templateFolderRelation,
	)
	{
		parent::__construct();
	}
}