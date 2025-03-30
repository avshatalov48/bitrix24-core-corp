<?php

if(class_exists("salescenter"))
{
	return;
}

IncludeModuleLangFile(__FILE__);

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

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = GetMessage("SALESCENTER_MODULE_NAME_MSGVER_1");
		$this->MODULE_DESCRIPTION = GetMessage("SALESCENTER_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;

		$step = intval($step);
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
			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_INSTALL_TITLE_MSGVER_1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_INSTALL_TITLE_MSGVER_1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		if (!$DB->TableExists('b_salescenter_page'))
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/salescenter/install/db/' . $connection->getType() . '/install.sql');
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
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('landing', 'onLandingStartPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingStartPublication');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnSaleOrderSaved', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnSaleOrderSaved');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnSalePsServiceProcessRequestBeforePaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onSalePsServiceProcessRequestBeforePaid');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnPrintableCheckSend', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnPrintableCheckSend');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnCheckPrintError', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnCheckPrintError');
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('pull', 'OnGetDependentModule', 'salescenter', '\Bitrix\SalesCenter\Driver', 'onGetDependentModule', 1000);
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnPaymentPaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onPaymentPaid');

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendPaymentBySms',
			50
		);

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'notifications',
			'onMessageSuccessfullyEnqueued',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendPaymentByControlCenter',
			200
		);

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendCompilation',
			200
		);

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'catalog',
			'onFacebookCompilationExportFinished',
			'salescenter',
			'\Bitrix\SalesCenter\Controller\Compilation',
			'onFacebookCompilationExportFinishedHandler'
		);

		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'sale',
			'OnSaleAfterPsServiceProcessRequest',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\SaleManager',
			'onPaySystemServiceProcessRequest'
		);

		RegisterModule($this->MODULE_ID);

		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$shouldInstallApp = $request->get('install_app') === 'Y';
		if ($shouldInstallApp && \Bitrix\Main\Loader::includeModule($this->MODULE_ID))
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
		global $APPLICATION, $step;

		$step = (int)$step;
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_UNINSTALL_TITLE_MSGVER_1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif ($step === 2)
		{
			$this->UnInstallDB(["savedata" => $_REQUEST["savedata"]]);

			UnRegisterModule($this->MODULE_ID);

			$APPLICATION->IncludeAdminFile(GetMessage("SALESCENTER_UNINSTALL_TITLE_MSGVER_1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
		}

		return true;
	}

	function UnInstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		if (!isset($params['savedata']) || $params['savedata'] !== "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID."/install/db/" . $connection->getType() . "/uninstall.sql");
		}

		if ($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSaleOrderSaved', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'OnSaleOrderSaved');
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
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('landing', 'onLandingStartPublication', 'salescenter', '\Bitrix\SalesCenter\Integration\LandingManager', 'onLandingStartPublication');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('crm', 'OnActivityAdd', 'salescenter', '\Bitrix\SalesCenter\Integration\CrmManager', 'onActivityAdd');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('pull', 'OnGetDependentModule', 'salescenter', '\Bitrix\SalesCenter\Driver', 'onGetDependentModule');
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('sale', 'OnPaymentPaid', 'salescenter', '\Bitrix\SalesCenter\Integration\SaleManager', 'onPaymentPaid');

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendPaymentBySms'
		);

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'notifications',
			'onMessageSuccessfullyEnqueued',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendPaymentByControlCenter'
		);

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\CrmManager',
			'onSendCompilation'
		);

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'catalog',
			'onFacebookCompilationExportFinished',
			'salescenter',
			'\Bitrix\SalesCenter\Controller\Compilation',
			'onFacebookCompilationExportFinishedHandler'
		);

		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'sale',
			'OnSaleAfterPsServiceProcessRequest',
			'salescenter',
			'\Bitrix\SalesCenter\Integration\SaleManager',
			'onPaySystemServiceProcessRequest'
		);

		if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID))
		{
			\Bitrix\SalesCenter\Integration\ImManager::unInstallApplication();
		}

		return true;
	}
}
