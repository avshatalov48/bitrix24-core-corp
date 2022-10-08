<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("documentgenerator")) return;

class documentgenerator extends CModule
{
	public $MODULE_ID = "documentgenerator";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = DOCUMENTGENERATOR_VERSION;
			$this->MODULE_VERSION_DATE = DOCUMENTGENERATOR_VERSION;
		}

		$this->MODULE_NAME = GetMessage("DOCUMENTGENERATOR_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("DOCUMENTGENERATOR_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			if(!class_exists('\DOMDocument', true) || !class_exists('\ZipArchive', true))
			{
				$APPLICATION->ThrowException(GetMessage('DOCUMENTGENERATOR_INSTALL_DEPENDENCIES_ERROR'));
			}
			$APPLICATION->IncludeAdminFile(GetMessage("DOCUMENTGENERATOR_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();

			/**
			 * @see \Bitrix\DocumentGenerator\Driver::installDefaultRoles()
			 */
			CAgent::AddAgent('\Bitrix\DocumentGenerator\Driver::installDefaultRoles();', 'documentgenerator', "N", 150, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+150, "FULL"));

			/**
			 * @see \Bitrix\DocumentGenerator\Service\ActualizeQueue::process()
			 */
			CAgent::AddAgent(
				'\\Bitrix\\DocumentGenerator\\Service\\ActualizeQueue::process(5);',
				'documentgenerator',
				"N",
				300,
			);

			$APPLICATION->IncludeAdminFile(GetMessage("DOCUMENTGENERATOR_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = [])
	{
		global $DB, $APPLICATION;
		$errors = false;

		if (!$DB->Query("SELECT 'x' FROM b_documentgenerator_template", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/install.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModuleDependences('main', 'onNumberGeneratorsClassesCollect', $this->MODULE_ID, 'Bitrix\DocumentGenerator\Integration\Numerator\DocumentNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'documentgenerator', '\Bitrix\DocumentGenerator\Driver', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('pull', 'OnGetDependentModule', 'documentgenerator', '\Bitrix\DocumentGenerator\Driver', 'onGetDependentModule', 800);

		/**
		 * @see \Bitrix\DocumentGenerator\Driver::installDefaultTemplatesForCurrentRegion()
		 */
		CAgent::AddAgent(
			"\\Bitrix\\DocumentGenerator\\Driver::installDefaultTemplatesForCurrentRegion();",
			"documentgenerator",
			"N",
			300,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 300, 'FULL')
		);

		RegisterModule($this->MODULE_ID);

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

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
			true, true
		);

		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("DOCUMENTGENERATOR_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(["savedata" => $_REQUEST["savedata"]]);
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("DOCUMENTGENERATOR_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
		}

		return true;
	}

	function UnInstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$errors = false;

		if (!isset($params['savedata']) || $params['savedata'] !== "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/uninstall.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		UnRegisterModuleDependences('main', 'onNumberGeneratorsClassesCollect', $this->MODULE_ID, 'Bitrix\DocumentGenerator\Integration\Numerator\DocumentNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'documentgenerator', '\Bitrix\DocumentGenerator\Driver', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('pull', 'OnGetDependentModule', 'documentgenerator', '\Bitrix\DocumentGenerator\Driver', 'onGetDependentModule');

		UnRegisterModule($this->MODULE_ID);
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}
}