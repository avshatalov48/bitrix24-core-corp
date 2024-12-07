<?php

if(class_exists("transformer"))
{
	return;
}

IncludeModuleLangFile(__FILE__);

class transformer extends CModule
{
	var $MODULE_ID = "transformer";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = GetMessage("TRANSFORMER_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TRANSFORMER_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB(array (
				'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"],
			));
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		if (!$DB->TableExists('b_transformer_command'))
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/transformer/install/db/' . $connection->getType() . '/install.sql');
		}

		if ($params['PUBLIC_URL'] <> '' && mb_strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$errors)
			{
				$errors = [];
			}
			array_unshift($errors, GetMessage('TRANSFORMER_CHECK_PUBLIC_PATH'));
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("transformer");

		COption::SetOptionString("transformer", "portal_url", $params['PUBLIC_URL']);

		$nextDay = time() + 86400;
		CAgent::AddAgent('\\Bitrix\\Transformer\\Entity\\CommandTable::deleteOldAgent();', 'transformer', "N", 86400, "", "Y", ConvertTimeStamp(strtotime(date('Y-m-d 03:30:00', $nextDay)), 'FULL'));

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
			true, true
		);
		return true;
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;

		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMER_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));

			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMER_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformer/install/unstep2.php");
		}

		return true;
	}

	function UnInstallDB($params = array())
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		if (!isset($params['savedata']) || $params['savedata'] !== "Y")
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/transformer/install/db/".$connection->getType()."/uninstall.sql");

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		CAgent::RemoveModuleAgents('transformer');
		UnRegisterModule("transformer");
		return true;
	}
}
