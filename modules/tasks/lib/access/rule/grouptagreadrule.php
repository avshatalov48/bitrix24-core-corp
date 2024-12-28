<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

class GroupTagReadRule extends AbstractRule
{

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!array_key_exists('GROUP_ID', $params))
		{
			$this->controller->addError(static::class, 'Unable to load group info');
			return false;
		}

		$groupId = (int)$params['GROUP_ID'];

		if ($this->controller->check(ActionDictionary::ACTION_TAG_CREATE, $item, ['GROUP_ID' => $groupId]))
		{
			return true;
		}

		return $this->isUserMember($groupId);
	}

	private function isUserMember(int $groupId): bool
	{
		$permissions = Group::getUserPermissionsInGroup($groupId, $this->user->getUserId());

		return $permissions['UserIsMember'];
	}
}