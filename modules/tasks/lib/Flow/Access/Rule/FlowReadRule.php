<?php

namespace Bitrix\Tasks\Flow\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\Access\ValidationTrait;

class FlowReadRule extends AbstractRule
{
	use ValidationTrait;

	/** @var FlowAccessController */
	protected $controller;

	/** @var UserModel $user */
	protected $user;

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
		if (!$this->user->isExtranet() && $item->isForAll())
		{
			return true;
		}

		if ($item->isUserMember($this->user->getUserId()))
		{
			return true;
		}

		if (!empty(array_intersect($this->user->getUserDepartments(), $item->getDepartments())))
		{
			return true;
		}

		return false;
	}
}