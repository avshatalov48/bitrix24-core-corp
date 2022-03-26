<?php

namespace Bitrix\Mobile;

use Bitrix\Main\Application;
use Bitrix\Main\Result;

/**
 * Think about commands as business-scenarios that can change application state.
 *
 * For example:
 * - DocumentController validates input and invokes CreateDocumentCommand
 * - CreateDocumentCommand creates document and send some email notifications
 * - CreateDocumentCommand incapsulates business-rules and can be used from other controller/console/queue/etc.
 */
abstract class Command
{
	abstract public function execute(): Result;

	public function __invoke(): Result
	{
		return $this->execute();
	}

	/**
	 * @param \Closure $job
	 * @return Result
	 * @throws \UnexpectedValueException
	 */
	protected function transaction(\Closure $job): Result
	{
		$db = Application::getConnection();

		$db->startTransaction();

		$result = $job();

		if (!$result instanceof Result)
		{
			$db->rollbackTransaction();
			$type = is_object($result) ? get_class($result) : gettype($result);
			throw new \UnexpectedValueException("Return value must be instance of Bitrix\\Main\\Result, $type given");
		}

		if ($result->isSuccess())
		{
			$db->commitTransaction();
		}
		else
		{
			$db->rollbackTransaction();
		}

		return $result;
	}
}
