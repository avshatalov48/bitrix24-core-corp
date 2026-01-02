<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\Dashboard;

use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class OpenAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'open';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_OPEN') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = $rawFields['ID'];
		if (!$dashboardId)
		{
			return parent::getControl($rawFields);
		}

		if (!DashboardTariffConfigurator::isAvailableDashboard($rawFields['APP_ID']))
		{
			$sliderCode = DashboardTariffConfigurator::getSliderRestrictionCodeByAppId($rawFields['APP_ID']);
			if (!empty($sliderCode))
			{
				$this->onclick = "top.BX.UI.InfoHelper.show('{$sliderCode}');";

				return parent::getControl($rawFields);
			}
		}

		$this->onclick = "window.open(`{$rawFields['DETAIL_URL']}`, '_blank');";

		return parent::getControl($rawFields);
	}
}
