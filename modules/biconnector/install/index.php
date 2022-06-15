<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

if (class_exists('biconnector'))
{
	return;
}

class BIConnector extends \CModule
{
	public $MODULE_ID = 'biconnector';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	private $errors;

	public function __construct()
	{
		$arModuleVersion = [];

		include __DIR__ . '/version.php';

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('BICONNECTOR_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('BICONNECTOR_INSTALL_DESCRIPTION');
	}

	function installFiles($params = array())
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/public/bitrix/tools',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools', true, true
		);
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/components',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true
		);

		if ($params['public_dir'])
		{
			$siteList = CSite::GetList();
			while ($site = $siteList->Fetch())
			{
				CopyDirFiles(
					$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/public/biconnector',
					$site['ABS_DOC_ROOT'] . $site['DIR'] . '/' . $params["public_dir"], $params['public_rewrite']
				);
			}
		}

		return true;
	}

	function installDB()
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if (!$DB->Query('SELECT 1 FROM b_biconnector_dictionary_cache WHERE 1=0', true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/db/mysql/install.sql');
		}

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}
		else
		{
			RegisterModuleDependences('perfmon', 'OnGetTableSchema', 'biconnector', 'biconnector', 'OnGetTableSchema');
			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->registerEventHandler('report', 'onAnalyticPageBatchCollect', 'biconnector', '\Bitrix\BIConnector\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');
			$eventManager->registerEventHandler('report', 'onAnalyticPageCollect', 'biconnector', '\Bitrix\BIConnector\Integration\Report\EventHandler', 'onAnalyticPageCollect');
			$eventManager->registerEventHandler('rest', 'onRestApplicationConfigurationGetManifest', 'biconnector', '\Bitrix\BiConnector\Configuration\Manifest', 'list' );
			$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationEntity', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'getEntityList');
			$eventManager->registerEventHandler('rest', 'onRestApplicationConfigurationImport', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onImport');
			$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationExport', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onExport');
			$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationFinish', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onFinish');
			$eventManager->registerEventHandler('rest', 'onBeforeApplicationUninstall', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onBeforeRestApplicationDelete');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Lead', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadStatusHistory', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Deal', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealStageHistory', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Company', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Contact', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Voximplant\Call', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Socialnetwork\Group', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\LimitManager', 'onBitrix24LicenseChange');

			$this->InstallTasks();

			\CAgent::AddAgent('\\Bitrix\\BIConnector\\LogTable::cleanUpAgent();', 'biconnector', 'N', 86400);

			ModuleManager::registerModule($this->MODULE_ID);

			return true;
		}
	}

	function installEvents()
	{
		return true;
	}

	function uninstallDB($arParams = [])
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		if (!array_key_exists('save_tables', $arParams) || $arParams['save_tables'] != 'Y')
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/db/mysql/uninstall.sql');
		}

		UnRegisterModuleDependences('perfmon', 'OnGetTableSchema', 'biconnector', 'biconnector', 'OnGetTableSchema');
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageBatchCollect', 'biconnector', '\Bitrix\BIConnector\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageCollect', 'biconnector', '\Bitrix\BIConnector\Integration\Report\EventHandler', 'onAnalyticPageCollect');
		$eventManager->unRegisterEventHandler('rest', 'onRestApplicationConfigurationGetManifest', 'biconnector', '\Bitrix\BiConnector\Configuration\Manifest', 'list');
		$eventManager->unRegisterEventHandler('rest', 'OnRestApplicationConfigurationEntity', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'getEntityList');
		$eventManager->unRegisterEventHandler('rest', 'onRestApplicationConfigurationImport', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onImport');
		$eventManager->unRegisterEventHandler('rest', 'OnRestApplicationConfigurationExport', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onExport');
		$eventManager->unRegisterEventHandler('rest', 'OnRestApplicationConfigurationFinish', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onFinish');
		$eventManager->unRegisterEventHandler('rest', 'onBeforeApplicationUninstall', 'biconnector', '\Bitrix\BiConnector\Configuration\Action', 'onBeforeRestApplicationDelete');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Lead', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadStatusHistory', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Deal', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealStageHistory', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Company', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Contact', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Voximplant\Call', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Socialnetwork\Group', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\LimitManager', 'onBitrix24LicenseChange');

		\CAgent::RemoveModuleAgents('biconnector');

		$this->UnInstallTasks();

		UnRegisterModule($this->MODULE_ID);

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}

		return true;
	}

	function uninstallEvents()
	{
		return true;
	}

	function uninstallFiles()
	{
		DeleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . 'bitrix/modules/' . $this->MODULE_ID . '/install/public/bitrix/tools/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools'
		);

		return true;
	}

	function doInstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if ($USER->isAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php');
			}
			elseif ($step == 2)
			{
				if ($this->installDB())
				{
					$this->installFiles(array(
						'public_dir' => $_REQUEST['install_public'] == 'Y' ? 'biconnector' : '',
						'public_rewrite' => $_REQUEST['public_rewrite'] == 'Y',
					));
				}
				$GLOBALS['errors'] = $this->errors;
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php');
			}
		}
	}

	function doUninstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if ($USER->isAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php');
			}
			elseif ($step == 2)
			{
				$this->unInstallDB([
					'save_tables' => $_REQUEST['save_tables'],
				]);
				$this->unInstallFiles();
				$GLOBALS['errors'] = $this->errors;
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php');
			}
		}
	}

	function getModuleTasks()
	{
		return [
			'biconnector_deny' => [
				'LETTER' => 'D',
				'BINDING' => 'module',
				'OPERATIONS' => []
			],
			'biconnector_read' => [ //Can view all dashboards
				'LETTER' => 'R',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'biconnector_dashboard_view',
				]
			],
			'biconnector_add' => [ //Can view all keys and fully manage all dashboards
				'LETTER' => 'U',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'biconnector_key_view',
					'biconnector_dashboard_view',
					'biconnector_dashboard_manage',
				]
			],
			'biconnector_full' => [ //Can operate on all module entities
				'LETTER' => 'W',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'biconnector_key_view',
					'biconnector_key_manage',
					'biconnector_dashboard_view',
					'biconnector_dashboard_manage',
				]
			],
		];
	}

	public static function OnGetTableSchema()
	{
		return [
			'biconnector' => [
				'b_biconnector_dictionary_cache' => [
					'DICTIONARY_ID' => [
						'b_biconnector_dictionary_data' => 'DICTIONARY_ID',
					],
				],
				'b_biconnector_key' => [
					'ID' => [
						'b_biconnector_key_user' => 'KEY_ID',
						'b_biconnector_log' => 'KEY_ID',
					],
				],
				'b_biconnector_dashboard' => [
					'ID' => [
						'b_biconnector_dashboard_user' => 'DASHBOARD_ID',
					],
				],
			],
			'main' => [
				'b_user' => [
					'ID' => [
						'b_biconnector_key' => 'CREATED_BY',
						'b_biconnector_key_user' => 'CREATED_BY',
						'^b_biconnector_key_user' => 'USER_ID',
						'b_biconnector_dashboard' => 'CREATED_BY',
						'b_biconnector_dashboard_user' => 'CREATED_BY',
						'^b_biconnector_dashboard_user' => 'USER_ID',
					],
				],
			],
			'rest' => [
				'b_rest_app' => [
					'ID' => [
						'b_biconnector_key' => 'APP_ID',
					],
				],
			],
		];
	}
}
