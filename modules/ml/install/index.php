<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Class ml extends CModule
{
	var $MODULE_ID = "ml";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = Loc::getMessage("ML_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("ML_INSTALL_DESCRIPTION");
	}


	function InstallDB($install_wizard = true)
	{
		global $DB, $DBType, $APPLICATION;

		$errors = null;
		if (!$DB->Query("SELECT 'x' FROM b_ml_model", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ml/install/db/".$DBType."/install.sql");

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("ml");

		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		global $DB, $DBType, $APPLICATION;

		$errors = null;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ml/install/db/".$DBType."/uninstall.sql");

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
		}

		UnRegisterModule("ml");

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $step;

		$this->errors = null;

		$this->InstallFiles();
		$this->InstallDB(false);

		$GLOBALS["errors"] = $this->errors;
		$APPLICATION->IncludeAdminFile(Loc::getMessage("ML_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ml/install/step1.php");
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;

		$this->errors = array();

		$step = (int)$step;
		if($step<2)
		{
			$GLOBALS["ml_installer_errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(Loc::getMessage("ML_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ml/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				'savedata' => $_REQUEST['savedata']
			));
			$this->UnInstallFiles();

			$GLOBALS["ml_installer_errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(Loc::getMessage("ML_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ml/install/unstep2.php");
		}
	}
}
?>