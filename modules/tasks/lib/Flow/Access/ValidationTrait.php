<?php

namespace Bitrix\Tasks\Flow\Access;

use Bitrix\Main\Access\AccessibleItem;

trait ValidationTrait
{
	protected static string $modelClass = FlowModel::class;

	public function checkModel(?AccessibleItem $item): bool
	{
		if ($item instanceof static::$modelClass)
		{
			return true;
		}

		$this->controller->addError('Wrong instance for check.');
		return false;
	}
}