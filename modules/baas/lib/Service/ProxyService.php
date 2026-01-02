<?php

declare(strict_types=1);

namespace Bitrix\Baas\Service;

use Bitrix\Baas;
use Bitrix\Baas\UseCase\Proxy;

class ProxyService
{
	use Baas\Internal\Trait\SingletonConstructor;

	protected function __construct()
	{
	}

	public function convertServiceBalance(
		Baas\Entity\Service $service,
		array $proxyStateRawResponse,
	): Proxy\Response\BalanceDataConvertResult
	{
		return (new Proxy\BalanceDataConvert(
			new Proxy\Request\BalanceDataConvertRequest(
				$service,
				$proxyStateRawResponse,
			),
		))();
	}
}
