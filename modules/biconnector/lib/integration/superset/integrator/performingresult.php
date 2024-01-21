<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\Main\Result;

final class PerformingResult
{
	public function __construct(
		public IntegratorResponse $response,
		public Result $requestResult,
	)
	{}
}