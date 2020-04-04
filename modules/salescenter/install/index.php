<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("salescenter")) return;

class salescenter extends CModule
{
	public $MODULE_ID = "salescenter";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = "Y";

	protected $requiredModules = ['crm', 'sale', 'im', 'imopenlines', 'landing'];

	public function __construct()
	{
		$arModuleVersion = [];

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = SALESCENTER_VERSION;
			$this->MODULE_VERSION_DATE = SALESCENTER_VERSION;
		}

		$this->MODULE_NAME = GetMessage("SALESCENTER_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("SALESCENTER_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$notInstalledRequiredModules = [];
			foreach($this->requiredModules as $moduleId)
			{
				if(!\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId))
				{
					$notInstalledRequiredModules[] = $moduleId;
				}
			}
			if(!empty($notInstalledRequiredModules))
			{
				$APPLICATION->ThrowException(GetMessage('SALESCENTER_INSTALL_DEPENDENCIES_ERROR', ['#MODULES#' => implode(', ', $notInstalledRequiredModules)]));
			}
			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = [])
	{
		global $DB, $APPLICATION;
		$errors = false;

		if(!$DB->Query("SELECT 'x' FROM b_salescenter_page", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onAfterDemoCreate', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onAfterDemoCreate');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', '\Bitrix\Landing\Internals\Landing::OnAfterDelete', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onDeleteLanding');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onBuildTemplatePreviewUrl', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBuildTemplatePreviewUrl');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onHookExec', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onHookExec');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onLandingPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingPublication');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onLandingAfterUnPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingAfterUnPublication');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onBeforeSiteRecycle', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBeforeSiteRecycle');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onBeforeLandingRecycle', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBeforeLandingRecycle');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnSaleOrderPaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onSalePayOrder');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnSalePsServiceProcessRequestBeforePaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onSalePsServiceProcessRequestBeforePaid');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnPrintableCheckSend', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnPrintableCheckSend');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnCheckPrintError', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnCheckPrintError');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('crm', 'OnActivityAdd', 'salescenter', '\Bitrix\SalesCenter\Integration\CrmManager', 'onActivityAdd');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('pull', 'OnGetDependentModule', 'salescenter', '\Bitrix\SalesCenter\Driver', 'onGetDependentModule', 1000);

		RegisterModule($this->MODULE_ID);

		if(\Bitrix\Main\Loader::includeModule($this->MODULE_ID))
		{
			\Bitrix\SalesCenter\Integration\ImManager::installApplication();
		}

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
			true, true
		);

		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(["savedata" => $_REQUEST["savedata"]]);
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
		}

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSaleOrderPaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onSalePayOrder');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSalePsServiceProcessRequestBeforePaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onSalePsServiceProcessRequestBeforePaid');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnPrintableCheckSend', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnPrintableCheckSend');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnCheckPrintError', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnCheckPrintError');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onAfterDemoCreate', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onAfterDemoCreate');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', '\Bitrix\Landing\Internals\Landing::OnAfterDelete', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onDeleteLanding');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onBuildTemplatePreviewUrl', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBuildTemplatePreviewUrl');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onHookExec', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onHookExec');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onLandingPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingPublication');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onLandingAfterUnPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingAfterUnPublication');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onBeforeSiteRecycle', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBeforeSiteRecycle');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onBeforeLandingRecycle', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onBeforeLandingRecycle');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('crm', 'OnActivityAdd', 'salescenter', '\Bitrix\SalesCenter\Integration\CrmManager', 'onActivityAdd');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('pull', 'OnGetDependentModule', 'salescenter', '\Bitrix\SalesCenter\Driver', 'onGetDependentModule');
		\Bitrix\SalesCenter\Integration\ImManager::unInstallApplication();

		UnRegisterModule($this->MODULE_ID);

		return true;
	}

	function UnInstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$errors = false;

		if(!isset($params['savedata']) || $params['savedata'] !== "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		UnRegisterModule($this->MODULE_ID);
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}
}