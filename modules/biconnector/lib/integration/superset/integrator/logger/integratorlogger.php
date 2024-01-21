<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Logger;

use Bitrix\Main\Error;

interface IntegratorLogger
{
	/**
	 * @param string $method
	 * @param Error[] $errors
	 * @return void
	 */
	public function logMethodErrors(string $method, string $status, array $errors): void;
}