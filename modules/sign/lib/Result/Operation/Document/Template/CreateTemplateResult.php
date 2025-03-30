<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Result\SuccessResult;
use Bitrix\Sign\Item\Document\Template;

class CreateTemplateResult extends SuccessResult
{
	public function __construct(
		public readonly Template $template,
	)
	{
		parent::__construct();
	}
}