<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
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
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_COPY') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		if (!DashboardTariffConfigurator::isAvailableDashboard($rawFields['APP_ID']))
		{
			return null;
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$rawFields['ID'],
			'TYPE' => $rawFields['TYPE'],
			'OWNER_ID' => (int)$rawFields['OWNER_ID'],
		]);
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_COPY, $accessItem))
		{
			return null;
		}

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
