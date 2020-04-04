<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmProductPreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$this->arResult = \CCrmProduct::GetList(
			array(),
			array(
				'ID' => $this->arParams['productId'],
				'CHECK_PERMISSIONS' => 'N'
			),
			array('*')
		)->Fetch();

		$this->arResult['CATALOG_ID'] = isset($this->arResult['CATALOG_ID']) ? intval($this->arResult['CATALOG_ID']) : CCrmCatalog::EnsureDefaultExists();

		if(    $this->arResult['SECTION_ID'] > 0
			&& $arSection = CIBlockSection::GetByID($this->arResult['SECTION_ID'])->Fetch())
		{
			$this->arResult['SECTION_NAME'] = $arSection['NAME'];
		}
		else
		{
			$this->arResult['SECTION_NAME'] = GetMessage('CRM_SECTION_NOT_SELECTED');
		}

		$this->arResult['PRICE_FORMATTED'] =  CCrmProduct::FormatPrice($this->arResult);
		$this->arResult['ACTIVE_FORMATTED'] = GetMessage(isset($this->arResult['ACTIVE']) && $this->arResult['ACTIVE'] == 'Y' ? 'MAIN_YES' : 'MAIN_NO');
	}

	protected function getReferenceValue($referenceName, $elementId)
	{
		$referenceValues = CCrmStatus::GetStatusListEx($referenceName);
		if(isset($referenceValues[$elementId]))
		{
			return htmlspecialcharsbx($referenceValues[$elementId]);
		}
		return null;
	}

	public function executeComponent()
	{
		$this->prepareData();
		if($this->arResult['ID'] > 0)
		{
			$this->includeComponentTemplate();
		}
	}
}