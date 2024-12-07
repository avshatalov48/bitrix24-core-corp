<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorLogger;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use \Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\Main\Error;

final class Registrar extends Base
{
	private const ID = 'REGISTRAR';

	public function __construct(
		private readonly \Bitrix\BIConnector\Integration\Superset\Registrar $registrar,
		private readonly IntegratorLogger $logger
	) {}

	public function beforeRequest(IntegratorRequest $request): ?IntegratorResponse
	{
		if ($this->registrar->isComplete())
		{
			return null;
		}

		$this->logger->logInfo('portal make register action', ['trigger_request' => $request->getAction()]);
		$result = $this->registrar->register();
		if (!$result->isSuccess())
		{
			$this->logger->logErrors([
				new Error('cannot register portal on supersetproxy while make method ' . $request->getAction()),
				...$result->getErrors(),
			]);

			return new IntegratorResponse(
				IntegratorResponse::STATUS_UNKNOWN,
				null,
				[new Error('portal registration is incomplete', IntegratorResponse::STATUS_UNKNOWN)]
			);
		}

		return null;
	}

	public function afterRequest(IntegratorRequest $request, IntegratorResponse $response): IntegratorResponse
	{
		if ($response->getStatus() === IntegratorResponse::STATUS_REGISTER_REQUIRED)
		{
			$this->logger->logInfo("Portal got 'register required' response. Clear registrar info");
			$response->setStatus(IntegratorResponse::STATUS_FROZEN);
			$this->registrar->clear();
		}

		return $response;
	}

	public static function getMiddlewareId(): string
	{
		return self::ID;
	}
}