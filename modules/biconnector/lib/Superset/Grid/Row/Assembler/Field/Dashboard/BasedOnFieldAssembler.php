<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class BasedOnFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		if ($value['ENTITY_TYPE'] === DashboardGroupRepository::TYPE_GROUP)
		{
			return '';
		}

		$sourceId = $value['SOURCE_ID'];
		$sourceTitle = htmlspecialcharsbx($value['SOURCE_TITLE']);
		$type = $value['TYPE'];

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_MARKET)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_MARKET_MSGVER_1');
		}

		if ($type === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_SOURCE_TYPE_SYSTEM');
		}

		$detailUrl = $value['SOURCE_DETAIL_URL'] ?? "/bi/dashboard/detail/{$sourceId}/";

		return "<a href='{$detailUrl}'>{$sourceTitle}</a>";
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
				'SOURCE_DETAIL_URL' => $row['data']['SOURCE_DETAIL_URL'],
				'ENTITY_TYPE' => $row['data']['ENTITY_TYPE'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}