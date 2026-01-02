<?php

namespace Bitrix\Sign\Result\Service\Sign\Template;

use Bitrix\Sign\Item\Document\TemplateFolder;
use Bitrix\Sign\Result\SuccessResult;

class CreateTemplateFolderResult extends SuccessResult
{
	public function __construct(
		public readonly TemplateFolder $templateFolder,
	)
	{
		parent::__construct();
	}
}