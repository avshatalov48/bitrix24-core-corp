<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

class ApacheSupersetControlPanelWorkspaceAnalytic extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->prepareMenuItems();
		$this->includeComponentTemplate();
	}

	private function prepareMenuItems(): void
	{
		$currentPage = $this->request->getRequestedPageDirectory();
		$menuItems = [
			[
				'ID' => 'DATASETS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_DATASETS'),
				'URL' => '/bi/dataset/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
				'IS_ACTIVE' => $currentPage === '/bi/dataset/',
			],
			[
				'ID' => 'CONNECTIONS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_CONNECTIONS'),
				'URL' => '/bi/source/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
				'IS_ACTIVE' => $currentPage === '/bi/source/',
			],
			[
				'ID' => 'STATISTICS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_STATISTICS'),
				'URL' => '/bi/statistics/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
				'IS_ACTIVE' => $currentPage === '/bi/statistics/',
			],
			[
				'ID' => 'UNUSED_ELEMENTS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_UNUSED_ELEMENTS'),
				'URL' => '/bi/unused_elements/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
				'IS_ACTIVE' => $currentPage === '/bi/unused_elements/',
			],
		];

		$this->arResult['MENU_ITEMS'] = $menuItems;
	}
}
