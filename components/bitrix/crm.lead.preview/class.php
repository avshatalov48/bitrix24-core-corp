<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmLeadPreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$this->arResult = CCrmLead::GetListEx(
			array(),
			array(
				'ID' => $this->arParams['leadId'],
				'CHECK_PERMISSIONS' => 'N'
			)
		)->Fetch();

		$this->arResult['STATUS_TEXT'] = $this->getReferenceValue('STATUS', $this->arResult['STATUS_ID']);

		$dbResMultiFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $this->arParams['leadId'])
		);

		$contactInfo = array();
		while($arMultiFields = $dbResMultiFields->Fetch())
		{
			$contactInfo[$arMultiFields['TYPE_ID']][] = $arMultiFields['VALUE'];
		}

		foreach($contactInfo as $contactInfoType => $contactInfoValue)
		{
			$this->arResult['CONTACT_INFO'][$contactInfoType] = htmlspecialcharsbx($contactInfoValue[0]);
		}

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
			$GLOBALS['CACHE_MANAGER']->RegisterTag("crm_entity_name_".CCrmOwnerType::Lead."_".$this->arParams['leadId']);
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