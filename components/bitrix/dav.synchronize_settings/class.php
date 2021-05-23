<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;


Loc::loadMessages(__FILE__);
Loader::includeModule('dav');

/**
 * Class CDavSynchronizeSettings
 */
class CDavSynchronizeSettings extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return array();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return array();
	}

	public function saveSettingsAction()
	{
		global $USER;
		if ($USER->IsAuthorized())
		{
			$postParams = $this->request->getPostList()->toArray();
			if (!empty($postParams["data"]))
				$this->saveParams($postParams["data"]);

			return true;
		}
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->setFrameMode(false);
		$APPLICATION->SetTitle(Loc::getMessage("DAV_SYNCHRONIZE_TITLE"));

		if (isset($this->arParams["COMPONENT_AJAX_LOAD"]) && $this->arParams["COMPONENT_AJAX_LOAD"] == "Y")
		{
			$this->arParams["COMPONENT_AJAX_LOAD"] = "Y";
		}
		else
		{
			$this->arParams["COMPONENT_AJAX_LOAD"] = "N";
		}

		global $USER;
		if ($USER->IsAuthorized())
		{
			$postParams = $this->request->getPostList()->toArray();
			if (!empty($postParams) && check_bitrix_sessid() && $this->arParams["COMPONENT_AJAX_LOAD"] == "N")
				$this->saveParams($postParams);

			$this->prepareData();
		}
		else
		{
			$this->arResult["MESSAGE"] = array("MESSAGE" => Loc::getMessage("dav_app_synchronize_auth"), "TYPE" => "ERROR");
		}

		$this->IncludeComponentTemplate();
	}

	protected function prepareData()
	{
		global $USER;
		$arResult = array();
		$arResult['COMMON']['DEFAULT_COLLECTION_TO_SYNC']['VALUE'] = CDavAddressbookHandler::GetDefaultResourceProviderName($USER->GetID());

		$arResult['ACCOUNTS']['ENABLED'] = CDavAccounts::IsResourceSyncEnabled($USER->GetID());
		$arResult['ACCOUNTS']['UF_DEPARTMENT'] = CDavAccounts::GetResourceSyncUfDepartments($USER->GetID());
		$providerVariants['accounts'] = Loc::getMessage('DAV_ACCOUNTS');
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('extranet'))
		{
			$arResult['EXTRANET_ACCOUNTS']['ENABLED'] = CDavExtranetAccounts::IsResourceSyncEnabled($USER->GetID());
			$providerVariants['extranetAccounts'] = Loc::getMessage('DAV_EXTRANET_ACCOUNTS');
		}
		if (Loader::includeModule('crm'))
		{
			if (CCrmContact::CheckExportPermission())
			{
				$arResult['CONTACTS']['ENABLED'] = CDavCrmContacts::IsResourceSyncEnabled($USER->GetID());
				$arResult['CONTACTS']['MAX_COUNT'] = CDavCrmContacts::GetResourceSyncMaxCount($USER->GetID());
				$arResult['CONTACTS']['FILTER']['ITEMS'] = CDavCrmContacts::GetListOfFilterItems();
				$arResult['CONTACTS']['FILTER']['VALUE'] = CDavCrmContacts::GetResourceSyncFilterOwner($USER->GetID());
				$providerVariants['crmContacts'] = Loc::getMessage('DAV_CONTACTS');
			}

			if (CCrmCompany::CheckExportPermission())
			{
				$arResult['COMPANIES']['ENABLED'] = CDavCrmCompanies::IsResourceSyncEnabled($USER->GetID());
				$arResult['COMPANIES']['MAX_COUNT'] = CDavCrmCompanies::GetResourceSyncMaxCount($USER->GetID());
				$arResult['COMPANIES']['FILTER']['ITEMS'] = CDavCrmCompanies::GetListOfFilterItems();
				$arResult['COMPANIES']['FILTER']['VALUE'] = CDavCrmCompanies::GetResourceSyncFilterOwner($USER->GetID());
				$providerVariants['crmCompanies'] = Loc::getMessage('DAV_COMPANIES');
			}
		}
		$arResult['COMMON']['DEFAULT_COLLECTION_TO_SYNC']['VARIANTS'] = $providerVariants;
		$this->arResult = $arResult;
	}

	private function saveParams($params)
	{
		global $USER;
		if (isset($params['DAV_SYNC_SETTINGS']['COMMON']['DEFAULT_COLLECTION_TO_SYNC']))
		{
			CDavAddressbookHandler::SetDefaultResourceProviderName($params['DAV_SYNC_SETTINGS']['COMMON']['DEFAULT_COLLECTION_TO_SYNC'], $USER->GetID());
		}

		if (isset($params['DAV_SYNC_SETTINGS']['ACCOUNTS']))
		{
			CDavAccounts::SetResourceSyncSetting($params['DAV_SYNC_SETTINGS']['ACCOUNTS'], $USER->GetID());
		}

		if (isset($params['DAV_SYNC_SETTINGS']['EXTRANET_ACCOUNTS']))
		{
			CDavExtranetAccounts::SetResourceSyncSetting($params['DAV_SYNC_SETTINGS']['EXTRANET_ACCOUNTS'], $USER->GetID());
		}

		if (isset($params['DAV_SYNC_SETTINGS']['CONTACTS']))
		{
			CDavCrmContacts::SetResourceSyncSetting($params['DAV_SYNC_SETTINGS']['CONTACTS'], $USER->GetID());
		}

		if (isset($params['DAV_SYNC_SETTINGS']['COMPANIES']))
		{
			CDavCrmCompanies::SetResourceSyncSetting($params['DAV_SYNC_SETTINGS']['COMPANIES'], $USER->GetID());
		}
	}
}
