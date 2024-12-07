<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Logger;

use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

final class IntegratorEventLogger implements IntegratorLogger
{
	private const LOG_ENTITY_TYPE = 'SUPERSET_PROXY_REQUEST';

	private static function getAuditTypeByLogType(string $logType): string
	{
		return self::LOG_ENTITY_TYPE . '_' . $logType;
	}

	/**
	 * @inheritDoc
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

		$logType = 'ERROR';
		\CEventLog::Add([
			'SEVERITY' => $logType,
			'AUDIT_TYPE_ID' => self::getAuditTypeByLogType($logType),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($errorObj),
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function logMethodInfo(string $method, string $status, string $message, array $params = []): void
	{
		$messageObj = [
			'method' => $method,
			'status' => $status,
			'message' => $message,
		];

		if (!empty($params))
		{
			$messageObj['params'] = $params;
		}


		$logType = 'INFO';
		\CEventLog::Add([
			'SEVERITY' => $logType,
			'AUDIT_TYPE_ID' => self::getAuditTypeByLogType($logType),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($messageObj),
		]);
	}

	public function logErrors(array $errors)
	{
		$errorObj = [
			'errors' => [],
		];

		foreach ($errors as $error)
		{
			$errorObj['errors'][] = $error->getMessage();
		}

		$logType = 'ERROR';
		\CEventLog::Add([
			'SEVERITY' => $logType,
			'AUDIT_TYPE_ID' => self::getAuditTypeByLogType($logType),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($errorObj),
		]);
	}

	public function logInfo(string $message, array $params = [])
	{
		$messageObj = [
			'message' => $message,
		];

		if (!empty($params))
		{
			$messageObj['params'] = $params;
		}


		$logType = 'INFO';
		\CEventLog::Add([
			'SEVERITY' => $logType,
			'AUDIT_TYPE_ID' => self::getAuditTypeByLogType($logType),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($messageObj),
		]);
	}
}