<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;

class DeleteTaskStageRelationCommand extends AbstractCommand
{
	public function __construct(
		#[ElementsType(typeEnum: Type::Integer)]
		#[NotEmpty]
		public readonly array $relationIds,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$taskStageService = Container::getInstance()->getTaskStageService();

		$handler = new DeleteTaskStageRelationHandler($taskStageService);

		$handler($this);

		return new Result();
	}
}
