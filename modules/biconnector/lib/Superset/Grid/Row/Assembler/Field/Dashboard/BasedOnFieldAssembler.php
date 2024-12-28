<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class BasedOnFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$sourceId = $value['SOURCE_ID'];
		$sourceTitle = htmlspecialcharsbx($value['SOURCE_TITLE']);
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_MARKET');
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_SYSTEM');
		}

		$detailUrl = $value['SOURCE_DETAIL_URL'] ?? "/bi/dashboard/detail/{$sourceId}/";

		if (empty($value['SOURCE_HAS_ZONE_URL_PARAMS']))
		{
			$link = "<a href='{$detailUrl}'>{$sourceTitle}</a>";
		}
		else
		{
			$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.showLockedByParamsPopup()'
				>
					{$sourceTitle}
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
			$value = [
				'SOURCE_ID' => $row['data']['SOURCE_ID'],
				'SOURCE_TITLE' => $row['data']['SOURCE_TITLE'],
				'TYPE' => $row['data']['TYPE'],
				'SOURCE_HAS_ZONE_URL_PARAMS' => $row['data']['SOURCE_HAS_ZONE_URL_PARAMS'],
				'SOURCE_DETAIL_URL' => $row['data']['SOURCE_DETAIL_URL'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}