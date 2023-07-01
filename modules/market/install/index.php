<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

if (class_exists('Market'))
{
	return;
}

class Market extends \CModule
{
	public $MODULE_ID = 'market';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	private $MODULE_FOLDER;
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

		$this->MODULE_NAME = Loc::getMessage('MARKET_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('MARKET_INSTALL_DESCRIPTION');
		$this->MODULE_FOLDER = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID;
	}

	public function installFiles($params = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/market/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/market/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/market/install/images",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/market", true, true);

		if(ModuleManager::isModuleInstalled('intranet'))
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/market/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
		}

		return true;
	}

	public function installDB()
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if (!$DB->Query('SELECT 1 FROM b_market_tag WHERE 1=0', true))
		{
			$this->errors = $DB->RunSQLBatch(
				$this->MODULE_FOLDER . '/install/db/mysql/install.sql'
			);
		}

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}
		else
		{
			$eventManager = EventManager::getInstance();
			foreach ($this->listEventHandler() as $event)
			{
				$eventManager->registerEventHandler(...$event);
			}

			foreach ($this->listAgentHandler() as $agent)
			{
				\CAgent::AddAgent(...$agent);
			}

			ModuleManager::registerModule($this->MODULE_ID);

			return true;
		}
	}

	public function uninstallDB($arParams = [])
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		if (!array_key_exists('save_tables', $arParams) || $arParams['save_tables'] !== 'Y')
		{
			$this->errors = $DB->RunSQLBatch(
				$this->MODULE_FOLDER . '/install/db/mysql/uninstall.sql'
			);
		}

		$eventManager = EventManager::getInstance();
		foreach ($this->listEventHandler() as $event)
		{
			$eventManager->unRegisterEventHandler(...$event);
		}

		\CAgent::RemoveModuleAgents($this->MODULE_ID);

		ModuleManager::unRegisterModule($this->MODULE_ID);

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}

		return true;
	}

	private function listEventHandler()
	{
		return [
			[
				'main',
				'OnAfterRegisterModule',
				'market',
				'\Bitrix\Market\Tag\Manager',
				'onAfterRegisterModule',
			],
			[
				'main',
				'OnAfterUnRegisterModule',
				'market',
				'\Bitrix\Market\Tag\Manager',
				'onAfterUnRegisterModule',
			],
			//Rest integration
			[
				'main',
				'OnAfterSetOption_~mp24_paid',
				'market',
				'\Bitrix\Market\Integration\Rest\TagHandler',
				'onChangeSubscription',
			],
			[
				'main',
				'OnAfterSetOption_~mp24_paid_date',
				'market',
				'\Bitrix\Market\Integration\Rest\TagHandler',
				'onChangeSubscription',
			],
			//Rest integration end
			//VoxImplant integration
			[
				'voximplant',
				'\Bitrix\Voximplant\Sip::OnAfterAdd',
				'market',
				'\Bitrix\Market\Integration\Voximplant\TagHandler',
				'onChangeSip',
			],
			[
				'voximplant',
				'\Bitrix\Voximplant\Sip::OnAfterUpdate',
				'market',
				'\Bitrix\Market\Integration\Voximplant\TagHandler',
				'onChangeSip',
			],
			[
				'voximplant',
				'\Bitrix\Voximplant\Sip::OnAfterDelete',
				'market',
				'\Bitrix\Market\Integration\Voximplant\TagHandler',
				'onChangeSip',
			],
			//end Voximplant integration
			//Landing integration
			[
				'landing',
				'onLandingPublication',
				'market',
				'\Bitrix\Market\Integration\Landing\TagHandler',
				'onChangeLandingPublication',
			],
			[
				'landing',
				'onLandingAfterUnPublication',
				'market',
				'\Bitrix\Market\Integration\Landing\TagHandler',
				'onChangeLandingPublication',
			],
			//end Landing integration
			//Intranet integration
			[
				'intranet',
				'onAfterChangeLeftMenuPreset',
				'market',
				'\Bitrix\Market\Integration\Intranet\TagHandler',
				'onAfterChangeLeftMenuPreset',
			],
			//end Intranet integration
			//Main integration
			[
				'main',
				'OnAfterUserUpdate',
				'market',
				'\Bitrix\Market\Integration\Main\TagHandler',
				'onAfterUserUpdate',
			],
			[
				'main',
				'OnAfterSetOption_~PARAM_CLIENT_LANG',
				'market',
				'\Bitrix\Market\Integration\Main\TagHandler',
				'onChangeClientLang',
			],
			[
				'main',
				'OnAfterSetOption_~controller_group_name',
				'market',
				'\Bitrix\Market\Integration\Main\TagHandler',
				'onBitrix24LicenseChange',
			],
			//end Main integration
			//ImOpenLines integration
			[
				'imopenlines',
				'\Bitrix\ImOpenLines\Model\Config::OnAfterAdd',
				'market',
				'\Bitrix\Market\Integration\ImOpenLines\TagHandler',
				'onChangeConfig'
			],
			[
				'imopenlines',
				'\Bitrix\ImOpenLines\Model\Config::OnAfterUpdate',
				'market',
				'\Bitrix\Market\Integration\ImOpenLines\TagHandler',
				'onChangeConfig'
			],
			[
				'imopenlines',
				'\Bitrix\ImOpenLines\Model\Config::OnAfterDelete',
				'market',
				'\Bitrix\Market\Integration\ImOpenLines\TagHandler',
				'onChangeConfig'
			],
			//end ImOpenLines integration
			//BizProc integration
			[
				'bizproc',
				'onAfterWorkflowTemplateAdd',
				'market',
				'\Bitrix\Market\Integration\BizProc\TagHandler',
				'onEventAdd'
			],
			[
				'bizproc',
				'onAfterWorkflowTemplateDelete',
				'market',
				'\Bitrix\Market\Integration\BizProc\TagHandler',
				'onEventDelete'
			],
			//BizProc end integration
		];
	}

	private function listAgentHandler()
	{
		return [
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'intranet\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 900, 'FULL'),
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'landing\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1000, 'FULL'),
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'voximplant\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1100, 'FULL'),
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'rest\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1200, 'FULL'),
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'imopenlines\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1300, 'FULL')
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'main\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1400, 'FULL')
			],
			[
				'\Bitrix\Market\Tag\Manager::doAgentOnceLoad(\'bizproc\');',
				$this->MODULE_ID,
				'N',
				86400,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 1500, 'FULL')
			],
		];
	}

	public function uninstallFiles()
	{
		return true;
	}

	public function doInstall()
	{
		global $APPLICATION, $step, $USER;
		if ($USER->isAdmin())
		{
			$step = (int)$step;
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(
					Loc::getMessage('MARKET_INSTALL_TITLE'),
					$this->MODULE_FOLDER . '/install/step1.php'
				);
			}
			elseif ($step == 2)
			{
				if ($this->installDB())
				{
					$this->installFiles(
						[
							'public_dir' => (isset($_REQUEST['install_public']) && $_REQUEST['install_public'] == 'Y') ? 'market' : '',
							'public_rewrite' => (isset($_REQUEST['public_rewrite']) && $_REQUEST['public_rewrite'] == 'Y'),
						]
					);
				}
				$GLOBALS['errors'] = $this->errors;
				$APPLICATION->includeAdminFile(
					Loc::getMessage('MARKET_INSTALL_TITLE'),
					$this->MODULE_FOLDER . '/install/step2.php'
				);
			}
		}
	}

	public function doUninstall()
	{
		global $APPLICATION, $step, $USER;
		if ($USER->isAdmin())
		{
			$step = (int)$step;
			if ($step < 2)
			{
				$APPLICATION->includeAdminFile(
					Loc::getMessage('MARKET_UNINSTALL_TITLE'),
					$this->MODULE_FOLDER . '/install/unstep1.php'
				);
			}
			elseif ($step == 2)
			{
				$this->unInstallDB([
					'save_tables' => $_REQUEST['save_tables'] ?? null,
				]);
				$this->unInstallFiles();
				$GLOBALS['errors'] = $this->errors;
				$APPLICATION->includeAdminFile(
					Loc::getMessage('MARKET_UNINSTALL_TITLE'),
					$this->MODULE_FOLDER . '/install/unstep2.php'
				);
			}
		}
	}
}
