<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;

class TagSearchRule extends AbstractRule
{

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->controller->addError(static::class, 'Unable to load socialnetwork');
			return false;
		}

		if (!array_key_exists('GROUP_ID', $params))
		{
			$this->controller->addError(static::class, 'Unable to load group info');
			return false;
		}

		$group = (int)$params['GROUP_ID'];

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (!FeaturePermRegistry::getInstance()->get(
			$group,
			'tasks',
			'edit_tasks',
			$this->user->getUserId())
			&&
			!FeaturePermRegistry::getInstance()->get(
				$group,
				'tasks',
				'create_tasks',
				$this->user->getUserId())
		)
		{
			$this->controller->addError(static::class, 'Access to find tag denied by group permissions');
			return false;
		}

		return true;
	}
}