<?php

namespace Bitrix\Crm\Kanban\Helper;

final class FieldsPreparer
{
	public function getPreparedRequiredFields(array $required, array $allStages): array
	{
		if (empty($required))
		{
			return $required;
		}

		$allFieldNames = [];
		foreach ($required as $fieldNames)
		{
			$allFieldNames = [...$allFieldNames, ...$fieldNames];
		}

		$allFieldNames = array_unique($allFieldNames);

		$allNamesRow = [];

		foreach ($allFieldNames as $fieldName)
		{
			$isFieldRequiredToAllStages = true;
			foreach ($allStages as $stageName)
			{
				if (!in_array($fieldName, $required[$stageName] ?? [], true))
				{
					$isFieldRequiredToAllStages = false;
					break;
				}
			}

			if ($isFieldRequiredToAllStages)
			{
				$allNamesRow[] = $fieldName;
			}
		}

		if (empty($allNamesRow))
		{
			return $required;
		}

		foreach ($required as $stageId => $fieldNames)
		{
			$required[$stageId] = array_values(array_diff($fieldNames, $allNamesRow));
			if (empty($required[$stageId]))
			{
				unset($required[$stageId]);
			}
		}

		$required['ALL'] = $allNamesRow;

		return $required;
	}
}
