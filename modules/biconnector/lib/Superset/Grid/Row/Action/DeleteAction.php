<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
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
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_DELETE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$rawFields['ID'],
			'TYPE' => $rawFields['TYPE'],
			'OWNER_ID' => (int)$rawFields['OWNER_ID'],
		]);
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_DELETE, $accessItem))
		{
			return null;
		}

		$dashboardId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.deleteDashboard({$dashboardId})";

		return parent::getControl($rawFields);
	}
}
