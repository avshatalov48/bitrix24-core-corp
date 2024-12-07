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

	/**
	 * @param string $method
	 * @param string $status
	 * @param string $message
	 * @param array $params
	 * @return void
	 */
	public function logMethodInfo(string $method, string $status, string $message, array $params = []): void;


	public function logInfo(string $message, array $params = []);

	public function logErrors(array $errors);
}