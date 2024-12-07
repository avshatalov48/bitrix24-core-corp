<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class ExportAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'export';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_EXPORT') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		if (!MarketDashboardManager::getInstance()->isExportEnabled())
		{
			return null;
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$rawFields['ID'],
			'TYPE' => $rawFields['TYPE'],
			'OWNER_ID' => (int)$rawFields['OWNER_ID'],
		]);
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_EXPORT, $accessItem))
		{
			return null;
		}

		$dashboardId = (int)$rawFields['ID'];
		$onClickHandler = <<<JS
			/** @see BX.BIConnector.SupersetDashboardGridManager.exportDashboard */
			BX.BIConnector.SupersetDashboardGridManager.Instance.exportDashboard({$dashboardId});
		JS;

		$this->onclick = $onClickHandler;

		return parent::getControl($rawFields);
	}
}
