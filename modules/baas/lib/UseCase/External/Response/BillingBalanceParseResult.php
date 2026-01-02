<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Response;

use Bitrix\Main;
use Bitrix\Baas;

class BillingBalanceParseResult extends Main\Result
{
	public function __construct(
		public readonly Baas\Model\EO_Purchase_Collection $purchases,
		public readonly Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		public readonly Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	)
	{
		parent::__construct();
	}
}
