<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;

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

		if ($groupId > 0)
		{
			return $this->checkGroupPermission($groupId);
		}

		return false;
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