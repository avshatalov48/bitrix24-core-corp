<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class CalendarMobile extends CModule
{
	public $MODULE_ID = 'calendarmobile';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	private $workspaceClass = \Bitrix\CalendarMobile\Workspace::class;

	public function __construct()
	{
		$arModuleVersion = [];
		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && $arModuleVersion['VERSION'] && $arModuleVersion['VERSION_DATE'])
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('CALENDARMOBILE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CALENDARMOBILE_MODULE_DESCRIPTION');
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

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function installFiles()
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/calendarmobile/install/components/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/components',
			true,
			true
		);

		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/calendarmobile/install/mobileapp/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/mobileapp/',
			true,
			true
		);

		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/calendarmobile/install/js/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/',
			true,
			true
		);

		return true;
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

		if (!ModuleManager::isModuleInstalled('calendar'))
		{
			$APPLICATION->throwException(Loc::getMessage('CALENDARMOBILE_MODULE_INSTALL_ERROR_CALENDAR'));
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

	protected function showInstallStep(int $step): void
	{
		global $APPLICATION;

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('CALENDARMOBILE_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step' . $step . '.php'
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
				Loc::getMessage('CALENDARMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
			);
		}
		elseif($step === 2)
		{
			$this->uninstallDB();
			$this->uninstallFiles();
			$this->uninstallEvents();

			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('CALENDARMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php'
			);
		}
	}
}
