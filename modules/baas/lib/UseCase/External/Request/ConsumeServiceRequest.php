<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class ConsumeServiceRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public Baas\Entity\Service $service,
		public int $units,
		public bool $force,
		public ?array $attributes = null,
	)
	{
		parent::__construct($server, $client);
	}
}
