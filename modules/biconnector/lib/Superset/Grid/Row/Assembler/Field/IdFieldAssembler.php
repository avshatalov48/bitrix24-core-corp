<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class IdFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];

		if (empty($value['HAS_ZONE_URL_PARAMS']))
		{
			$link = "<a href='{$value['DETAIL_URL']}'>{$id}</a>";
		}
		else
		{
			$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.showLockedByParamsPopup()'
				>
					{$id}
				</a>
			";
		}

		return $link;
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			if ($row['data'][$columnId])
			{
				$value = $row['data'];
			}
			else
			{
				$value = [];
			}
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}
