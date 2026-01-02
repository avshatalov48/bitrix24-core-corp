<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\CheckList;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class ExpandCheckListCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $checkListId,
		#[PositiveNumber]
		public readonly int $userId,
	)
	{}

	protected function executeInternal(): Result
	{
		$result = new Result();

		try
		{
			$service = Container::getInstance()->getCheckListUserOptionService();

			$handler = new ExpandCheckListCommandHandler($service);

			$handler($this);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}

		return $result;
	}
}
