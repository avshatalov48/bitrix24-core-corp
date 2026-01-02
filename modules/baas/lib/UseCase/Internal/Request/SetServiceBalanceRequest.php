<?php

namespace Bitrix\Baas\UseCase\Internal\Request;

use \Bitrix\Baas;

class SetServiceBalanceRequest
{
	public function __construct(
		public Baas\Entity\Service $service,
		public int $stateNumber,
		public Baas\Model\EO_ServiceInPurchasedPackage_Collection $serviceInPurchasedPackages,
	)
	{
	}
}
