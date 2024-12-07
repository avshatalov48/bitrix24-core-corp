<?php

use Bitrix\Main\Localization\Loc;


class CallMobile extends \CModule
{
	public $MODULE_ID = 'callmobile';

	private $workspaceClass = \Bitrix\CallMobile\Workspace::class;

	public function __construct()
	{
		$arModuleVersion = [];
		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && $arModuleVersion['VERSION'] && $arModuleVersion['VERSION_DATE'])
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('CALLMOBILE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CALLMOBILE_MODULE_DESCRIPTION');
	}

	public function installDB()
	{
		\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandler(
			'mobileapp',
			'onJNComponentWorkspaceGet',
			$this->MODULE_ID,
			$this->workspaceClass,
			'getPath'
		);

		return true;
	}

	public function uninstallDB()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'mobileapp',
			'onJNComponentWorkspaceGet',
			$this->MODULE_ID,
			$this->workspaceClass,
			'getPath'
		);

		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function installFiles()
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/callmobile/install/mobileapp/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/mobileapp/',
			true,
			true
		);

		return true;
	}

	public function uninstallFiles(): void
	{
		DeleteDirFilesEx('/bitrix/mobileapp/' . $this->MODULE_ID);
	}

	private function installDependencies()
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('mobile'))
		{
			$moduleClass = new \CModule();
			if ($mobile = $moduleClass->CreateModuleObject('mobile'))
			{
				$mobile->InstallFiles();
				$mobile->InstallDB();
			}
		}
	}

	public function doInstall()
	{
		global $USER, $APPLICATION;

		if (!$USER->isAdmin())
		{
			return;
		}

		$this->installDB();
		$this->installFiles();
		$this->installDependencies();

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('CALLMOBILE_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
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
				Loc::getMessage('CALLMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
			);
		}
		elseif($step === 2)
		{
			$this->uninstallDB();
			$this->uninstallFiles();
			$this->uninstallEvents();

			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('CALLMOBILE_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php'
			);
		}
	}
}
