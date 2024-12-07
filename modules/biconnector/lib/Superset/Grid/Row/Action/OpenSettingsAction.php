<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Grid\Row\Action\BaseAction;

final class OpenSettingsAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'settings';
	}

	public function processRequest(Main\HttpRequest $request): ?Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_OPEN_SETTINGS') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.DashboardManager.openSettingsSlider({$dashboardId})";

		return parent::getControl($rawFields);
	}
}
