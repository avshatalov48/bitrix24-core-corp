<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class GetPurchaseReportRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public string $packageCode,
		public string $purchaseCode,
		public ?string $serviceCode = null,
	)
	{
		parent::__construct($server, $client);
	}
}
