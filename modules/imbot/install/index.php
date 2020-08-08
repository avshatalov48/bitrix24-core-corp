<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (class_exists('imbot'))
{
	return;
}

Loc::loadMessages(__FILE__);

Class imbot extends \CModule
{
	public $MODULE_ID = "imbot";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = IMBOT_VERSION;
			$this->MODULE_VERSION_DATE = IMBOT_VERSION_DATE;
		}

		$this->MODULE_NAME = Loc::getMessage("IMBOT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("IMBOT_MODULE_DESCRIPTION");
	}

	public function DoInstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step1.php");
		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB(Array(
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				));
				$this->InstallFiles();
			}
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step2.php");
		}
		return true;
	}

	public function InstallEvents()
	{
		return true;
	}

	public function CheckModules()
	{
		global $APPLICATION;

		if (!Main\Loader::includeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_PULL');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_IM');
		}
		else
		{
			$imVersion = Main\ModuleManager::getVersion('im');
			if (version_compare("16.1.0", $imVersion) == 1)
			{
				$this->errors[] = Loc::getMessage('IMBOT_CHECK_IM_VERSION');
			}
		}

		if(is_array($this->errors) && !empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			return true;
		}
	}

	public function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		if (!$DB->query("SELECT 'x' FROM b_im_bot_network_session WHERE 1=0", true))
		{
			$errors = $DB->runSqlBatch(sprintf(
				'%s/bitrix/modules/%s/install/db/%s/install.sql',
				$_SERVER['DOCUMENT_ROOT'],
				mb_strtolower($this->MODULE_ID),
				mb_strtolower($DB->type)
			));
			if($errors !== false)
			{
				if (!$this->errors)
				{
					$this->errors = $errors;
				}
			}
		}

		if (strlen($params['PUBLIC_URL']) > 0 && strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_PUBLIC_PATH');
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		Main\ModuleManager::registerModule($this->MODULE_ID);

		\COption::SetOptionString("imbot", "portal_url", $params['PUBLIC_URL']);

		RegisterModuleDependences('im', 'OnAfterUserRead', 'imbot', '\Bitrix\ImBot\Event', 'onUserRead');
		RegisterModuleDependences('im', 'OnAfterMessagesLike', 'imbot', '\Bitrix\ImBot\Event', 'onMessageLike');
		RegisterModuleDependences('im', 'OnStartWriting', 'imbot', '\Bitrix\ImBot\Event', 'onStartWriting');
		RegisterModuleDependences('im', 'OnSessionVote', 'imbot', '\Bitrix\ImBot\Event', 'onSessionVote');

		Main\Loader::includeModule('imbot');

		return true;
	}

	public function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);

		return true;
	}

	public function UnInstallEvents()
	{
		return true;
	}

	public function DoUninstall()
	{
		global $APPLICATION, $step, $DB;
		$step = IntVal($step);

		$botCount = 0;
		if ($step < 2)
		{
			$result = $DB->Query("SELECT COUNT(1) CNT FROM b_user WHERE EXTERNAL_AUTH_ID = 'bot'");
			$row = $result->Fetch();
			$botCount = $row['CNT'];
		}
		if ($botCount > 0)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMBOT_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep0.php");
		}
		else if($step<2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMBOT_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep1.php");
		}
		else if($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMBOT_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep2.php");
		}
	}

	public function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB;

		$this->errors = false;

		Main\Loader::includeModule('imbot');

		UnRegisterModuleDependences('im', 'OnAfterUserRead', 'imbot', '\Bitrix\ImBot\Event', 'onUserRead');
		UnRegisterModuleDependences('im', 'OnAfterMessagesLike', 'imbot', '\Bitrix\ImBot\Event', 'onMessageLike');
		UnRegisterModuleDependences('im', 'OnStartWriting', 'imbot', '\Bitrix\ImBot\Event', 'onStartWriting');
		UnRegisterModuleDependences('im', 'OnSessionVote', 'imbot', '\Bitrix\ImBot\Event', 'onSessionVote');

		$dir = new Bitrix\Main\IO\Directory(Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imbot/lib/bot/');
		$dirList = $dir->getChildren();
		foreach ($dirList as $dirElement)
		{
			$className = $dirElement->getName();
			$className = explode('.', $className);
			if ($className[0] == 'base')
			{
				continue;
			}

			if (class_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0])) && method_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'))
			{
				call_user_func_array(array('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'), Array());
			}
		}

		if (!isset($arParams['savedata']) || $arParams['savedata'] !== true)
		{
			$errors = $DB->runSqlBatch(sprintf(
				'%s/bitrix/modules/%s/install/db/%s/uninstall.sql',
				$_SERVER['DOCUMENT_ROOT'],
				strtolower($this->MODULE_ID),
				strtolower($DB->type)
			));
			if ($errors !== false)
			{
				$APPLICATION->ThrowException(implode("<br>", $errors));

				return false;
			}
		}

		Main\ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	public function UnInstallFiles($arParams = array())
	{
		return true;
	}
}
