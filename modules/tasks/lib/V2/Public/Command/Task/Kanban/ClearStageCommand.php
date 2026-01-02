<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;

class ClearStageCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $stageId
	)
	{

	}

	protected function executeInternal(): Result
	{
		$taskStageRepository = Container::getInstance()->getTaskStageRepository();

		$handler = new ClearStageHandler($taskStageRepository);

		$handler($this);

		return new Result();
	}
}
