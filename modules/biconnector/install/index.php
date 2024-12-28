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

	public function InstallFiles($params = [])
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/public/bitrix/tools',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools', true, true
		);
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/components',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true
		);
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/images',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/images',
			true, true
		);
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/js',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/js',
			true, true
		);

		if (!isset($params['manual_installing']))
		{
			$params['public_dir'] = 'biconnector';
			$params['public_rewrite'] = true;
		}

		if ($params['public_dir'] !== '')
		{
			$siteList = CSite::GetList();
			while ($site = $siteList->Fetch())
			{
				CopyDirFiles(
					$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/install/public/biconnector',
					$site['ABS_DOC_ROOT'] . $site['DIR'] . '/' . $params['public_dir'], $params['public_rewrite']
				);
			}
		}

		return true;
	}

	public function InstallDB()
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
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\GoogleDataStudio', 'createServiceInstance');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\MicrosoftPowerBI', 'createServiceInstance');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\YandexDataLens', 'createServiceInstance');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\ApacheSuperset', 'createServiceInstance');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\GoogleDataStudio', 'validateDashboardUrl');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\MicrosoftPowerBI', 'validateDashboardUrl');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\YandexDataLens', 'validateDashboardUrl');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Activity', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Company', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\CompanyUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Contact', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ContactUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Deal', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealProductRow', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealStageHistory', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Lead', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadProductRow', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadStatusHistory', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DynamicItems', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Socialnetwork\Group', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Voximplant\Call', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Main\User', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Product', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ProductProperty', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ProductPropertyValue', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\Task', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskUserField', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskStages', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\Flow', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskElapsedTime', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskEfficiency', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\HumanResources\Structure', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\LimitManager', 'onBitrix24LicenseChange');
			$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\SupersetInitializer', 'onBitrix24LicenseChange');
			$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'biconnector', '\Bitrix\BIConnector\DictionaryManager', 'onBeforeUserUpdateHandler');
			$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'biconnector', '\Bitrix\BIConnector\DictionaryManager', 'onAfterUserUpdateHandler');
			$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'biconnector', '\Bitrix\BIConnector\Superset\SystemDashboardManager', 'onAfterUserUpdateHandler');
			$eventManager->registerEventHandler('main', 'OnAfterSetOption_server_name', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\SupersetInitializer', 'refreshSupersetDomainConnection');
			//bizproc
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Bizproc\Task', 'onBIConnectorDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Bizproc\WorkflowState', 'onBIConnectorDataSources');

			$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\Events\Main\User', 'onBeforeUserUpdate');
			$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\Events\Main\User', 'onAfterUserUpdate');

			$eventManager->registerEventHandler('pull', 'onGetDependentModule', $this->MODULE_ID, '\Bitrix\BIConnector\Integration\Pull\PullManager', 'onGetDependentModule');
			$eventManager->registerEventHandler('crm', 'onAfterAutomatedSolutionDelete', 'biconnector', '\Bitrix\BIConnector\Superset\Scope\ScopeService', 'deleteAutomatedSolutionBinding');

			$eventManager->registerEventHandler('main', 'OnBuildGlobalMenu', 'biconnector', '\Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorShop', 'buildCrmMenu');

			$eventManager->registerEventHandler('biconnector', 'onAfterAddDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\SupersetIntegration', 'onAfterAddDataset');
			$eventManager->registerEventHandler('biconnector', 'onAfterDeleteDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\SupersetIntegration', 'onAfterDeleteDataset');
			$eventManager->registerEventHandler('biconnector', 'onAfterDeleteDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\Source\Csv', 'onAfterDeleteDataset');

			$eventManager->registerEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\ExternalSource\Dataset\Base', 'onBIBuilderExternalDataSources');

			// bi builder
			$eventManager->registerEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Tracking\Dataset\SourceExpenses', 'onBIBuilderDataSources');
			$eventManager->registerEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Tracking\Dataset\Source', 'onBIBuilderDataSources');

			$this->InstallTasks();

			\CAgent::AddAgent('\\Bitrix\\BIConnector\\LogTable::cleanUpAgent();', 'biconnector', 'N', 86400);
			\CAgent::AddAgent('\\Bitrix\\BIConnector\\Integration\\Superset\\Agent::sendRestStatistic();', 'biconnector');
			\CAgent::AddAgent('\\Bitrix\\BIConnector\\Integration\\Superset\\Agent::createDefaultRoles();', 'biconnector');

			ModuleManager::registerModule($this->MODULE_ID);

			return true;
		}
	}

	public function InstallEvents()
	{
		return true;
	}

	public function UnInstallDB($arParams = [])
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
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\GoogleDataStudio', 'createServiceInstance');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\MicrosoftPowerBI', 'createServiceInstance');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\YandexDataLens', 'createServiceInstance');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorCreateServiceInstance', 'biconnector', '\Bitrix\BIConnector\Services\ApacheSuperset', 'createServiceInstance');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\GoogleDataStudio', 'validateDashboardUrl');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\MicrosoftPowerBI', 'validateDashboardUrl');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorValidateDashboardUrl', 'biconnector', '\Bitrix\BIConnector\Services\YandexDataLens', 'validateDashboardUrl');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Activity', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Company', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\CompanyUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Contact', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ContactUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Deal', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealProductRow', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealStageHistory', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DealUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Lead', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadProductRow', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadStatusHistory', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\LeadUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\DynamicItems', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Socialnetwork\Group', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Voximplant\Call', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Main\User', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Product', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ProductProperty', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\ProductPropertyValue', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\Task', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskUserField', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskStages', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\Flow', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskElapsedTime', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Tasks\TaskEfficiency', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIConnectorDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\HumanResources\Structure', 'onBIConnectorDataSources');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\LimitManager', 'onBitrix24LicenseChange');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\SupersetInitializer', 'onBitrix24LicenseChange');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserUpdate', 'biconnector', '\Bitrix\BIConnector\DictionaryManager', 'onBeforeUserUpdateHandler');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserUpdate', 'biconnector', '\Bitrix\BIConnector\DictionaryManager', 'onAfterUserUpdateHandler');
		$eventManager->unregisterEventHandler('main', 'OnAfterSetOption_server_name', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\SupersetInitializer', 'refreshSupersetDomainConnection');
		$eventManager->unregisterEventHandler('main', 'OnBeforeUserUpdate', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\Events\Main\User', 'onBeforeUserUpdate');
		$eventManager->unregisterEventHandler('main', 'OnAfterUserUpdate', 'biconnector', '\Bitrix\BIConnector\Integration\Superset\Events\Main\User', 'onAfterUserUpdate');
		$eventManager->unregisterEventHandler('pull', 'onGetDependentModule', $this->MODULE_ID, '\Bitrix\BIConnector\Integration\Pull\PullManager', 'onGetDependentModule');
		$eventManager->unregisterEventHandler('crm', 'onAfterAutomatedSolutionDelete', 'biconnector', '\Bitrix\BIConnector\Superset\Scope\ScopeService', 'deleteAutomatedSolutionBinding');
		$eventManager->unregisterEventHandler('main', 'OnBuildGlobalMenu', 'biconnector', '\Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorShop', 'buildCrmMenu');
		$eventManager->unregisterEventHandler('biconnector', 'onAfterAddDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\SupersetIntegration', 'onAfterAddDataset');
		$eventManager->unregisterEventHandler('biconnector', 'onAfterDeleteDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\SupersetIntegration', 'onAfterDeleteDataset');
		$eventManager->unRegisterEventHandler('biconnector', 'onAfterDeleteDataset', 'biconnector', '\Bitrix\BIConnector\ExternalSource\Source\Csv', 'onAfterDeleteDataset');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\ExternalSource\Dataset\Base', 'onBIBuilderExternalDataSources');

		// bi builder
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Tracking\Dataset\SourceExpenses', 'onBIBuilderDataSources');
		$eventManager->unRegisterEventHandler('biconnector', 'OnBIBuilderDataSources', 'biconnector', '\Bitrix\BIConnector\Integration\Crm\Tracking\Dataset\Source', 'onBIBuilderDataSources');

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

	public function UnInstallEvents()
	{
		return true;
	}

	public function UnInstallFiles()
	{
		DeleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . 'bitrix/modules/' . $this->MODULE_ID . '/install/public/bitrix/tools/',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools'
		);
		DeleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/images',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/images'
		);
		DeleteDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/js',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/js'
		);

		return true;
	}

	public function DoInstall()
	{
		global $APPLICATION, $step, $USER, $errors;
		if ($USER->isAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php');
			}
			elseif ($step == 2)
			{
				if ($this->InstallDB())
				{
					$this->InstallFiles([
						'public_dir' => $_REQUEST['install_public'] === 'Y' ? 'biconnector' : '',
						'public_rewrite' => $_REQUEST['public_rewrite'] === 'Y',
						'manual_installing' => true,
					]);

					$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
					\CBitrixComponent::clearComponentCache("bitrix:menu");
				}
				$errors = $this->errors;
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php');
			}
		}
	}

	public function DoUninstall()
	{
		global $APPLICATION, $step, $USER, $errors;
		if ($USER->isAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php');
			}
			elseif ($step == 2)
			{
				$this->UnInstallDB([
					'save_tables' => $_REQUEST['save_tables'],
				]);
				$this->UnInstallFiles();

				$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
				\CBitrixComponent::clearComponentCache("bitrix:menu");

				$errors = $this->errors;
				$APPLICATION->includeAdminFile(GetMessage('BICONNECTOR_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep2.php');
			}
		}
	}

	public function GetModuleTasks()
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
