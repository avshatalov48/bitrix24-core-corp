<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\BIConnector\LimitManager;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class ActionFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$manager = LimitManager::getInstance()->setIsSuperset();
		if ($value['EDIT_URL'] === '' || !$manager->checkLimit())
		{
			return '';
		}

		$params = \CUtil::PhpToJSObject([
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
				'ID' => $row['data']['ID'],
				'TYPE' => $row['data']['TYPE'],
				'APP_ID' => $row['data']['APP_ID'],
			];
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}
