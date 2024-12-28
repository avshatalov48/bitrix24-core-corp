<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\LimitManager;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

final class EditAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'edit';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_EDIT') ?? '';
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

		if (
			(
				$rawFields['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM
				|| $rawFields['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_MARKET
			)
			&& !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_COPY, $accessItem)
		)
		{
			return null;
		}

		if (
			$rawFields['TYPE'] === SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM
			&& !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT, $accessItem)
		)
		{
			return null;
		}

		$manager = LimitManager::getInstance()->setIsSuperset();
		if (!$manager->checkLimit())
		{
			return null;
		}

		$params =  Json::encode([
			'dashboardId' => (int)$rawFields['ID'],
			'type' => $rawFields['TYPE'],
			'editUrl' => $rawFields['EDIT_URL'],
			'appId' => $rawFields['APP_ID'],
		]);
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.showLoginPopup({$params}, 'grid_menu')";

		return parent::getControl($rawFields);
	}
}
