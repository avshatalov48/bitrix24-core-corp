<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Util\User;

class TagCreateRule extends AbstractRule
{

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!array_key_exists('GROUP_ID', $params))
		{
			$this->controller->addError(static::class, 'Unable to load group info');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$groupId = (int)$params['GROUP_ID'];

		if ($groupId < 1)
		{
			return false;
		}

		if ($this->checkGroupPermission($groupId))
		{
			return true;
		}

		$isExtranetUser = User::isExternalUser($this->user->getUserId());

		if ($isExtranetUser)
		{
			return false;
		}

		// if user can add tasks to flow, he can create flow group tags
		$groupData = WorkgroupTable::query()
			->setSelect(['VISIBLE', 'OPENED'])
			->setFilter(['ID' => $groupId])
			->setLimit(1)
			->fetch()
		;

		$isGroupSecret = $groupData['VISIBLE'] === 'N' && $groupData['OPENED'] === 'N';
		if (!$isGroupSecret)
		{
			foreach ($this->getGroupFlows($groupId) as $flow)
			{
				if (FlowAccessController::can($this->user, FlowAction::READ, $flow->getId()))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function getGroupFlows(int $groupId) : ?FlowEntityCollection
	{
		return FlowTable::query()
			->setSelect(['ID'])
			->setFilter(['GROUP_ID' => $groupId])
			->fetchCollection()
		;
	}

	private function checkGroupPermission(int $group): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->controller->addError(static::class, 'Unable to load sonet');
			return false;
		}

		if (!FeaturePermRegistry::getInstance()->get(
			$group,
			'tasks',
			'create_tasks',
			$this->user->getUserId()
		))
		{
			$this->controller->addError(static::class, 'Access to create tag denied by group permissions');
			return false;
		}

		return true;
	}
}