<?
use \Bitrix\Main\Localization\Loc;

use \Bitrix\Main\Application,
	\Bitrix\Main\EventManager,
	\Bitrix\Main\IO\Directory,
	\Bitrix\Main\ModuleManager,
	\Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if(class_exists("ImConnector")) return;

Class ImConnector extends CModule
{
	private $errors = array();

	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__."/version.php");

		$this->MODULE_ID = 'imconnector';

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = Loc::getMessage("IMCONNECTOR_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("IMCONNECTOR_MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if($DB->type !== 'MYSQL')
		{
			$this->errors = array(
				Loc::getMessage('IMCONNECTOR_DB_NOT_SUPPORTED'),
			);
		}

		if (!$this->errors && !$DB->Query("SELECT 'x' FROM b_imconnectors_status WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/db/".$DBType."/install.sql");
		}
		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$rsUserType = \CUserTypeEntity::GetList(
			[],
			[
				'ENTITY_ID'  => 'USER',
				'FIELD_NAME' => 'UF_CONNECTOR_MD5',
			]
		);

		if(is_object($rsUserType) && !$rsUserType->fetch())
		{
			$CAllUserTypeEntity = new \CUserTypeEntity();

			$CAllUserTypeEntity->Add([
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_CONNECTOR_MD5',
				'USER_TYPE_ID' => 'string',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
			]);
		}

		Option::set($this->MODULE_ID, 'uri_client', $arParams['public_url']);

		$listConnector = [
			'livechat',
			'whatsappbytwilio',
			'avito',
			'viber',
			'telegrambot',
			'imessage',
			'wechat',
			'yandex',
			'vkgroup',
			'ok',
			'olx',
			'facebook',
			'facebookcomments',
			'fbinstagramdirect',
			'fbinstagram',
			'network',
		];
		Option::set($this->MODULE_ID, 'list_connector', implode(',', $listConnector));

		ModuleManager::registerModule($this->MODULE_ID);

		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector', '\Bitrix\ImConnector\Rest\CustomConnectors', 'OnRestServiceBuildDescription');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector', '\Bitrix\ImConnector\Rest\Status', 'OnRestServiceBuildDescription');
		$eventManager->registerEventHandler('imconnector', 'OnUpdateStatusConnector', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onUpdateStatusConnector');
		$eventManager->registerEventHandler('imconnector', 'OnDeleteStatusConnector', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onChangeStatusConnector');
		$eventManager->registerEventHandler('imopenlines', 'OnImopenlineCreate', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onImopenlineCreate');
		$eventManager->registerEventHandler('imopenlines', 'OnImopenlineDelete', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onImopenlineDelete');
		$eventManager->registerEventHandler('rest', 'OnRestAppDelete', 'imconnector', '\Bitrix\ImConnector\Rest\CustomConnectors', 'OnRestAppDelete');

		CAgent::AddAgent("\Bitrix\ImConnector\InfoConnectors::infoConnectorsUpdateAgent();", "imconnector", "Y", 21600, "", "Y", ConvertTimeStamp((time() + 21600), 'FULL'));
		CAgent::AddAgent('\Bitrix\ImConnector\Status::cleanupDuplicates();', "imconnector", "N", 60, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, "FULL"));
		CAgent::AddAgent('\Bitrix\ImConnector\Connectors\Olx::initializeReceiveMessages();', 'imconnector', 'N', 60, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, "FULL"));

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/db/".$DBType."/uninstall.sql");

			$rsUserType = \CUserTypeEntity::GetList(
				array(),
				array(
					'ENTITY_ID'  => 'USER',
					'FIELD_NAME' => 'UF_CONNECTOR_MD5',
				)
			);

			if(is_object($rsUserType) && $userType = $rsUserType->fetch())
			{
				$CAllUserTypeEntity = new \CUserTypeEntity();

				$CAllUserTypeEntity->Delete($userType["ID"]);
			}

			Option::delete($this->MODULE_ID);
		}

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector', '\Bitrix\ImConnector\Rest\CustomConnectors', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector', '\Bitrix\ImConnector\Rest\Status', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('imconnector', 'OnUpdateStatusConnector', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onUpdateStatusConnector');
		$eventManager->unRegisterEventHandler('imconnector', 'OnDeleteStatusConnector', 'imconnector', '\Bitrix\ImConnector\InfoConnectors', 'onChangeStatusConnector');
		$eventManager->unRegisterEventHandler('rest', 'OnRestAppDelete', 'imconnector', '\Bitrix\ImConnector\Rest\CustomConnectors', 'OnRestAppDelete');

		CAgent::RemoveModuleAgents('imconnector');
		ModuleManager::unRegisterModule($this->MODULE_ID);

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
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/pub/imconnector", $_SERVER["DOCUMENT_ROOT"]."/pub/imconnector", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/components/bitrix", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);

		return true;
	}

	function UnInstallFiles()
	{
		Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/pub/imconnector/');

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if($request["step"]<2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMCONNECTOR_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/step1.php");
		}
		elseif($request["step"]==2)
		{
			$this->InstallDB(array(
				"public_url" => $request["public_url"],
			));
			$this->InstallEvents();
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMCONNECTOR_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $APPLICATION;

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if($request["step"]<2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMCONNECTOR_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/unstep1.php");
		}
		elseif($request["step"]==2)
		{
			$this->UnInstallFiles();
			$this->UnInstallEvents();

			$this->UnInstallDB(array(
				"savedata" => $request["savedata"],
			));

			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMCONNECTOR_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/unstep2.php");
		}
	}
}
?>