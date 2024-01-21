<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

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
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_EDIT');
	}

	public function getControl(array $rawFields): ?array
	{
		$params = \CUtil::PhpToJSObject([
			'dashboardId' => (int)$rawFields['ID'],
			'type' => $rawFields['TYPE'],
			'editUrl' => $rawFields['EDIT_URL'],
			'appId' => $rawFields['APP_ID'],
		]);
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.showLoginPopup({$params}, 'grid_menu')";

		return parent::getControl($rawFields);
	}
}