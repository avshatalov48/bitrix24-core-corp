<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

/**
 * @method DashboardSettings getSettings()
 */
class StatusFieldAssembler extends FieldAssembler
{
	private const DASHBOARD_STATUS_COMPUTED_NOT_FOUND = 'NOT_FOUND';
	private const DASHBOARD_STATUS_COMPUTED_NOT_LOAD = 'NOT_LOAD';

	protected function prepareColumn($value): string
	{
		if (SupersetInitializer::isSupersetLoading())
		{
			return self::getStatusLabelByStatusType(SupersetDashboardTable::DASHBOARD_STATUS_LOAD);
		}

		if (!$this->getSettings()->isSupersetAvailable())
		{
			return self::getStatusLabelByStatusType(self::DASHBOARD_STATUS_COMPUTED_NOT_LOAD);
		}

		if ($value['EDIT_URL'] === '' && in_array($value['STATUS'], SupersetDashboard::getActiveDashboardStatuses(), true))
		{
			return self::getStatusLabelByStatusType(self::DASHBOARD_STATUS_COMPUTED_NOT_FOUND);
		}

		return self::getStatusLabelByStatusType($value['STATUS'], $value['ID']);
	}

	private function getStatusLabelByStatusType(string $status, int $dashboardId = null): string
	{
		switch ($status)
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

			case SupersetDashboardTable::DASHBOARD_STATUS_DRAFT:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_DRAFT');
				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-default ui-label-fill dashboard-status-label">
						<span class="ui-label-inner">$status</span>
					</span>
				</div>
				HTML;

			case SupersetDashboardTable::DASHBOARD_STATUS_FAILED:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_FAILED');

				if ($dashboardId !== null)
				{
					$refreshBtn = <<<HTML
					<div id="restart-dashboard-load-btn" onclick="BX.BIConnector.SupersetDashboardGridManager.Instance.restartDashboardLoad($dashboardId)" class="dashboard-status-label-error-btn">
						<div class="ui-icon-set --refresh-5 dashboard-status-label-error-icon"></div>
					</div>
					HTML;
				}
				else
				{
					$refreshBtn = '';
				}

				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-fill dashboard-status-label dashboard-status-label-error ui-label-danger">
						<span class="ui-label-inner">$status</span>
					</span>
					$refreshBtn
				</div>
				HTML;

			case self::DASHBOARD_STATUS_COMPUTED_NOT_FOUND:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_NOT_FOUND');

				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-fill dashboard-status-label dashboard-status-label-error ui-label-danger">
						<span class="ui-label-inner">$status</span>
					</span>
				</div>
				HTML;

			case self::DASHBOARD_STATUS_COMPUTED_NOT_LOAD:
				$status = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_STATUS_NOT_LOAD');

				return <<<HTML
				<div class="dashboard-status-label-wrapper">
					<span class="ui-label ui-label-fill dashboard-status-label dashboard-status-label-error ui-label-danger">
						<span class="ui-label-inner">$status</span>
					</span>
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
					'EDIT_URL' => $row['data']['EDIT_URL'],
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