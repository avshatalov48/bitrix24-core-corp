<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\PlacementTable;

// it is a new placement type.
// it shows the contents of the link from the PLACEMENT_HANDLER field.
class HandlerPlacement extends CBitrixComponent
{
	public function executeComponent()
	{
		if (!Loader::includeModule('rest') || !Loader::includeModule('biconnector'))
		{
			ShowError(Loc::getMessage('CC_BBP_ERROR_INCLUDE_MODULE'));
		}

		if (!CurrentUser::get()->canDoOperation('biconnector_dashboard_view'))
		{
			$this->includeComponentTemplate();
			return;
		}

		$select = [0 => 'TITLE', 1 => 'PLACEMENT_HANDLER', 'APP_NAME' => 'REST_APP.APP_NAME',];
		$filter = [
			'ID' => (int)$this->arParams['PLACEMENT_ID'],
			'PLACEMENT' => \Bitrix\BIConnector\Rest::BI_MENU_PLACEMENT
		];
		$placement = PlacementTable::getList([
			'select' => $select,
			'filter' => $filter,
			'cache' => [
				'ttl' => PlacementTable::CACHE_TTL
			]
		])->fetch();

		if ($placement !== false && preg_match('#^(?:/|https?://)#', $placement['PLACEMENT_HANDLER']) > 0)
		{
			$this->arResult['URL'] = $placement['PLACEMENT_HANDLER'];
		}

		if ($this->arResult && $this->arParams['SET_TITLE'] == 'Y')
		{
			global $APPLICATION;
			$title = !empty($placement['TITLE']) ? $placement['TITLE'] : $placement['APP_NAME'];
			$APPLICATION->SetTitle(htmlspecialcharsbx($title));
		}

		$this->includeComponentTemplate();
	}
}
