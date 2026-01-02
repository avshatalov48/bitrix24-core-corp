<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BillingBalanceParseRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public array $data,
	)
	{
		parent::__construct($server, $client);
	}
}
