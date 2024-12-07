<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Integrator\Sender;

abstract class Base
{
	private bool $skipAfterMiddlewares = false;

	abstract public static function getMiddlewareId(): string;

	/**
	 * $request calls this method before making request through <b>Sender</b>.
	 * If method returns IntegratorResponse - IntegratorRequest stops execute all other middlewares, and it's request, and returns in request returned response
	 *
	 * @see Sender
	 * @param IntegratorRequest $request
	 * @return IntegratorResponse|null
	 */
	public function beforeRequest(IntegratorRequest $request): ?IntegratorResponse
	{
		return null;
	}

	/**
	 * $request calls this method after making request through <b>Sender</b>. $response is response that returns by ProxySender and modify by other RequestMiddleware's
	 *
	 * @see Sender
	 * @param IntegratorRequest $request
	 * @param IntegratorResponse $response
	 * @return IntegratorResponse
	 */
	public function afterRequest(IntegratorRequest $request, IntegratorResponse $response): IntegratorResponse
	{
		return $response;
	}

	protected function skipAfterMiddlewares(bool $skip = true): void
	{
		$this->skipAfterMiddlewares = $skip;
	}

	/**
	 * If returns true - all after request middlewares will be skipped.
	 * @return bool
	 */
	public function isSkippedAfterMiddlewares(): bool
	{
		return $this->skipAfterMiddlewares;
	}
}
