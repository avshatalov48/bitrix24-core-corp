<?php

namespace Bitrix\Sign\Result\Service;

class ExternalSigningUrlResult extends \Bitrix\Sign\Result\SuccessResult
{
	public function __construct(
		public string $url,
	)
	{
		parent::__construct();
	}
}