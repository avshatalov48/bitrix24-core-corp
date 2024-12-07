<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware\Queue;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;

class AfterRequestQueue extends Queue
{
	public function execute(IntegratorRequest $request, IntegratorResponse $response): IntegratorResponse
	{
		foreach ($this->getQueue() as $middleware)
		{
			$response = $middleware->afterRequest($request, $response);
		}

		return $response;
	}
}
