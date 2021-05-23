<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Rest\RestException;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\Crm\Requisite\EntityLink;

IncludeModuleLangFile(__FILE__);

class SalesCenterCompanyContacts extends CBitrixComponent implements Controllerable
{
	public function executeComponent()
	{
		if (!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SCC_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('SCC_CRM_MODULE_ERROR'));
			return;
		}

		$this->prepareData();

		$this->includeComponentTemplate();
	}

	protected function prepareData()
	{
		$this->arResult['companyTitle'] = CrmManager::getPublishedCompanyName();
		$this->arResult['companyId'] = $this->getDefaultCompanyId();
		$this->arResult['companyPhoneList'] = $this->getCompanyPhoneList();
		$this->arResult['phoneIdSelected'] = $this->getSelectedCompanyPhone()['ID'];
		$this->arResult['phoneValueSelected'] = $this->getSelectedCompanyPhone()['VALUE'];
		$this->arResult['previewLang'] = $this->getZone() == 'ua' ? 'ua':(in_array($this->getZone(), ['ru','by','kz']) ? 'ru':'en');
	}

	private function getZone()
	{
		if (Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		else
		{
			$iterator = Bitrix\Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y']
			]);
			$row = $iterator->fetch();
			$zone = $row['ID'];
		}

		return $zone;
	}

	public function getSelectedCompanyPhone(): array
	{
		$result = [
			'ID' => 0,
			'VALUE'=>''
		];

		if(CrmManager::getDefaultMyCompanyPhoneId() == 0)
		{
			return $result;
		}

		$companyId = EntityLink::getDefaultMyCompanyId();
		if($companyId>0)
		{
			$dbRes = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $companyId,
					'TYPE_ID' => \CCrmFieldMulti::PHONE,
				]
			);
			while ($crmFieldMultiData = $dbRes->Fetch())
			{
				$phoneNumberId = $crmFieldMultiData['ID'];
				if ($phoneNumberId)
				{
					if(CrmManager::getDefaultMyCompanyPhoneId() == $phoneNumberId)
					{
						$result = [
							'ID' => $crmFieldMultiData['ID'],
							'VALUE'=>$crmFieldMultiData['VALUE']
						];
					}
				}
			}
		}

		if($result['ID'] == 0)
		{
			CrmManager::setDefaultMyCompanyPhoneId(0);
		}

		return $result;
	}

	protected function getCompanyPhoneList()
	{
		$result = [];
		$companyId = $this->getDefaultCompanyId();
		if($companyId>0)
		{
			$dbRes = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $companyId,
					'TYPE_ID' => \CCrmFieldMulti::PHONE,
				]
			);
			while ($crmFieldMultiData = $dbRes->Fetch())
			{
				$phoneNumber = (string)$crmFieldMultiData['VALUE'];
				if ($phoneNumber)
				{
					$result[] = [
						'id'=>(integer)$crmFieldMultiData['ID'],
						'value'=>(string)$phoneNumber
					] ;
				}
			}
		}

		return $result;
	}

	protected function getDefaultCompanyId() : int
	{
		return Crm\Requisite\EntityLink::getDefaultMyCompanyId();
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}

	public function updateCompanyContactsAction($id, $fields)
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->showRestError(Loc::getMessage('SCC_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('crm'))
		{
			$this->showRestError(Loc::getMessage('SCC_CRM_MODULE_ERROR'));
			return;
		}

		if(intval($id)<=0)
		{
			$this->showRestError(Loc::getMessage('SCC_CRM_MODULE_ERROR'));
			return;
		}

		$company = new \CCrmCompany(true);

		$updateFields = [
			'IS_MY_COMPANY'=>'Y',
			'TITLE'=>$fields['title']
		];
		$result = $company->Update($id, $updateFields);
		if($result <= 0)
		{
			$this->showRestError($company->LAST_ERROR);
		}

		\Bitrix\SalesCenter\Integration\CrmManager::setDefaultMyCompanyPhoneId($fields['phoneIdSelected']);

		return $result;
	}

	public function saveCompanyContactsAction($fields)
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->showRestError(Loc::getMessage('SCC_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('crm'))
		{
			$this->showRestError(Loc::getMessage('SCC_CRM_MODULE_ERROR'));
			return;
		}

		$company = new \CCrmCompany(true);
		$fields = [
			'TITLE'=>$fields['title'],
			'IS_MY_COMPANY'=>'Y',
			'FM'=>[
				\CCrmFieldMulti::PHONE =>[
					'n0'=>[
						'VALUE'=>$fields['phone'], 'VALUE_TYPE'=>'WORK'
					]]
			]];

		$companyId = $company->Add($fields);
		if($companyId <= 0)
		{
			$this->showRestError($company->LAST_ERROR);
		}
		else
		{
			$dbRes = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $companyId,
					'TYPE_ID' => \CCrmFieldMulti::PHONE,
				]
			);
			if ($crmFieldMultiData = $dbRes->Fetch())
			{
				\Bitrix\SalesCenter\Integration\CrmManager::setDefaultMyCompanyPhoneId($crmFieldMultiData['ID']);
			}
		}

		return $companyId;
	}

	protected function showRestError($error)
	{
		throw new RestException($error);
	}
}