<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmCompanyPreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$this->arResult = \CCrmCompany::GetListEx(
			array(),
			array(
				'ID' => $this->arParams['companyId'],
				'CHECK_PERMISSIONS' => 'N'
			)
		)->Fetch();

		$dbResMultiFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $this->arParams['companyId'])
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


		if(!isset($this->arResult['LOGO']))
		{
			$this->arResult['HEAD_IMAGE_URL']  = null;
		}
		else
		{
			$imageFile= \CFile::ResizeImageGet(
				$this->arResult['LOGO'],
				array('width' => 34, 'height' => 34),
				BX_RESIZE_IMAGE_EXACT);
			$this->arResult['HEAD_IMAGE_URL'] = isset($imageFile['src']) ? $imageFile['src'] : null;
		}

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS['CACHE_MANAGER']->RegisterTag("crm_entity_name_".CCrmOwnerType::Company."_".$this->arParams['companyId']);
		}
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