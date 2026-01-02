<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\DetailLinkFieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

class NameFieldAssembler extends DetailLinkFieldAssembler
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

		$classTypePrefix = 'dashboard';
		$pinButton = '';
		if ($value['ENTITY_TYPE'] === DashboardGroupRepository::TYPE_DASHBOARD)
		{
			$pinButton = $this->getPinButton($id, $isPinned);
		}

		if ($value['IS_TARIFF_RESTRICTED'])
		{
			$onclick = '';
			$sliderCode = DashboardTariffConfigurator::getSliderRestrictionCodeByAppId($value['APP_ID']);
			if (!empty($sliderCode))
			{
				$onclick = "onclick=\"top.BX.UI.InfoHelper.show('{$sliderCode}');\"";
			}

			$link = "
				<a 
					style='cursor: pointer' 
					{$onclick}
				>
					<span class='tariff-lock'></span> {$title}
				</a>
			";
		}
		elseif ($value['ENTITY_TYPE'] === DashboardGroupRepository::TYPE_GROUP)
		{
			Extension::load('ui.icons.disk');
			$classTypePrefix = 'group';

			$eventGroup = Json::encode([
				'ID' => (int)$value['ID'],
				'TITLE' => $value['TITLE'],
			]);

			$iconClass =
				$value['TYPE'] === SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM
					? 'ui-icon-file-air-folder-24'
					: 'ui-icon-file-air-folder-person'
			;

			$dashboardCount = $value['COUNT_DASHBOARDS'];
			$subtitle = Loc::getMessagePlural('BI_GROUP_SUBTITLE', $dashboardCount, [
				'#COUNT#' => $dashboardCount,
			]);

			$link = "
				<div class ='biconnector-grid-group-name'>
					<div class='ui-icon {$iconClass} biconnector-grid-group-icon'>
						<i style='margin-left: 0;'></i>
					</div>
					<div>
						<a
							class ='biconnector-grid-group-name-link'
							onclick='BX.BIConnector.SupersetDashboardGridManager.Instance.handleGroupTitleClick($eventGroup)'
						>
							{$title}
						</a>
						<div class='biconnector-grid-group-name-subtitle'>{$subtitle}</div>
					</div>
				</div>
			";
		}
		elseif (!$value['IS_ACCESS_ALLOWED'])
		{
			$link = $title;
		}
		else
		{
			$link = "<a href='{$value['DETAIL_URL']}' target='_blank'>{$title}</a>";
		}

		$buttons = '';
		if (!empty($editButton) || !empty($pinButton))
		{
			$buttons = "
				<div class='dashboard-title-buttons'>
					{$editButton}
					{$pinButton}
				</div>
			";
		}

		return <<<HTML
			<div class="{$classTypePrefix}-title-wrapper">
				<div class="{$classTypePrefix}-title-wrapper__item {$classTypePrefix}-title-preview">
					{$link}
					{$buttons}
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
				: 'BX.BIConnector.SupersetDashboardGridManager.Instance.pin'
		;

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
