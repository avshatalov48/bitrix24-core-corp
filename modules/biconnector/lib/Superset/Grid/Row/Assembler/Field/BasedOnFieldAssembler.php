<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class BasedOnFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$sourceId = $value['SOURCE_ID'];
		$sourceTitle = $value['SOURCE_TITLE'];
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_MARKET');
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_SYSTEM');
		}

		return "<a href='/bi/dashboard/detail/{$sourceId}/'>" . htmlspecialcharsbx($sourceTitle) . "</a>";
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
			$value = [
				'SOURCE_ID' => $row['data']['SOURCE_ID'],
				'SOURCE_TITLE' => $row['data']['SOURCE_TITLE'],
				'TYPE' => $row['data']['TYPE'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}
