<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class StatusFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		switch ($value['STATUS'])
		{
			case SupersetDashboardTable::DASHBOARD_STATUS_LOAD:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_LOAD');
				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-primary ui-label-fill dashboard-status-label">
						<span class="ui-label-inner">$status</span>
					</span>
				</div>
				HTML;

			case SupersetDashboardTable::DASHBOARD_STATUS_READY:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_READY');
				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-lightgreen ui-label-fill dashboard-status-label">
						<span class="ui-label-inner">$status</span>
					</span>
				</div>
				HTML;
			case SupersetDashboardTable::DASHBOARD_STATUS_FAILED:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');
				$dashboardId = (int)$value['ID'];

				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-fill dashboard-status-label dashboard-status-label-error ui-label-danger">
						<span class="ui-label-inner">$status</span>
					</span>
					<div id="restart-dashboard-load-btn" onclick="BX.BIConnector.SupersetDashboardGridManager.Instance.restartDashboardLoad($dashboardId)" class="dashboard-status-label-error-btn">
						<div class="ui-icon-set --refresh-5 dashboard-status-label-error-icon"></div>
					</div>
				</div>
				HTML;
		}

		return '';
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
					'STATUS' => $row['data']['STATUS'],
					'ID' => $row['data']['ID'],
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
}
