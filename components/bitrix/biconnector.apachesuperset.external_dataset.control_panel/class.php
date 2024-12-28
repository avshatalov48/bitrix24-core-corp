<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

class ApacheSupersetControlPanelExternalDataset extends CBitrixComponent
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
		];

		$this->arResult['MENU_ITEMS'] = $menuItems;
	}
}
