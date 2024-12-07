<?php

use Bitrix\SignMobile\Workspace;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class SignMobile extends CModule
{
	public $MODULE_ID = 'signmobile';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	private $workspaceClass = Workspace::class;

	public function __construct()
	{
		$arModuleVersion = [];
		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && $arModuleVersion['VERSION'] && $arModuleVersion['VERSION_DATE'])
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('SIGN_MOBILE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('SIGN_MOBILE_MODULE_DESCRIPTION');
	}

	public function installDB()
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();

		// db
		$errors = $DB->runSQLBatch(
			$this->getDocumentRoot().'/bitrix/modules/signmobile/install/db/' . $connection->getType() . '/install.sql'
		);
		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

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
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();

		$errors = false;

		// db
		if (isset($arParams['savedata']) && !$arParams['savedata'])
		{
			$errors = $DB->runSQLBatch(
				$this->getDocumentRoot().'/bitrix/modules/signmobile/install/db/' . $connection->getType() . '/uninstall.sql'
			);
		}
		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		$eventManager = EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'mobileapp',
			'onJNComponentWorkspaceGet',
			$this->MODULE_ID,
			$this->workspaceClass,
			'getPath'
		);

		ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	public function installFiles()
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/signmobile/install/mobileapp/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/mobileapp/',
			true,
			true
		);

		return true;
	}

	public function installEvents()
	{
	}

	public function uninstallEvents()
	{
	}

	public function uninstallFiles(): void
	{
		DeleteDirFilesEx('/bitrix/mobileapp/' . $this->MODULE_ID);
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

		if (!ModuleManager::isModuleInstalled('sign'))
		{
			$APPLICATION->throwException(Loc::getMessage('SIGN_MOBILE_MODULE_INSTALL_ERROR_SIGN'));
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
			Loc::getMessage('SIGN_MOBILE_INSTALL_TITLE_MSGVER_1'),
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
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('SIGN_MOBILE_UNINSTALL_TITLE_MSGVER_1'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
			);
		}
		elseif ($step === 2)
		{
			$params = [];
			if (isset($_GET['savedata']))
			{
				$params['savedata'] = $_GET['savedata'] == 'Y';
			}

			$this->uninstallDB($params);
			$this->uninstallFiles();
			$this->uninstallEvents();

			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('SIGN_MOBILE_UNINSTALL_TITLE_MSGVER_1'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php'
			);
		}
	}

	private function getDocumentRoot(): string
	{
		$context =
			\Bitrix\Main\Application::getInstance()
				->getContext()
		;

		return $context ? $context->getServer()
			->getDocumentRoot() : $_SERVER['DOCUMENT_ROOT'];
	}
}
