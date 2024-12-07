<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SetDraftAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'setDraft';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_SET_DRAFT') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = $rawFields['ID'];
		if (!$dashboardId)
		{
			return parent::getControl($rawFields);
		}

		if ($rawFields['STATUS'] !== SupersetDashboardTable::DASHBOARD_STATUS_READY)
		{
			return null;
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => (int)$rawFields['ID'],
			'TYPE' => $rawFields['TYPE'],
			'OWNER_ID' => (int)$rawFields['OWNER_ID'],
		]);

		if (
			$rawFields['TYPE'] !== SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM
			|| !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT, $accessItem)
		)
		{
			return null;
		}

		$dashboardId = (int)$rawFields['ID'];
		$onClickHandler = <<<JS
			BX.BIConnector.SupersetDashboardGridManager.Instance.setDraft({$dashboardId}, {
				id: {$dashboardId},
				publish: false,
				from: 'grid_menu',
			})
		JS;

		$this->onclick = $onClickHandler;

		return parent::getControl($rawFields);
	}
}
