<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class CopyAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'copy';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_COPY');
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = (int)$rawFields['ID'];
		$appId = \CUtil::JSEscape($rawFields['APP_ID']);
		$type = \CUtil::JSEscape($rawFields['TYPE']);
		$onClickHandler = <<<JS
			BX.BIConnector.SupersetDashboardGridManager.Instance.duplicateDashboard({$dashboardId}, {
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