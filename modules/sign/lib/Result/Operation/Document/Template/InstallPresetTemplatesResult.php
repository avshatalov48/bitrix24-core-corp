<?php

namespace Bitrix\Sign\Result\Operation\Document\Template;

use Bitrix\Sign\Result\SuccessResult;

class InstallPresetTemplatesResult extends SuccessResult
{
	public function __construct(
		public readonly bool $isOptionsReloaded,
	)
	{
		parent::__construct();
	}
}