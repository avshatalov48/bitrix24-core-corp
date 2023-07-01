<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmWidgetSaleTargetComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath(
			'PATH_TO_DEAL_CATEGORY_LIST',
			$this->arParams['PATH_TO_DEAL_CATEGORY_LIST'] ?? '',
			COption::GetOptionString('crm', 'path_to_deal_category_list')
		);
		$this->arResult['USER_SELECTOR_DATA'] = $this->getUserSelectorData();
		$this->arResult['DEAL_CATEGORIES'] = $this->getDealCategories();
		$this->includeComponentTemplate();
	}

	private function getUserSelectorData()
	{
		$result = array(
			'users' => [],
			'last' => []
		);
		
		if (CModule::includeModule('socialnetwork'))
		{
			$arStructure = CSocNetLogDestination::GetStucture(array());
			$result['department'] = $arStructure['department'];
			$result['departmentRelation'] = $arStructure['department_relation'];

			$result['destSort'] = CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => "CRM_FILTER_USER",
			));

			CSocNetLogDestination::fillLastDestination(
				$result['destSort'],
				$result['last']
			);

			$users = [];
			if (isset($result["last"]["USERS"]) && is_array($result["last"]["USERS"]))
			{
				foreach ($result["last"]["USERS"] as $value)
				{
					$users[] = str_replace("U", "", $value);
				}
			}

			$result["users"] = \CSocNetLogDestination::getUsers(array("id" => $users));
		}
		return $result;
	}

	private function getDealCategories()
	{
		$result = [];
		foreach (\Bitrix\Crm\Category\DealCategory::getAll(true) as $category)
		{
			$result[] = array(
				'id' => $category['ID'],
				'name' => $category['NAME']
			);
		}

		return $result;
	}
}
