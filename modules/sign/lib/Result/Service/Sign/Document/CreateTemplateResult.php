<?php

namespace Bitrix\Sign\Result\Service\Sign\Document;

use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Result\SuccessResult;

class CreateTemplateResult extends SuccessResult
{
	public function __construct(
		public readonly Template $template,
	)
	{
		parent::__construct();
	}
}