<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Detector;

class ChecklistOperationGrouper
{
	/**
	 * Groups operations by checklist name
	 * 
	 * @param ChecklistOperation[] $operations
	 * @return ChecklistOperationGroup[] Indexed by checklist name
	 */
	public function groupOperations(array $operations): array
	{
		$groupedByChecklist = [];
		
		foreach ($operations as $operation)
		{
			$checklistName = $operation->getChecklistName();
			
			if (!isset($groupedByChecklist[$checklistName]))
			{
				$groupedByChecklist[$checklistName] = new ChecklistOperationGroup();
			}
			
			$groupedByChecklist[$checklistName]->addOperation($operation);
		}
		
		return $groupedByChecklist;
	}

	/**
	 * Determines if operations should be grouped or sent individually
	 * 
	 * @param ChecklistOperationGroup $operationGroup
	 * @return bool
	 */
	public function shouldGroupOperations(ChecklistOperationGroup $operationGroup): bool
	{
		return $operationGroup->hasMultipleOperationTypes();
	}

	/**
	 * Gets all checklist names that have operations
	 * 
	 * @param ChecklistOperation[] $operations
	 * @return string[]
	 */
	public function getChecklistNames(array $operations): array
	{
		$names = [];
		foreach ($operations as $operation)
		{
			$names[] = $operation->getChecklistName();
		}
		
		return array_unique($names);
	}
}