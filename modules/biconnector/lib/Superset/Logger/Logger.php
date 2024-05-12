<?php

namespace Bitrix\BIConnector\Superset\Logger;

use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class Logger
{
	/**
	 * @param Error[] $errors
	 * @param Array<string, string|array> $additionalFields
	 * @return void
	 */
	final public static function logErrors(array $errors, array $additionalFields = []): void
	{
		$result = [];

		foreach ($errors as $error)
		{
			$result['errors'][] = $error->getMessage();
		}

		if (!empty($additionalFields))
		{
			$result += $additionalFields;
		}

		\CEventLog::Add([
			'SEVERITY' => \CEventLog::SEVERITY_ERROR,
			'AUDIT_TYPE_ID' => static::getAuditType(\CEventLog::SEVERITY_ERROR),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($result),
		]);
	}

	/**
	 * @param string $message
	 * @param array $params additional data that contains in log message
	 * @return void
	 */
	final public static function logInfo(string $message, array $params = []): void
	{
		$desc = [
			'message' => $message,
		];

		if (!empty($params))
		{
			$desc += $params;
		}

		$message = \Bitrix\Main\Web\Json::encode($desc);

		\CEventLog::Add([
			'SEVERITY' => \CEventLog::SEVERITY_INFO,
			'AUDIT_TYPE_ID' => static::getAuditType(\CEventLog::SEVERITY_INFO),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => $message,
		]);
	}

	/**
	 * @return string
	 */
	final protected static function getAuditType(string $actionType = "ERROR"): string
	{
		$subType = static::getAuditSubType();
		if ($subType === null)
		{
			return "SUPERSET_{$actionType}";
		}

		return "SUPERSET_{$subType}_{$actionType}";
	}

	/**
	 * @return ?string
	 */
	protected static function getAuditSubType(): ?string
	{
		return null;
	}
}
