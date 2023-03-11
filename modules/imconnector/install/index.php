<?php

use Bitrix\ImConnector\Rest\Status;
use Bitrix\ImConnector\InfoConnectors;
use Bitrix\ImConnector\Rest\CustomConnectors;

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (!class_exists('imconnector'))
{
	class imconnector extends \CModule
	{
		protected $errors = [];

		/**
		 * ImConnector constructor.
		 */
		public function __construct()
		{
			$arModuleVersion = [];
			include(__DIR__ . '/version.php');

			$this->MODULE_ID = 'imconnector';

			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

			$this->MODULE_NAME = Loc::getMessage('IMCONNECTOR_MODULE_NAME');
			$this->MODULE_DESCRIPTION = Loc::getMessage('IMCONNECTOR_MODULE_DESC');
		}

		/**
		 * @param array $arParams
		 * @return bool
		 */
		public function InstallDB($arParams = [])
		{
			global $DB, $APPLICATION;
			$this->errors = false;

			if (
				!$this->errors
				&& !$DB->Query('SELECT \'x\' FROM b_imconnectors_status WHERE 1=0', true)
			)
			{
				$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imconnector/install/db/mysql/install.sql');
			}

			if ($this->errors !== false)
			{
				$APPLICATION->ThrowException(implode('<br>', $this->errors));
				return false;
			}

			$rsUserType = \CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID'  => 'USER',
					'FIELD_NAME' => 'UF_CONNECTOR_MD5',
				]
			);

			if (
				is_object($rsUserType)
				&& !$rsUserType->fetch()
			)
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
				'vkgroup',
				'ok',
				'olx',
				'facebook',
				'facebookcomments',
				'fbinstagramdirect',
				'network',
				'notifications',
				'whatsappbyedna',
			];
			Option::set($this->MODULE_ID, 'list_connector', implode(',', $listConnector));

			ModuleManager::registerModule($this->MODULE_ID);

			$eventManager = EventManager::getInstance();
			$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector',
				CustomConnectors::class, 'OnRestServiceBuildDescription');
			$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector',
				Status::class, 'OnRestServiceBuildDescription');
			$eventManager->registerEventHandler('imconnector', 'OnUpdateStatusConnector', 'imconnector',
				InfoConnectors::class, 'onUpdateStatusConnector');
			$eventManager->registerEventHandler('imconnector', 'OnDeleteStatusConnector', 'imconnector',
				InfoConnectors::class, 'onChangeStatusConnector');
			$eventManager->registerEventHandler('imopenlines', 'OnImopenlineCreate', 'imconnector',
				InfoConnectors::class, 'onImopenlineCreate');
			$eventManager->registerEventHandler('imopenlines', 'OnImopenlineDelete', 'imconnector',
				InfoConnectors::class, 'onImopenlineDelete');
			$eventManager->registerEventHandler('rest', 'OnRestAppDelete', 'imconnector',
				CustomConnectors::class, 'OnRestAppDelete');

			CAgent::AddAgent('\Bitrix\ImConnector\InfoConnectors::infoConnectorsUpdateAgent();', 'imconnector', 'Y', 21600, '', 'Y', ConvertTimeStamp((time() + 21600), 'FULL'));
			CAgent::AddAgent('\Bitrix\ImConnector\Status::cleanupDuplicates();', 'imconnector', 'N', 60, '', 'Y', ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, 'FULL'));
			CAgent::AddAgent('\Bitrix\ImConnector\Connectors\Olx::initializeReceiveMessages();', 'imconnector', 'N', 60, '', 'Y', ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, 'FULL'));
			/** @see \Bitrix\ImConnector\Agent::notifyUndelivered */
			CAgent::AddAgent('\Bitrix\ImConnector\Agent::notifyUndelivered();', 'imconnector', 'N', 60);

			return true;
		}

		public function UnInstallDB($arParams = [])
		{
			global $DB, $APPLICATION;
			$this->errors = false;

			if (
				array_key_exists('savedata', $arParams)
				&& $arParams['savedata'] !== 'Y'
			)
			{
				$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imconnector/install/db/mysql/uninstall.sql');

				$rsUserType = \CUserTypeEntity::GetList(
					[],
					[
						'ENTITY_ID'  => 'USER',
						'FIELD_NAME' => 'UF_CONNECTOR_MD5',
					]
				);

				if (
					is_object($rsUserType)
					&& $userType = $rsUserType->fetch()
				)
				{
					$CAllUserTypeEntity = new \CUserTypeEntity();

					$CAllUserTypeEntity->Delete($userType['ID']);
				}

				Option::delete($this->MODULE_ID);
			}

			if ($this->errors !== false)
			{
				$APPLICATION->ThrowException(implode('<br>', $this->errors));
				return false;
			}

			$eventManager = EventManager::getInstance();
			$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector',
				CustomConnectors::class, 'onRestServiceBuildDescription');
			$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imconnector',
				Status::class, 'onRestServiceBuildDescription');
			$eventManager->unRegisterEventHandler('imconnector', 'OnUpdateStatusConnector', 'imconnector',
				InfoConnectors::class, 'onUpdateStatusConnector');
			$eventManager->unRegisterEventHandler('imconnector', 'OnDeleteStatusConnector', 'imconnector',
				InfoConnectors::class, 'onChangeStatusConnector');
			$eventManager->unRegisterEventHandler('imopenlines', 'OnImopenlineCreate', 'imconnector',
				InfoConnectors::class, 'onImopenlineCreate');
			$eventManager->unRegisterEventHandler('imopenlines', 'OnImopenlineDelete', 'imconnector',
				InfoConnectors::class, 'onImopenlineDelete');
			$eventManager->unRegisterEventHandler('rest', 'OnRestAppDelete', 'imconnector',
				CustomConnectors::class, 'OnRestAppDelete');

			CAgent::RemoveModuleAgents($this->MODULE_ID);
			ModuleManager::unRegisterModule($this->MODULE_ID);

			return true;
		}


		/**
		 * @return bool
		 */
		public function InstallFiles()
		{
			\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]. '/bitrix/modules/imconnector/install/pub', $_SERVER['DOCUMENT_ROOT']. '/pub', true, true);
			\CopyDirFiles($_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/imconnector/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
			\CopyDirFiles($_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/imconnector/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);

			return true;
		}

		/**
		 * @return bool
		 */
		public function UnInstallFiles()
		{
			Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/pub/imconnector/');
			\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/components/bitrix', $_SERVER["DOCUMENT_ROOT"].'/bitrix/components/bitrix');
			\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/js', $_SERVER["DOCUMENT_ROOT"].'/bitrix/js');

			return true;
		}

		public function DoInstall()
		{
			global $APPLICATION;

			$application = Application::getInstance();

			if ($application instanceof Application)
			{
				$context = $application->getContext();

				$request = $context->getRequest();

				if ((int)$request['step']<2)
				{
					$APPLICATION->IncludeAdminFile(Loc::getMessage('IMCONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/step1.php');
				}
				elseif ((int)$request['step'] === 2)
				{
					$this->InstallDB([
						'public_url' => $request['public_url'],
					]);

					$this->InstallEvents();
					$this->InstallFiles();

					$APPLICATION->IncludeAdminFile(Loc::getMessage('IMCONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/step2.php');
				}
			}
		}

		public function DoUninstall()
		{
			global $APPLICATION;

			$application = Application::getInstance();

			if ($application instanceof Application)
			{
				$context = $application->getContext();

				$request = $context->getRequest();

				if ((int)$request['step']<2)
				{
					$APPLICATION->IncludeAdminFile(Loc::getMessage('IMCONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/unstep1.php');
				}
				elseif ((int)$request['step']===2)
				{
					$this->UnInstallFiles();
					$this->UnInstallEvents();

					$this->UnInstallDB([
						'savedata' => $request['savedata'],
					]);

					$APPLICATION->IncludeAdminFile(Loc::getMessage('IMCONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imconnector/install/unstep2.php');
				}
			}
		}
	}
}
