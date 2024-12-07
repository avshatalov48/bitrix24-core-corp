<?php

namespace Bitrix\Tasks\Flow\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\ValidationTrait;

class FlowCreateRule extends AbstractRule
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

		if (!Loader::includeModule('socialnetwork'))
		{
			$this->controller->addError(static::class, 'Module socialnetwork is required');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			Loader::includeModule('extranet')
			&& $this->user->isExtranet()
		)
		{
			$this->controller->addError(static::class, 'Forbidden for extranet users');
			return false;
		}

		return true;
	}
}