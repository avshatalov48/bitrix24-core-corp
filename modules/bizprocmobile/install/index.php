<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class BizprocMobile extends CModule
{
	public $MODULE_ID = 'bizprocmobile';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	private $workspaceClass = \Bitrix\BizprocMobile\Workspace::class;

	public function __construct()
	{
		$arModuleVersion = [];
		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && $arModuleVersion['VERSION'] && $arModuleVersion['VERSION_DATE'])
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('BIZPROCMOBILE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('BIZPROCMOBILE_MODULE_DESCRIPTION');
	}

	public function installDB()
	{
		ModuleManager::registerModule($this->MODULE_ID);

		$eventManager = EventManager::getInstance();

		$eventManager->registerEventHandler(
			'mobileapp',
			'onJNComponentWorkspaceGet',
			$this->MODULE_ID,
			$this->workspaceClass,
			'getPath'
		);

		$eventManager->registerEventHandler(
			'mobile',
			'onBeforeTabsGet',
			$this->MODULE_ID,
			\Bitrix\BizprocMobile\BizpocTab::class,
			'onBeforeTabsGet'
		);

		return true;
	}

	public function uninstallDB($arParams = [])
	{
		$eventManager = EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'mobileapp',
			'onJNComponentWorkspaceGet',
			$this->MODULE_ID,
			$this->workspaceClass,
			'getPath'
		);

		$eventManager->unRegisterEventHandler(
			'mobile',
			'onBeforeTabsGet',
			$this->MODULE_ID,
			\Bitrix\BizprocMobile\BizpocTab::class,
			'onBeforeTabsGet'
		);

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function installFiles()
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bizprocmobile/install/mobileapp/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/mobileapp/',
			true,
			true
		);

		return true;
	}

	public function uninstallFiles()
	{
	}

	public function installEvents()
	{
	}

	public function uninstallEvents()
	{
	}

	private function installDependencies()
	{
		$pathToMobileApp = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mobileapp/install/index.php';

		if (!ModuleManager::isModuleInstalled('mobile') && file_exists($pathToMobileApp))
		{
			include_once($pathToMobileApp);

			$mobile = new mobile();
			$mobile->InstallFiles();
			$mobile->InstallDB();
		}
	}

	public function doInstall()
	{
		global $USER, $APPLICATION;

		if (!$USER->isAdmin())
		{
			return;
		}

		if (!ModuleManager::isModuleInstalled('bizproc'))
		{
			$APPLICATION->throwException(Loc::getMessage('BIZPROCMOBILE_MODULE_INSTALL_ERROR_BIZPROC'));
			$this->showInstallStep(1);
		}
		else
		{
			$this->installDB();
			$this->installFiles();
			$this->installEvents();
			$this->installDependencies();

			$this->showInstallStep(2);
		}
	}

	protected function showInstallStep($step)
	{
		global $APPLICATION;

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('BIZPROCMOBILE_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step.php'
		);
	}

	public function doUninstall()
	{
		global $USER, $APPLICATION, $step;

		if (!$USER->isAdmin())
		{
			return;
		}

		$step = (int)$step;
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('BIZPROCMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
			);
		}
		elseif($step === 2)
		{
			$this->uninstallDB();
			$this->uninstallFiles();
			$this->uninstallEvents();

			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('BIZPROCMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php'
			);
		}
	}
}
