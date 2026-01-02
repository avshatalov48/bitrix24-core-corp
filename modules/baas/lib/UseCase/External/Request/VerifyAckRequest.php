<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class VerifyAckRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public readonly string $ack,
		public readonly string $syn,
	)
	{
		parent::__construct($server, $client);
	}
}
