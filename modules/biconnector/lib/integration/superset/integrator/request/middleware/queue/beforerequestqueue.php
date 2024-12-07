<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware\Queue;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;

class BeforeRequestQueue extends Queue
{
	private bool $skipAfterMiddlewares = false;

	public function execute(IntegratorRequest $request): ?IntegratorResponse
	{
		foreach ($this->getQueue() as $middleware)
		{
			$response = $middleware->beforeRequest($request);
			if ($response)
			{
				if ($middleware->isSkippedAfterMiddlewares())
				{
					$this->skipAfterMiddlewares = true;
				}

				return $response;
			}
		}

		return null;
	}

	public function isSkipAfterMiddlewares(): bool
	{
		return $this->skipAfterMiddlewares;
	}
}
