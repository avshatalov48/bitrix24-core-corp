<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BillingDataPurgeRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public Baas\Repository\ServiceRepositoryInterface $serviceRepository,
		public Baas\Repository\PackageRepositoryInterface $packageRepository,
		public Baas\Repository\PurchaseRepositoryInterface $purchaseRepository,
	)
	{
		parent::__construct($server, $client);
	}
}
