<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Tasks\Scrum\Controllers\DoD as DodController;
use Bitrix\Tasks\Util\User;
use Bitrix\TasksMobile\Provider\ChecklistProvider;

class Dod extends DodController
{
	public function configureActions(): array
	{
		return [
			'getDodTree' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getDodTreeAction(int $groupId, int $taskId, int $typeId): ?array
	{
		$checklist = new ChecklistProvider();
		$checklistItems = $this->getListItems($groupId, $taskId, $typeId);
		$objectTreeStructure = $checklist->buildTreeStructure($checklistItems);

		return $this->convertKeysToCamelCase($objectTreeStructure->toTreeArray());
	}
}
