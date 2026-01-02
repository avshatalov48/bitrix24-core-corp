<?php

namespace Bitrix\Baas\UseCase\Proxy\Response;

use Bitrix\Main;
use Bitrix\Baas;

class BalanceDataConvertResult extends Main\Result
{
	public function __construct(
		public readonly int $stateNumber,
		public readonly Baas\Model\EO_Service $service,
		public readonly Baas\Model\EO_ServiceInPurchasedPackage_Collection $serviceInPurchasedPackages,
	)
	{
		parent::__construct();
	}
}
