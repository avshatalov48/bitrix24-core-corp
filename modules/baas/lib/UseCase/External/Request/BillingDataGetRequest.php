<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BillingDataGetRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public string $languageId,
	)
	{
		parent::__construct($server, $client);
	}
}
