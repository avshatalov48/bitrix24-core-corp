<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Logger\IntegratorLogger;
use Bitrix\Main\Error;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\SupersetStatusOptionContainer;

/**
 * Perform response only if superset status is 'READY'
 */
final class ReadyGate extends Base
{

	public function __construct(
		private readonly SupersetStatusOptionContainer $statusOptionContainer,
		private readonly IntegratorLogger $logger
	) {}

	public static function getMiddlewareId(): string
	{
		return 'READY_GATE';
	}

	public function beforeRequest(IntegratorRequest $request): ?IntegratorResponse
	{
		if ($this->statusOptionContainer->get() !== SupersetInitializer::SUPERSET_STATUS_READY)
		{
			$this->skipAfterMiddlewares();

			$errors = [new Error("For action '{$request->getAction()}' status 'READY' required")];
			$this->logger->logErrors($errors);

			return new IntegratorResponse(
				IntegratorResponse::STATUS_INNER_ERROR,
				null,
				$errors
			);
		}

		return null;
	}
}