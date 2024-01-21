<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class DeleteAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'delete';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_DELETE');
	}

	public function getControl(array $rawFields): ?array
	{
		if ($rawFields['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
		{
			return null;
		}

		$dashboardId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.deleteDashboard({$dashboardId})";

		return parent::getControl($rawFields);
	}
}
