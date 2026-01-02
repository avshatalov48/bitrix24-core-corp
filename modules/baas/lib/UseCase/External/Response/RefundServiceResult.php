<?php

namespace Bitrix\Baas\UseCase\External\Response;

use Bitrix\Main;
use Bitrix\Baas;

class RefundServiceResult extends Main\Result
{
	public function __construct(
		public readonly int $stateNumber,
		public readonly string $consumptionId,
		public readonly Baas\Model\EO_Service $service,
		public readonly Baas\Model\EO_ServiceInPurchasedPackage_Collection $serviceInPurchasedPackages,
	)
	{
		parent::__construct();
	}
}
