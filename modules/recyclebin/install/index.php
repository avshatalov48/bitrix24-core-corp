<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class recyclebin
 */
class recyclebin extends CModule
{
	public $MODULE_ID = 'recyclebin';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	protected const INSTALL_PATH_FROM = '/bitrix/modules/recyclebin/install/';
	protected const INSTALL_PATH_TO = '/bitrix/';

	/**
	 * recyclebin constructor.
	 */
	function __construct()
	{
		$arModuleVersion = array();
		include __DIR__.'/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

		$this->MODULE_NAME = Loc::getMessage('RECYCLEBIN_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('RECYCLEBIN_MODULE_DESC');
	}

	/**
	 * Install
	 */
	function DoInstall()
	{
		$this->InstallDB();
		$this->InstallFiles();

		return true;
	}

	/**
	 * Install module into system
	 */
	function InstallDB()
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();

		if (!$DB->TableExists('b_recyclebin'))
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . self::INSTALL_PATH_FROM .'db/' . $connection->getType() . '/install.sql');

			if ($errors !== false)
			{
				$APPLICATION->ResetException();
				$APPLICATION->ThrowException(implode('', $errors));

				return false;
			}
		}

		$this->InstallUserFields();

		RegisterModule($this->MODULE_ID);

		return true;
	}

	private function installUserFields()
	{
		$oUserTypeEntity = new CUserTypeEntity();
		$aUserFields = array(
			'ENTITY_ID'     => 'RECYCLEBIN_DISK',
			'FIELD_NAME'    => 'UF_RECYCLEBIN_FILES',
			'USER_TYPE_ID'  => 'disk_file',
			'XML_ID'        => 'XML_ID_RECYCLEBIN_FILES',
			'SORT'          => 500,
			'MULTIPLE'      => 'Y',
			'MANDATORY'     => 'N',
			'SHOW_FILTER'   => 'S',
			'SHOW_IN_LIST'  => '',
			'EDIT_IN_LIST'  => '',
			'IS_SEARCHABLE' => 'N'
		);
		$oUserTypeEntity->Add($aUserFields);
	}

	public function installFiles($arParams = []): bool
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/recyclebin/install/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/recyclebin/install/components/bitrix', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/recyclebin/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/recyclebin/install/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images', true, true);

		return true;
	}

	/**
	 * Remove module from system
	 */
	function DoUninstall()
	{
		global $APPLICATION, $step;

		$step = intval($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("RECYCLEBIN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . self::INSTALL_PATH_FROM . "unstep1.php");
		}
		elseif($step == 2)
		{
			$GLOBALS["CACHE_MANAGER"]->CleanAll();
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$APPLICATION->IncludeAdminFile(GetMessage("RECYCLEBIN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . self::INSTALL_PATH_FROM . "unstep2.php");
		}
	}

	/**
	 * Uninstall module from system
	 *
	 * @param array $arParams
	 *
	 * @return bool
	 */
	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION, $USER_FIELD_MANAGER;
		$connection = \Bitrix\Main\Application::getConnection();

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$dbFilePath = '/bitrix/modules/' . $this->MODULE_ID . '/install/db/'.$connection->getType() . '/uninstall.sql';
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . $dbFilePath);

			if (!empty($errors))
			{
				$APPLICATION->ResetException();
				$APPLICATION->ThrowException(implode('', $errors));

				return false;
			}
		}

		$userFields = $USER_FIELD_MANAGER->GetUserFields('RECYCLEBIN_DISK');
		$oUserTypeEntity = new CUserTypeEntity();
		$oUserTypeEntity->Delete($userFields['UF_RECYCLEBIN_FILES']['ID']);

		UnRegisterModule($this->MODULE_ID);

		$GLOBALS["CACHE_MANAGER"]->CleanAll();

		return true;
	}

	public function uninstallFiles(): bool
	{
		$root = $_SERVER['DOCUMENT_ROOT'];
		$pathFrom = $root . self::INSTALL_PATH_FROM;
		$pathTo = $root . self::INSTALL_PATH_TO;

		DeleteDirFiles($pathFrom . 'admin', $pathTo . 'admin');
		DeleteDirFiles($pathFrom . 'components', $pathTo . 'bitrix');
		DeleteDirFiles($pathFrom . 'images', $pathTo . 'images/recyclebin');
		DeleteDirFiles($pathFrom . 'js', $pathTo . 'js/recyclebin');

		return true;
	}
}
