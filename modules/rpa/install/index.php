<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("rpa")) return;

class rpa extends CModule
{
	public $MODULE_ID = "rpa";
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
			$this->MODULE_VERSION = RPA_VERSION;
			$this->MODULE_VERSION_DATE = RPA_VERSION;
		}

		$this->MODULE_NAME = GetMessage("RPA_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("RPA_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("RPA_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("RPA_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = [])
	{
		global $DB, $APPLICATION;
		$errors = false;

		if (!$DB->Query("SELECT 'x' FROM b_rpa_type", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/install.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		static::installUserFields();

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('main', 'onGetUserFieldTypeFactory', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onGetTypeDataClassList', 100);
		$eventManager->registerEventHandler('pull', 'OnGetDependentModule', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onGetDependentModule', 800);
		$eventManager->registerEventHandler('disk', 'onBuildAdditionalConnectorList', 'rpa', '\Bitrix\Rpa\Driver', 'onDiskBuildConnectorList');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onRestServiceBuildDescription');
		RegisterModuleDependences("main", "OnAfterRegisterModule", "main", 'rpa', "installUserFields", 200, "/modules/rpa/install/index.php");

		RegisterModule($this->MODULE_ID);

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/activities",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/activities",
			true, true
		);

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
			$APPLICATION->IncludeAdminFile(GetMessage("RPA_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(["savedata" => $_REQUEST["savedata"] ?? null]);
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("RPA_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
		}

		return true;
	}

	function UnInstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$errors = false;

		if (!isset($params['savedata']) || $params['savedata'] !== "Y")
		{
			if(\Bitrix\Main\Loader::includeModule($this->MODULE_ID))
			{
				$result = \Bitrix\Rpa\Driver::getInstance()->deleteAllData();
				if(!$result->isSuccess())
				{
					$errors = $result->getErrorMessages();
				}
				else
				{
					$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/uninstall.sql");
				}
			}
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('main', 'onGetUserFieldTypeFactory', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onGetTypeDataClassList', 100);
		$eventManager->unRegisterEventHandler('pull', 'OnGetDependentModule', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onGetDependentModule');
		$eventManager->unRegisterEventHandler('disk', 'onBuildAdditionalConnectorList', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onDiskBuildConnectorList');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', $this->MODULE_ID, '\Bitrix\Rpa\Driver', 'onRestServiceBuildDescription');
		UnRegisterModuleDependences("main", "OnAfterRegisterModule", "main", $this->MODULE_ID, "installUserFields", "/modules/rpa/install/index.php");

		UnRegisterModule($this->MODULE_ID);
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	public static function installUserFields($moduleId = null): ?array
	{
		$errors = [];

		if(!$moduleId || $moduleId === 'disk')
		{
			$installCommentUserFieldResult = static::installCommentDiskUserField();
			if (!$installCommentUserFieldResult->isSuccess())
			{
				$errors = array_merge($errors, $installCommentUserFieldResult->getErrorMessages());
			}
		}

		return (empty($errors) ? null : $errors);
	}

	public static function installCommentDiskUserField(): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		$rsUserType = CUserTypeEntity::GetList([], [
				'ENTITY_ID' => 'RPA_COMMENT',
				'FIELD_NAME' => 'UF_RPA_COMMENT_FILES',
			]
		);

		if (!$rsUserType->fetch())
		{
			$CAllUserTypeEntity = new CUserTypeEntity();
			$userFieldId = $CAllUserTypeEntity->Add([
				'ENTITY_ID' => 'RPA_COMMENT',
				'FIELD_NAME' => 'UF_RPA_COMMENT_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'XML_ID' => 'RPA_COMMENT_FILES',
				'MULTIPLE' => 'Y',
				'MANDATORY' => null,
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => null,
				'EDIT_IN_LIST' => null,
				'IS_SEARCHABLE' => null,
				'SETTINGS' => [
					'IBLOCK_TYPE_ID' => '0',
					'IBLOCK_ID' => '',
					'UF_TO_SAVE_ALLOW_EDIT' => ''
				],
				'EDIT_FORM_LABEL' => [
					'en' => 'Load files',
					'ru' => 'Load files',
					'de' => 'Load files'
				]
			]);

			if(!$userFieldId)
			{
				global $APPLICATION;
				if($strEx = $APPLICATION->GetException())
				{
					$result->addError(new \Bitrix\Main\Error($strEx->GetString()));
				}
			}
		}

		return $result;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}
}
