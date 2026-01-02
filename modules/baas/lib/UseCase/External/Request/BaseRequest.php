<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
	)
	{
	}
}
