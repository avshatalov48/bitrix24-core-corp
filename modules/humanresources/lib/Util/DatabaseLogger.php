<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Model\LogTable;
use Bitrix\Main;
use Psr\Log;

final class DatabaseLogger extends Main\Diag\Logger implements \Bitrix\HumanResources\Contract\Util\Logger
{
	/**
	 * @param string $level
	 * @param string $message
	 *
	 * @return void
	 */
	public function logMessage(string $level, string $message): void
	{
		if (!isset(DatabaseLogger::$supportedLevels[$level]))
		{
			throw new Log\InvalidArgumentException("Log level {$level} is unsupported.");
		}

		LogTable::add([
			'MESSAGE' => "{$level}: {$message}",
		]);
	}

	/**
	 * @param array{message?: string, entityType?: string, entityId?: int, userId?: int} $context
	 * @return void
	 */
	public function write(array $context): void
	{
		$log = LogTable::getEntity()->createObject();

		if (!empty($context['message']))
		{
			$log->setMessage($context['message']);
		}

		if (isset($context['entityType']))
		{
			$log->setEntityType($context['entityType']);
		}

		if (isset($context['entityId']))
		{
			$log->setEntityId($context['entityId']);
		}

		if (isset($context['userId']))
		{
			$log->setUserId($context['userId']);
		}

		$log->save();
	}
}