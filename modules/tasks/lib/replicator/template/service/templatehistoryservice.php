<?php

namespace Bitrix\Tasks\Replicator\Template\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\SystemLogTable;
use Bitrix\Tasks\Internals\Task\SystemLogObject;
use Bitrix\Tasks\Replicator\Template\Repository;

class TemplateHistoryService
{
	public function __construct(private Repository $repository)
	{
	}

	public function write(string $message, Result $operationResult, int $taskId = 0): Result
	{
		$result = new Result();

		$record = new SystemLogObject();
		$record
			->setEntityType(SystemLogTable::ENTITY_TYPE_TEMPLATE)
			->setType(SystemLogTable::TYPE_MESSAGE)
			->setCreatedDate(new DateTime())
			->setEntityId($this->repository->getTemplate()->getId())
			->setMessage($message)
			->setParamA($taskId)
		;

		if (!$operationResult->isSuccess())
		{
			$record->setError($this->makeError($operationResult));
			$record->setType(SystemLogTable::TYPE_ERROR);
		}

		$record->save();

		return $result;
	}

	private function makeError(Result $result): string
	{
		$errors = [];
		$errors[] = [
			'CODE' => 'ERROR',
			'MESSAGE' => $result->getErrors()[0]->getMessage(),
			'TYPE' => SystemLogTable::TYPE_ERROR,
		];

		return serialize($errors);
	}
}