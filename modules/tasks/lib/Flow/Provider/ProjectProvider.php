<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Tasks\Flow\Internal\FlowTable;

class ProjectProvider
{
	public function hasFlows(int $projectId): bool
	{
		if ($projectId <= 0)
		{
			return false;
		}

		$query = FlowTable::query()
			->addSelect(new ExpressionField('1', '1'))
			->where('GROUP_ID', $projectId)
			->getQuery();

		return Application::getConnection()->query($query)->fetch() !== false;
	}
}