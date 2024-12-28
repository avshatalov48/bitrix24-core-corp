<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;

class FilterPeriodFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		if ($value['FILTER_PERIOD'] !== EmbeddedFilter\DateTime::PERIOD_RANGE)
		{
			return EmbeddedFilter\DateTime::getPeriodName($value['FILTER_PERIOD']) ?? '';
		}

		$dateStart = $value['DATE_FILTER_START'];
		if ($dateStart instanceof Date)
		{
			$dateStart->toString();
		}

		$dateEnd = $value['DATE_FILTER_END'];
		if ($dateEnd instanceof Date)
		{
			$dateEnd->toString();
		}

		$preparedValue = "{$dateStart} - {$dateEnd}";

		if ($value['INCLUDE_LAST_FILTER_DATE'])
		{
			$preparedValue .= " {$this->getHint()}";
		}

		return $preparedValue;
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
				$value = [
					'DATE_FILTER_START' => $row['data']['DATE_FILTER_START'],
					'DATE_FILTER_END' => $row['data']['DATE_FILTER_END'],
					'FILTER_PERIOD' => $row['data']['FILTER_PERIOD'],
					'INCLUDE_LAST_FILTER_DATE' => $row['data']['INCLUDE_LAST_FILTER_DATE'],
				];
			}
			else
			{
				$value = [];
			}
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}

	private function getHint(): string
	{
		$hint = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_PERIOD_INCLUDE_LAST_FILTER_DATE_HINT');
		return "<span data-hint=\"{$hint}\" data-hint-interactivity><span class=\"ui-hint-icon\"></span></span>";
	}
}