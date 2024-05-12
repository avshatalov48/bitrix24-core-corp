<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\UI\Extension;

class NameFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['TITLE']);

		if ($this->canEditTitle($value) && !empty($value['EDIT_URL']))
		{
			$editButton = $this->getEditButton($id);
		}
		else
		{
			$editButton = '';
		}

		return <<<HTML
			<div class="dashboard-title-wrapper">
				<div class="dashboard-title-wrapper__item dashboard-title-preview">
					<a href='/bi/dashboard/detail/{$id}/'>{$title}</a>
					{$editButton}
				</div>
			</div>
		HTML;
	}

	protected function getEditButton(int $dashboardId): string
	{
		Extension::load('ui.design-tokens');
		return <<<HTML
			<a
				onclick="event.stopPropagation(); BX.BIConnector.SupersetDashboardGridManager.Instance.renameDashboard({$dashboardId})"
			>
				<i
					class="ui-icon-set --pencil-60"
					style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: none"
				></i>
			</a>
		HTML;
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
					'TITLE' => $row['data']['TITLE'],
					'ID' => $row['data']['ID'],
					'EDIT_URL' => $row['data']['EDIT_URL'],
					'TYPE' => $row['data']['TYPE'],
					'STATUS' => $row['data']['STATUS'],
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

	/**
	 * @param array $dashboardData Dashboard fields described in prepareRow method.
	 *
	 * @return bool
	 */
	protected function canEditTitle(array $dashboardData): bool
	{
		$supersetController = new SupersetController(ProxyIntegrator::getInstance());
		if (!$supersetController->isSupersetEnabled() || !$supersetController->isExternalServiceAvailable())
		{
			return false;
		}

		return (
			$dashboardData['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM
			&& $dashboardData['STATUS'] === SupersetDashboardTable::DASHBOARD_STATUS_READY
		);
	}
}
