<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Logger;

use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class IntegratorEventLogger implements IntegratorLogger
{
	/**
	 * @param string $method
	 * @param Error[] $errors
	 * @return void
	 */
	public function logMethodErrors(string $method, string $status, array $errors): void
	{
		$errorObj = [
			'method' => $method,
			'status' => $status,
			'errors' => [],
		];

		foreach ($errors as $error)
		{
			$errorObj['errors'][] = $error->getMessage();
		}

		\CEventLog::Add([
			'SEVERITY' => 'ERROR',
			'AUDIT_TYPE_ID' => 'SUPERSET_PROXY_REQUEST_ERROR',
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($errorObj),
		]);
	}
}