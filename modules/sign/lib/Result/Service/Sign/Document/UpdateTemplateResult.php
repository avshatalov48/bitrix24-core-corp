<?php

namespace Bitrix\Sign\Result\Service\Sign\Document;

use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Result\SuccessResult;

class UpdateTemplateResult extends SuccessResult
{
	public function __construct(
		public readonly Template $template,
		public readonly ?int $companyEntityId = null,
	)
	{
		parent::__construct();
	}
}