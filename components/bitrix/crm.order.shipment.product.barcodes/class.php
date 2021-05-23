<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CCrmOrderShipmentProductListBarcodes extends \CBitrixComponent
{
	private $userId = 0;
	private $userPermissions;
	private $errors = [];

	private function init()
	{
		if(!CModule::IncludeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!CModule::IncludeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!CModule::IncludeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');
			return false;
		}

		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $this->userPermissions))
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return false;
		}

		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		CJSCore::Init(array('date', 'popup', 'ajax', 'tooltip'));
		return true;
	}

	private function showErrors()
	{
		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{

		if(!$this->init())
		{
			$this->showErrors();
			return;
		}

		$this->arResult['BASKET_ID'] = isset($this->arParams['BASKET_ID']) ? intval($this->arParams['BASKET_ID']) : 0;
		$this->arResult['STORE_ID'] = isset($this->arParams['STORE_ID']) ? intval($this->arParams['STORE_ID']) : 0;
		$this->arResult['ADDITIONAL_CSS_PATH'] = isset($this->arParams['ADDITIONAL_CSS_PATH']) ? trim($this->arParams['ADDITIONAL_CSS_PATH']) : '';
		$this->arResult['SERVICE_URL'] = $this->getPath().'/ajax.php';
		$this->IncludeComponentTemplate();
	}
}
?>