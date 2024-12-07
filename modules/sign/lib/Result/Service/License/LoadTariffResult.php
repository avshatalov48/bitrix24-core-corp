<?php

namespace Bitrix\Sign\Result\Service\License;

class LoadTariffResult extends \Bitrix\Sign\Result\SuccessResult
{
	public function __construct(
		public string $tariffCode,
	)
	{
		parent::__construct();
	}
}