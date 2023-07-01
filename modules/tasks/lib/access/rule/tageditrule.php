<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Access\Model\TagModel;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

class TagEditRule extends AbstractRule
{

	public function execute(AccessibleItem $tag = null, $params = null): bool
	{
		if (!($tag instanceof TagModel))
		{
			$this->controller->addError(static::class, 'Incorrect type');
			return false;
		}
		if ((int)($params['GROUP_ID'] ?? 0) > 0)
		{
			if ($this->user->isAdmin())
			{
				return true;
			}

			$groupId = (int)$params['GROUP_ID'];

			return $this->checkGroupPermission($groupId);

		}
		if ($tag->getOwner() === $this->user->getUserId())
		{
			return true;
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

		$permissions = Group::getUserPermissionsInGroup($group, $this->user->getUserId());

		$codes = [
			'UserIsOwner',
			'UserIsScrumMaster',
			'UserCanModerateGroup',
		];

		foreach ($codes as $code)
		{
			if ($permissions[$code])
			{
				return true;
			}
		}

		return false;
	}
}