<?php

namespace Bitrix\Baas\UseCase\Proxy\Request;

use \Bitrix\Baas;

class BalanceDataConvertRequest
{
	public function __construct(
		public Baas\Entity\Service $service,
		public array $rawData,
	)
	{
	}
}
