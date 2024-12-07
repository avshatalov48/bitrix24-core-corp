<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\UI\EntitySelector\BaseFilter;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

class UserDataFilter extends BaseFilter
{
	public function __construct($options)
	{
		parent::__construct();

		if (isset($options['role']) && $this->isValidRole($options['role']))
		{
			$this->options['role'] = $options['role'];
		}

		$this->options['groupId'] = 0;
		if (isset($options['groupId']) && is_int($options['groupId']))
		{
			$this->options['groupId'] = $options['groupId'];
		}
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function apply(array $items, Dialog $dialog): void
	{
		$currentUserId = UserProvider::getCurrentUserId();
		$accessController = new TaskAccessController($currentUserId);

		foreach ($items as $item)
		{
			if (!($item instanceof Item))
			{
				continue;
			}

			$isSelectable = true;

			if ($this->getOption('role') === MemberTable::MEMBER_TYPE_RESPONSIBLE)
			{
				$task = TaskModel::createFromArray([
					'CREATED_BY' => $currentUserId,
					'RESPONSIBLE_ID' => $item->getId(),
					'GROUP_ID' => $this->getOption('groupId'),
				]);
				$isSelectable = $accessController->check(
					ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE,
					null,
					$task,
				);
			}
			elseif ($this->getOption('role') === MemberTable::MEMBER_TYPE_ACCOMPLICE)
			{
				$task = TaskModel::createFromArray([
					'CREATED_BY' => $currentUserId,
					'RESPONSIBLE_ID' => $currentUserId,
					'ACCOMPLICES' => $item->getId(),
					'GROUP_ID' => $this->getOption('groupId'),
				]);
				$isSelectable = $accessController->check(
					ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES,
					null,
					$task,
				);
			}

			$item->getCustomData()->set('isSelectable', $isSelectable);
		}
	}

	private function isValidRole(string $role): bool
	{
		return in_array($role, MemberTable::possibleTypes());
	}
}
