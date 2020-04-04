<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmDealPreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$this->arResult = CCrmDeal::GetListEx(
			array(),
			array(
				'ID' => $this->arParams['dealId'],
				'CHECK_PERMISSIONS' => 'N'
			)
		)->Fetch();

		$this->arResult['STAGE_TEXT'] = $this->getReferenceValue('DEAL_STAGE', $this->arResult['STAGE_ID']);
		$this->arResult['COMPANY_INDUSTRY_TEXT'] = $this->getReferenceValue('INDUSTRY', $this->arResult['COMPANY_INDUSTRY']);
		$this->arResult['TYPE_TEXT'] = $this->getReferenceValue('DEAL_TYPE', $this->arResult['TYPE_ID']);

		if(empty($this->arResult['CURRENCY_ID']))
			$this->arResult['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();

		$this->arResult['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($this->arResult['OPPORTUNITY'], $this->arResult['CURRENCY_ID']);

		$this->arResult['ASSIGNED_BY_FORMATTED_NAME'] = CUser::FormatName(
			$this->arParams['NAME_TEMPLATE'],
			array(
				'LOGIN' => $this->arResult['ASSIGNED_BY_LOGIN'],
				'NAME' => $this->arResult['ASSIGNED_BY_NAME'],
				'LAST_NAME' => $this->arResult['ASSIGNED_BY_LAST_NAME'],
				'SECOND_NAME' => $this->arResult['ASSIGNED_BY_SECOND_NAME']
			),
			true, false
		);
		$this->arResult['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($this->arResult['ASSIGNED_BY_FORMATTED_NAME']);

		$this->arResult['ASSIGNED_BY_PROFILE'] = CComponentEngine::MakePathFromTemplate(
			$this->arParams["PATH_TO_USER_PROFILE"],
			array("user_id" => $this->arResult["ASSIGNED_BY_ID"])
		);
		$this->arResult['ASSIGNED_BY_UNIQID'] = 'u_'.$this->randString();

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS['CACHE_MANAGER']->RegisterTag("crm_entity_name_".CCrmOwnerType::Deal."_".$this->arParams['dealId']);
		}
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