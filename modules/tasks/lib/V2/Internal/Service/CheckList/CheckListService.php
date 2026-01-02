<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\CheckList;

use Bitrix\Tasks\CheckList\Decorator\CheckListMemberDecorator;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\CheckListMapper;
use Bitrix\Tasks\V2\Internal\Entity;

class CheckListService
{
	public function __construct(
		private readonly CheckListMapper $checkListMapper,
	)
	{

	}

	public function save(array $checklists, int $taskId, int $userId): Entity\Task
	{
		$fieldsService = Container::getInstance()->getCheckListEntityFieldService();

		$checklists = $fieldsService->prepare($checklists);

		$items = $this->checkListMapper->mapToNodes($checklists);

		$decorator = new CheckListMemberDecorator(new TaskCheckListFacade(), $userId);
		$nodes = $decorator->mergeNodes(
			entityId: $taskId,
			nodes: $items,
		);

		return new Entity\Task(
			id: $taskId,
			checklist: $this->checkListMapper->mapToArray($nodes),
		);
	}

	private function apply()
	{

	}
}