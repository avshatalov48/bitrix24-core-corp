<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Tasks\V2\Internal\Result\Result;
use Exception;

class UpdateResultCommand extends AbstractCommand
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
		$result = new Result();

		$resultService = Container::getInstance()->getResultService();
		$consistencyResolver = Container::getInstance()->getConsistencyResolver();
		$handler = new UpdateResultHandler($resultService, $consistencyResolver);

		try
		{
			$object = $handler($this);

			return $result->setObject($object);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}

	public function toArray(): array
	{
		return [
			'result' => $this->result->toArray(),
			'userId' => $this->userId,
		];
	}
}
