<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Helper\Task\Dependence;

class DeleteChildrenDependencies
{
	public function __invoke(array $fullTaskData): void
	{
		$taskId = $fullTaskData['ID'];

		$children = Dependence::getSubTree($taskId)
			 ->find(['__PARENT_ID' => $taskId])
			 ?->getData();

		Dependence::delete($taskId);

		if (
			$fullTaskData['PARENT_ID']
			&& !empty($children)
		)
		{
			foreach ($children as $child)
			{
				Dependence::attach($child['__ID'], $fullTaskData['PARENT_ID']);
			}
		}
	}
}