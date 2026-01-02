<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Exception;

class AddResultCommand extends AbstractCommand
{
	public function __construct(
		public readonly Entity\Result $result,
		#[PositiveNumber]
		public readonly int $userId,
	)
	{
	}

	protected function executeInternal(): Result
	{
		$response = new Result();

		try
		{
			$resultService = Container::getInstance()->getResultService();
			$consistencyResolver = Container::getInstance()->getConsistencyResolver();
			$handler = new AddResultHandler($resultService, $consistencyResolver);
			$object = $handler($this);

			return $response->setObject($object);
		}
		catch (Exception $e)
		{
			return $response->addError(Error::createFromThrowable($e));
		}
	}

	protected function validateInternal(): ValidationResult
	{
		$validationResult = new ValidationResult();
		if ((int)$this->result->taskId <= 0)
		{
			$validationResult->addError(new Error('Task id must be positive'));
		}

		return $validationResult;
	}

	public function toArray(): array
	{
		return [
			'result' => $this->result->toArray(),
			'userId' => $this->userId,
		];
	}
}
