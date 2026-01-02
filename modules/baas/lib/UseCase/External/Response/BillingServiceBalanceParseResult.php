<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Response;

use \Bitrix\Main;
use \Bitrix\Baas;

class BillingServiceBalanceParseResult extends Main\Result
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
