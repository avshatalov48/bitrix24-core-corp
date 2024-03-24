<?php

namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Tasks\Item\Task\Template;
use CTaskItem;

class Base extends Controller
{
	public function getAutoWiredParameters(): array
	{
		return [
			new Parameter(
				CTaskItem::class,
				function ($className, $id) {
					if (($id = (int)$id) <= 0)
					{
						$this->addError(new Error('wrong task id'));
						return null;
					}
					return new $className($id, $this->getUserId());
				}
			),
			new Parameter(
				Template::class,
				function ($className, $id) {
					if (($id = (int)$id) <= 0)
					{
						$this->addError(new Error('wrong task id'));
						return null;
					}
					return new $className($id, $this->getUserId());
				}
			),
		];
	}

	protected function filterFields(array $fields): array
	{
		foreach (array_keys($fields) as $field)
		{
			if (mb_strpos($field, '~') === 0)
			{
				$fields[str_replace('~', '', $field)] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $fields;
	}

	protected function getUserId(): int
	{
		return (int)CurrentUser::get()->getId();
	}
}