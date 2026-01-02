<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\Dataset;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class ExportCsvDatasetAction extends BaseAction
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
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_EXPORT_DATASET') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = (int)$rawFields['ID'];
		$dashboardTitle = \CUtil::JSEscape($rawFields['NAME']);
		$onClickHandler = <<<JS
			/** @see BX.BIConnector.ExternalDatasetManager.exportDataset */
			BX.BIConnector.ExternalDatasetManager.Instance.exportDataset({id: $dashboardId, title: '{$dashboardTitle}'});
		JS;

		$this->onclick = $onClickHandler;

		return parent::getControl($rawFields);
	}
}
