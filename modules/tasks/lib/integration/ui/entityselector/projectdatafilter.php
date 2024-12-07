<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\UI\EntitySelector\BaseFilter;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

class ProjectDataFilter extends BaseFilter
{
	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function apply(array $items, Dialog $dialog): void
	{
		foreach ($items as $item)
		{
			if (!($item instanceof Item))
			{
				continue;
			}

			$currentUserId = UserProvider::getCurrentUserId();

			$task = TaskModel::createFromArray([
				'CREATED_BY' => $currentUserId,
				'RESPONSIBLE_ID' => $currentUserId,
				'GROUP_ID' => $item->getId(),
			]);
			$accessController = new TaskAccessController($currentUserId);

			$item->getCustomData()->set(
				'isSelectable',
				$accessController->check(ActionDictionary::ACTION_TASK_CREATE, $task),
			);
		}
	}
}