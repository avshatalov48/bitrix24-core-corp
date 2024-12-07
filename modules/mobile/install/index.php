<?php

use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class mobile extends CModule
{
	var $MODULE_ID = "mobile";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__ . '/version.php');

		if (isset($arModuleVersion["VERSION"]))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage('APP_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('APP_MODULE_DESCRIPTION');
	}

	private function getSubmodules(): ?array
	{
		return [
			'im',
			'crm',
			'tasks',
			'calendar',
			'imconnector',
			'catalog',
			'bizproc',
			'lists',
			'sign',
			'intranet',
			'stafftrack',
			'disk',
		];
	}

	public function installSubmodules()
	{
		$submodules = $this->getSubmodules();
		foreach ($submodules as $submoduleId)
		{
			$name = "{$submoduleId}mobile";
			if (!IsModuleInstalled($name) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/$name/install/index.php"))
			{
				include_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/$name/install/index.php");
				$instance = new $name();
				if (method_exists($instance, "installFiles"))
				{
					call_user_func([$instance, "installFiles"]);
				}

				if (method_exists($instance, "installDB"))
				{
					call_user_func([$instance, "installDB"]);
				}
			}
		}

	}

	function InstallDB()
	{
		RegisterModule("mobile");
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('pull', 'ShouldMessageBeSent', 'mobile', 'CMobileEvent', 'shouldSendNotification');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'mobile', '\Bitrix\Mobile\Rest', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('mobile', 'onOneTimeHashRemoved', 'mobile', '\Bitrix\Mobile\Deeplink', 'onOneTimeHashRemoved');
		$eventManager->registerEventHandler('pull', 'OnGetDependentModule', 'mobile', 'CMobileEvent', 'PullOnGetDependentModule');
		$eventManager->registerEventHandler('pull', 'onPushTokenUniqueHashGet', 'mobile', '\Bitrix\Mobile\Push\EventHandler', 'onPushTokenUniqueHashGet');
		$eventManager->registerEventHandler('main', 'OnApplicationsBuildList', 'mobile', 'MobileApplication', 'OnApplicationsBuildList', 100, "modules/mobile/classes/general/mobile_event.php");
		$eventManager->registerEventHandler('mobileapp', 'onJNComponentWorkspaceGet', 'mobile', 'CMobileEvent', 'getJNWorkspace');
		$eventManager->registerEventHandler('mobile', 'onMobileMenuStructureBuilt', 'mobile', 'CMobileEvent', 'onMobileMenuBuilt');
		$eventManager->registerEventHandler('main', 'onKernelCheckInstallFilesMappingGet', 'mobile', 'CMobileEvent', 'getKernelCheckPath');
		$eventManager->registerEventHandler('mobileapp', 'onBeforeComponentContentGet', 'mobile', 'CMobileEvent', 'onBeforeComponentContentGet');

		return true;
	}

	function UnInstallDB($arParams = [])
	{
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "mobile", "CMobileEvent", "PullOnGetDependentModule");
		UnRegisterModuleDependences("main", "OnApplicationsBuildList", "main", 'MobileApplication', "OnApplicationsBuildList", 100, "modules/mobile/classes/general/mobile_event.php");

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'mobile', '\Bitrix\Mobile\Rest', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('mobileapp', 'onJNComponentWorkspaceGet', 'mobile', 'CMobileEvent', 'getJNWorkspace');
		$eventManager->unRegisterEventHandler('main', 'onKernelCheckInstallFilesMappingGet', 'mobile', 'CMobileEvent', 'getKernelCheckPath');
		$eventManager->unRegisterEventHandler('mobileapp', 'onBeforeComponentContentGet', 'mobile', 'CMobileEvent', 'onBeforeComponentContentGet');
		$eventManager->unRegisterEventHandler('pull', 'onPushTokenUniqueHashGet', 'mobile', '\Bitrix\Mobile\Push\EventHandler', 'onPushTokenUniqueHashGet');

		UnRegisterModule("mobile");

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/public/mobile/", $_SERVER["DOCUMENT_ROOT"] . "/mobile/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/templates/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/js/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/", true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mobile/install/tools/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mobile/install/services/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/services/', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mobile/install/mobileapp/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/mobileapp/', true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/images/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/components/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);

		COption::SetOptionString("socialnetwork", "urlToPost", "/mobile/log/index.php");

		$default_site_id = CSite::GetDefSite();
		if ($default_site_id)
		{
			$arAppTempalate = [
				"SORT" => 1,
				"CONDITION" => "CSite::InDir('/mobile/')",
				"TEMPLATE" => "mobile_app",
			];

			$arFields = ["TEMPLATE" => []];
			$dbTemplates = CSite::GetTemplateList($default_site_id);
			$mobileAppFound = false;
			while ($template = $dbTemplates->Fetch())
			{
				if ($template["TEMPLATE"] === "mobile_app")
				{
					$mobileAppFound = true;
					$template = $arAppTempalate;
				}

				$arFields["TEMPLATE"][] = [
					"TEMPLATE" => $template['TEMPLATE'],
					"SORT" => $template['SORT'],
					"CONDITION" => $template['CONDITION'],
				];
			}

			if (!$mobileAppFound)
			{
				$arFields["TEMPLATE"][] = $arAppTempalate;
			}

			$obSite = new CSite;
			$arFields["LID"] = $default_site_id;
			$obSite->Update($default_site_id, $arFields);
		}

		if (File::isFileExists(Application::getDocumentRoot() . "/mobile/webdav/index.php"))
		{
			CUrlRewriter::ReindexFile("/mobile/webdav/index.php");
		}
		if (File::isFileExists(Application::getDocumentRoot() . "/mobile/disk/index.php"))
		{
			CUrlRewriter::ReindexFile("/mobile/disk/index.php");
		}

		$siteId = \CSite::GetDefSite();
		if ($siteId)
		{
			\Bitrix\Main\UrlRewriter::add($siteId, [
				"CONDITION" => "#^\/?\/mobile/mobile_component\/(.*)\/.*#",
				"RULE" => "componentName=$1",
				"PATH" => "/bitrix/services/mobile/jscomponent.php",
			]);

			\Bitrix\Main\UrlRewriter::add($siteId, [
				"CONDITION" => "#^\/?\/mobile/jn\/(.*)\/.*#",
				"RULE" => "componentName=$1",
				"PATH" => "/bitrix/services/mobile/jscomponent.php",
			]);
			\Bitrix\Main\UrlRewriter::add($siteId, [
				"CONDITION" => "#^\/?\/mobile/jn/(.*)\/(.*)\/.*#",
				"RULE" => "componentName=$2&namespace=$1",
				"PATH" => "/bitrix/services/mobile/jscomponent.php",
			]);
			\Bitrix\Main\UrlRewriter::add($siteId, [
				"CONDITION" => "#^\/?\/mobile/web_mobile_component\/(.*)\/.*#",
				"RULE" => "componentName=$1",
				"PATH" => "/bitrix/services/mobile/webcomponent.php",
			]);
			\Bitrix\Main\UrlRewriter::add($siteId, [
				"CONDITION" => "#^/mobile/disk/(?<hash>[0-9]+)/download#",
				"RULE" => "download=1&objectId=\$1",
				"ID" => "bitrix:mobile.disk.file.detail",
				"PATH" => "/mobile/disk/index.php",
			]);
		}

		return true;
	}

	public function uninstallFiles(): void
	{

	}

	function InstallPull()
	{
		if (!IsModuleInstalled('pull') && file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/pull/install/index.php"))
		{
			include_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/pull/install/index.php");
			$pull = new pull();
			$pull->InstallFiles();
			$pull->InstallDB();
		}
	}

	function InstallMobileApp()
	{
		if (!IsModuleInstalled('mobileapp') && file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/mobileapp/install/index.php"))
		{
			include_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/mobileapp/install/index.php");
			$pull = new mobileapp();
			$pull->InstallFiles();
			$pull->InstallDB();
		}
	}

	function DoInstall()
	{
		global $USER, $DB, $APPLICATION;
		if (!$USER->IsAdmin())
		{
			return;
		}

		$this->InstallDB();
		$this->InstallFiles();
		$this->InstallMobileApp();
		$this->InstallPull();
		$this->installSubmodules();

		$APPLICATION->IncludeAdminFile(Loc::getMessage("APP_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/step.php");
	}

	function DoUninstall()
	{
		global $USER, $APPLICATION, $step;
		if ($USER->IsAdmin())
		{
			$step = (int)$step;
			if ($step < 2)
			{

				$APPLICATION->IncludeAdminFile(Loc::getMessage("APP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/unstep1.php");
			}
			elseif ($step == 2)
			{
				$this->UnInstallDB();
				$this->uninstallFiles();
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(Loc::getMessage("APP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/mobile/install/unstep.php");
			}
		}
	}

	function InstallEvents()
	{
	}

	function UnInstallEvents()
	{
	}
}
