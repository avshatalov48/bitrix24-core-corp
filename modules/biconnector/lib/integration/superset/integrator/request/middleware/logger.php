<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorLogger;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;

final class Logger extends Base
{
	private const ID = "LOGGER";


	public function __construct(private readonly IntegratorLogger $logger)
	{}

	public static function getMiddlewareId(): string
	{
		return self::ID;
	}

	public function afterRequest(IntegratorRequest $request, IntegratorResponse $response): IntegratorResponse
	{
		if ($response->hasErrors())
		{
			$this->logger->logMethodErrors($request->getAction(), $response->getStatus(), $response->getErrors());
		}

		return parent::afterRequest($request, $response);
	}
}
