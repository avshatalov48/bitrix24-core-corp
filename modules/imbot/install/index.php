<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("imbot")) return;

Class imbot extends CModule
{
	var $MODULE_ID = "imbot";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function imbot()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

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

		$this->MODULE_NAME = GetMessage("IMBOT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IMBOT_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(GetMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step1.php");
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
			$APPLICATION->IncludeAdminFile(GetMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step2.php");
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function CheckModules()
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = GetMessage('IMBOT_CHECK_PULL');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = GetMessage('IMBOT_CHECK_IM');
		}
		else
		{
			$imVersion = \Bitrix\Main\ModuleManager::getVersion('im');
			if (version_compare("16.1.0", $imVersion) == 1)
			{
				$this->errors[] = GetMessage('IMBOT_CHECK_IM_VERSION');
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

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if (strlen($params['PUBLIC_URL']) > 0 && strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = GetMessage('IMBOT_CHECK_PUBLIC_PATH');
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("imbot");

		COption::SetOptionString("imbot", "portal_url", $params['PUBLIC_URL']);

		RegisterModuleDependences('im', 'OnAfterUserRead', 'imbot', '\Bitrix\ImBot\Event', 'onUserRead');
		RegisterModuleDependences('im', 'OnAfterMessagesLike', 'imbot', '\Bitrix\ImBot\Event', 'onMessageLike');
		RegisterModuleDependences('im', 'OnStartWriting', 'imbot', '\Bitrix\ImBot\Event', 'onStartWriting');
		RegisterModuleDependences('im', 'OnSessionVote', 'imbot', '\Bitrix\ImBot\Event', 'onSessionVote');

		CModule::IncludeModule('imbot');

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);

		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step, $DB;
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
			$APPLICATION->IncludeAdminFile(GetMessage("IMBOT_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imbot/install/unstep0.php");
		}
		else if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("IMBOT_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imbot/install/unstep1.php");
		}
		else if($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("IMBOT_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imbot/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		$this->errors = false;

		CModule::IncludeModule('imbot');

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
				continue;

			if (class_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0])) && method_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'))
			{
				call_user_func_array(array('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'), Array());
			}
		}

		UnRegisterModule("imbot");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		return true;
	}
}
?>