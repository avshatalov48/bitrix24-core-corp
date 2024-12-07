<?php

namespace Bitrix\Tasks\Flow\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\Access\ValidationTrait;

class FlowUpdateRule extends AbstractRule
{
	use ValidationTrait;

	/** @var FlowAccessController */
	protected $controller;

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$this->checkModel($item))
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		/** @var FlowModel $item */
		if ($item->getOwnerId() === $this->user->getUserId())
		{
			return true;
		}

		if ($item->getCreatorId() === $this->user->getUserId())
		{
			return true;
		}

		return false;
	}
}