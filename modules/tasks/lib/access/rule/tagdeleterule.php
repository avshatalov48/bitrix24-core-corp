<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TagModel;

class TagDeleteRule extends AbstractRule
{
	public function execute(AccessibleItem $tag = null, $params = null): bool
	{
		if ($this->user->isAdmin())
		{
			return true;
		}

		return $this->controller->check(ActionDictionary::ACTION_TAG_EDIT, $tag, $params);
	}
}