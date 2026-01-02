<?php

namespace Bitrix\Baas\UseCase\External;

use Bitrix\Main;
use Bitrix\Baas;

abstract class BaseAction
{
	protected Entity\Client $client;
	protected Entity\Server $server;

	public function __construct(
		Baas\UseCase\External\Request\BaseRequest $request,
	)
	{
		$this->client = $request->client;
		$this->server = $request->server;
	}

	public function __invoke(): Main\Result
	{
		return $this->run();
	}

	abstract protected function run(): Main\Result;
}
