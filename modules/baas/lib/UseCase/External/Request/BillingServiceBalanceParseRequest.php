<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BillingServiceBalanceParseRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public Baas\Entity\Service $service,
		public array $rawData,
	)
	{
		parent::__construct($server, $client);
	}
}
