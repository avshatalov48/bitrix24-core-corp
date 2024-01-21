<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
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
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_EXPORT');
	}

	public function getControl(array $rawFields): ?array
	{
		if (!MarketDashboardManager::getInstance()->isExportEnabled())
		{
			return null;
		}

		if ($rawFields['TYPE'] !== SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
		{
			return null;
		}

		$dashboardId = (int)$rawFields['ID'];
		$appId = \CUtil::JSEscape($rawFields['APP_ID']);
		$type = \CUtil::JSEscape($rawFields['TYPE']);
		$onClickHandler = <<<JS
			BX.BIConnector.SupersetDashboardGridManager.Instance.exportDashboard({$dashboardId}, {
				id: {$dashboardId},
				appId: '{$appId}',
				type: '{$type}'.toLowerCase(),
				from: 'grid_menu',
			})
		JS;

		$this->onclick = $onClickHandler;

		return parent::getControl($rawFields);
	}
}
