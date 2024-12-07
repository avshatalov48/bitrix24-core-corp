<?php

if (class_exists('call'))
{
	return;
}

use Bitrix\Main\Localization\Loc;

class call extends \CModule
{
	public $MODULE_ID = 'call';

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('CALL_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CALL_MODULE_DESCRIPTION');
	}

	public function doInstall()
	{
		global $APPLICATION;
		$this->installFiles();
		$this->installDB();

		$APPLICATION->includeAdminFile(
			Loc::getMessage('CALL_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/step1.php'
		);
	}

	public function installDB()
	{
		/*
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();

		$errors = [];
		if (!$connection->isTableExists('b_im_call'))
		{
			$APPLICATION->resetException();
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/call/install/db/' . $connection->getType() . '/install.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}
		*/

		\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

		return true;
	}

	public function installFiles()
	{
		\CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/call/install/js',
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/js',
			true,
			true
		);
		\CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/call/install/components',
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/components',
			true,
			true
		);

		return true;
	}

	public function doUninstall()
	{
		global $APPLICATION;

		$step = (int)($_REQUEST['step'] ?? 1);
		$saveData = ($_REQUEST['savedata'] ?? 'N') == 'Y';
		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				Loc::getMessage('CALL_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/unstep1.php'
			);
		}
		elseif ($step == 2)
		{
			$this->unInstallDB($saveData);
			$this->unInstallFiles();

			$APPLICATION->includeAdminFile(
				Loc::getMessage('CALL_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/unstep2.php'
			);
		}
	}

	public function unInstallDB(bool $saveData = true)
	{
		global $APPLICATION, $DB;

		$errors = [];
		if (!$saveData)
		{
			$APPLICATION->resetException();
			$connection = \Bitrix\Main\Application::getConnection();
			//$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/call/install/db/' . $connection->getType() . '/uninstall.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}
}
