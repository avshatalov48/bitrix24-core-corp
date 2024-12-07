<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

class NameFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['TITLE']);
		$isPinned = (bool)$value['IS_PINNED'];

		$editButton = '';
		if ($this->canEditTitle($value) && !empty($value['EDIT_URL']))
		{
			$editButton = $this->getEditButton($id);
		}

		$pinButton = $this->getPinButton($id, $isPinned);

		if (empty($value['HAS_ZONE_URL_PARAMS']))
		{
			$link = "<a href='{$value['DETAIL_URL']}'>{$title}</a>";
		}
		else
		{
			$link = "
				<a 
					style='cursor: pointer' 
					onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.showLockedByParamsPopup()'
				>
					{$title}
				</a>
			";
		}

		return <<<HTML
			<div class="dashboard-title-wrapper">
				<div class="dashboard-title-wrapper__item dashboard-title-preview">
					{$link}
					<div class="dashboard-title-buttons">
						{$editButton}
						{$pinButton}
					</div>
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
					class="ui-icon-set --pencil-60 dashboard-edit-icon"
				></i>
			</a>
		HTML;
	}

	protected function getPinButton(int $dashboardId, bool $isPinned): string
	{
		$iconClass = $isPinned ? '--pin-2 dashboard-unpin-icon' : '--pin-1 dashboard-pin-icon';
		$method =
			$isPinned
				? 'BX.BIConnector.SupersetDashboardGridManager.Instance.unpin'
				: 'BX.BIConnector.SupersetDashboardGridManager.Instance.pin';

		return <<<HTML
			<a
				onclick="event.stopPropagation(); {$method}({$dashboardId})"
			>
				<i
					class="ui-icon-set {$iconClass}"
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

	/**
	 * @param array $dashboardData Dashboard fields described in prepareRow method.
	 *
	 * @return bool
	 */
	protected function canEditTitle(array $dashboardData): bool
	{
		$supersetController = new SupersetController(Integrator::getInstance());
		if (
			!$supersetController->isSupersetEnabled()
			|| !$supersetController->isExternalServiceAvailable()
			|| !($dashboardData['IS_AVAILABLE_DASHBOARD'] ?? true)
		)
		{
			return false;
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$dashboardData['ID'],
			'TYPE' => $dashboardData['TYPE'],
			'OWNER_ID' => $dashboardData['OWNER_ID'],
		]);

		return AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT, $accessItem);
	}
}
