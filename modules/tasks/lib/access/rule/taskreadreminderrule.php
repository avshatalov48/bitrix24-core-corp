<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Rule\Traits\SubordinateTrait;

class TaskReadReminderRule extends AbstractRule
{
	use SubordinateTrait;

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item)
		{
			$this->controller->addError(static::class, 'Incorrect task');

			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TASK_READ, $item))
		{
			$this->controller->addError(static::class, 'Access to read task denied');

			return false;
		}

		$reminder = $params['reminder'] ?? null;
		if ($reminderId <= 0)
		{
			$this->controller->addError(static::class, 'Incorrect reminder ID');

			return false;
		}

		return true;
	}
}