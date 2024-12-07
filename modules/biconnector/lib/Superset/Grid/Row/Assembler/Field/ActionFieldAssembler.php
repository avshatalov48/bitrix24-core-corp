<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\LimitManager;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

class ActionFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$manager = LimitManager::getInstance()->setIsSuperset();
		if ($value['EDIT_URL'] === '' || !$manager->checkLimit())
		{
			return '';
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$value['ID'],
			'TYPE' => $value['TYPE'],
			'OWNER_ID' => $value['OWNER_ID'],
		]);

		if (
			(
				$value['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM
				|| $value['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_MARKET
			)
			&& !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_COPY, $accessItem)
		)
		{
			return '';
		}

		if (
			$value['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM
			&& !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT, $accessItem)
		)
		{
			return '';
		}

		$params = Json::encode([
			'dashboardId' => (int)$value['ID'],
			'type' => $value['TYPE'],
			'editUrl' => $value['EDIT_URL'],
			'appId' => $value['APP_ID'],
		]);

		return "<a style='cursor: pointer' onclick=\"BX.BIConnector.SupersetDashboardGridManager.Instance.showLoginPopup({$params}, 'grid')\">" . Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_ACTION_TITLE_EDIT') . '</a>';
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
				'EDIT_URL' => $row['data']['EDIT_URL'] ?? '',
				'ID' => (int)$row['data']['ID'],
				'TYPE' => $row['data']['TYPE'],
				'APP_ID' => $row['data']['APP_ID'],
				'CREATED_BY_ID' => $row['data']['CREATED_BY_ID'],
				'OWNER_ID' => $row['data']['OWNER_ID'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}
