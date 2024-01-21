<?php

namespace Bitrix\BIConnector\Superset\Logger;

use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class Logger
{
	/**
	 * @param Error[] $errors
	 * @return void
	 */
	final static public function logErrors(array $errors): void
	{
		$result = [];
		foreach ($errors as $error)
		{
			$result['errors'][] = $error->getMessage();
		}

		\CEventLog::Add([
			'SEVERITY' => 'ERROR',
			'AUDIT_TYPE_ID' => static::getAuditType(),
			'MODULE_ID' => 'biconnector',
			'DESCRIPTION' => Json::encode($result),
		]);
	}

	/**
	 * @return string
	 */
	final protected static function getAuditType(): string
	{
		$subType = static::getAuditSubType();
		if ($subType === null)
		{
			return 'SUPERSET_ERROR';
		}

		return 'SUPERSET_' . $subType . '_ERROR';
	}

	/**
	 * @return ?string
	 */
	protected static function getAuditSubType(): ?string
	{
		return null;
	}
}
