<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SettingPeriodAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'settingPeriod';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_SETTING_PERIOD') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.DashboardManager.openSettingsSlider({$dashboardId})";

		return parent::getControl($rawFields);
	}
}