<?php

use Bitrix\AI\Handler\Main;
use Bitrix\AI\Rest;
use Bitrix\AI\Handler\Intranet;
use Bitrix\AI\Handler\Baas;
use Bitrix\AI\Cloud;
use Bitrix\AI\Cloud\Agent;
use Bitrix\AI\Cloud\Scenario\Registration;
use Bitrix\AI\Facade;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\FileTable;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('AI'))
{
	return;
}

class AI extends \CModule
{
	public $MODULE_ID = 'ai';
	public $MODULE_GROUP_RIGHTS = 'Y';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('AI_INSTALL_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('AI_INSTALL__MODULE_DESCRIPTION');
	}

	public function getDocumentRoot(): string
	{
		$context = \Bitrix\Main\Application::getInstance()->getContext();

		return $context->getServer()->getDocumentRoot();
	}

	/**
	 * Call all install methods.
	 * @returm void
	 */
	public function doInstall(): void
	{
		global $APPLICATION, $step, $USER;
		$step = (int)$step;

		if ($USER->IsAdmin())
		{
			if ($step < 2)
			{
				$APPLICATION->IncludeAdminFile(Loc::getMessage('B24C_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/ai/install/step1.php');
			}
			elseif ($step === 2)
			{
				if (!ModuleManager::isModuleInstalled('ai'))
				{
					$this->InstallDB();
					$this->InstallEvents();
					$this->InstallFiles();
					$GLOBALS['errors'] = $this->errors ?? [];

					$GLOBALS['APPLICATION']->includeAdminFile(
						Loc::getMessage('AI_INSTALL_INSTALL_TITLE'),
						$this->getDocumentRoot() . '/bitrix/modules/ai/install/step2.php'
					);
				}
			}
		}
	}

	/**
	 * Call all uninstall methods, include several steps.
	 * @returm void
	 */
	public function doUninstall(): void
	{
		global $APPLICATION;

		$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				Loc::getMessage('AI_INSTALL_UNINSTALL_TITLE'),
				$this->getDocumentRoot() . '/bitrix/modules/ai/install/unstep1.php'
			);
		}
		elseif ($step === 2)
		{
			$params = [];
			if (isset($_GET['savedata']))
			{
				$params['savedata'] = $_GET['savedata'] === 'Y';
			}

			$this->uninstallDB($params);
			$this->uninstallFiles();

			$APPLICATION->includeAdminFile(
				Loc::getMessage('AI_INSTALL_UNINSTALL_TITLE'),
				$this->getDocumentRoot() . '/bitrix/modules/ai/install/unstep2.php'
			);
		}
	}

	/**
	 * Install DB, events, etc.
	 * @return bool
	 */
	public function installDB(): bool
	{
		global $DB, $APPLICATION;

		// db
		if (File::isFileExists($this->getDocumentRoot() . "/bitrix/modules/ai/install/db/{$this->getConnectionType()}/install.sql"))
		{
			$errors = $DB->runSQLBatch(
				$this->getDocumentRoot() . "/bitrix/modules/ai/install/db/{$this->getConnectionType()}/install.sql"
			);
			if ($errors !== false)
			{
				$APPLICATION->throwException(implode('', $errors));
				return false;
			}
		}

		// module
		registerModule('ai');

		try
		{
			if (Loader::includeModule('ai') && Facade\Bitrix24::shouldUseB24() === false)
			{
				$registration = new Registration('en');
				$autoRegisterResult = $registration->tryAutoRegister();

				if ($autoRegisterResult->isSuccess())
				{
					Application::getInstance()->addBackgroundJob(fn () => Agent\PropertiesSync::retrieveModels());
				}
			}
		}
		catch (\Exception)
		{
		}

		// install event handlers
		$eventManager = EventManager::getInstance();
		/** @see \Bitrix\AI\Handler\Main */
		$eventManager->registerEventHandler('main', 'onAfterUserDelete', 'ai', '\\Bitrix\\AI\\Handler\\Main', 'onAfterUserDelete');
		/** @see \Bitrix\AI\Rest */
		$eventManager->registerEventHandler('rest', 'onRestServiceBuildDescription', 'ai', '\\Bitrix\\AI\\Rest', 'onRestServiceBuildDescription');
		/** @see \Bitrix\AI\Rest */
		$eventManager->registerEventHandler('rest', 'onRestAppDelete', 'ai', '\\Bitrix\\AI\\Rest', 'onRestAppDelete');
		/** @see \Bitrix\AI\Handler\Intranet */
		$eventManager->registerEventHandler('intranet', 'onSettingsProvidersCollect', 'ai', '\\Bitrix\\AI\\Handler\\Intranet', 'onSettingsProvidersCollect');
		/** @see \Bitrix\AI\Handler\Baas */
		$eventManager->registerEventHandler('baas', 'onPackagePurchased', 'ai', '\\Bitrix\\AI\\Handler\\Baas', 'onPackagePurchased');

		// agents
		/** @see \Bitrix\AI\QueueJob::clearOldAgent */
		CAgent::AddAgent('Bitrix\AI\QueueJob::clearOldAgent();', 'ai', 'N', 120);
		/** @see \Bitrix\AI\Updater::refreshDbAgent */
		CAgent::AddAgent('Bitrix\AI\Updater::refreshDbAgent();', 'ai', 'N', 3600);
		/** @see \Bitrix\AI\Cloud\Agent\PropertiesSync::retrieveModelsAgent */
		CAgent::addAgent(
			'Bitrix\\AI\\Cloud\\Agent\\PropertiesSync::retrieveModelsAgent();',
			'ai',
		);

		if ((Loader::includeModule('ai') && Facade\Bitrix24::shouldUseB24()))
		{
			\COption::SetOptionString(
				'ai',
				'bitrixaudio_modules',
				json_encode(['crm'])
			);
			\COption::SetOptionString(
				'ai',
				'bitrixaudio_availableIn',
				json_encode(['crm_copilot_fill_item_from_call_engine_audio'])
			);
			\COption::SetOptionString(
				'ai',
				'bitrixaudio_portalSettingsItemsToForceReset',
				json_encode(['crm_copilot_fill_item_from_call_engine_audio'])
			);

			/** @see \Bitrix\AI\Agents\EngineSettings::resetToBitrixAudioInCloudAgent */
			\CAgent::AddAgent(
				'\Bitrix\AI\Agents\EngineSettings::resetToBitrixAudioInCloudAgent();',
				'ai',
				interval: 3600,
				next_exec: ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL'),
			);

			/** @see \Bitrix\AI\Agents\EngineSettings::resetToBitrixGPTInCloudAgent */
			\CAgent::AddAgent(
				'\Bitrix\AI\Agents\EngineSettings::resetToBitrixGPTInCloudAgent();',
				'ai',
				interval: 3600,
				next_exec: ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL'),
			);
		}

		if ((Loader::includeModule('ai') && !Facade\Bitrix24::shouldUseB24()))
		{
			/** @see \Bitrix\AI\Agents\EngineSettings::resetFollowUpTextStepsToBGPTAgent */
			\CAgent::AddAgent(
				'\Bitrix\AI\Agents\EngineSettings::resetFollowUpTextStepsToBGPTAgent();',
				'ai',
				interval: 3600,
				next_exec: ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL'),
			);
		}

		// rights
		//$this->InstallTasks();

		return true;
	}

	/**
	 * Install files.
	 * @return bool
	 */
	public function installFiles(): bool
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ai/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ai/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);

		return true;
	}

	/**
	 * Uninstall DB, events, etc.
	 * @param array $arParams Some params.
	 * @return bool
	 */
	public function uninstallDB(array $arParams = []): bool
	{
		global $APPLICATION, $DB;

		$errors = false;

		// delete DB
		if (File::isFileExists($this->getDocumentRoot() . "/bitrix/modules/ai/install/db/{$this->getConnectionType()}/uninstall.sql"))
		{
			if (isset($arParams['savedata']) && !$arParams['savedata'])
			{
				$errors = $DB->runSQLBatch(
					$this->getDocumentRoot() . "/bitrix/modules/ai/install/db/{$this->getConnectionType()}/uninstall.sql"
				);
			}
		}

		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		Option::delete('ai', ['name' => 'prompt_version']);
		Option::delete('ai', ['name' => '~prompts_system_update_format_version']);

		if (Loader::includeModule('ai'))
		{
			$cloudConfiguration = new Cloud\Configuration();
			$cloudConfiguration->resetCloudRegistration();
			\CAdminNotify::DeleteByTag(Registration::NOTIFICATION_TAG);
		}

		// agents and rights
		CAgent::removeModuleAgents('ai');
		$this->unInstallTasks();

		// uninstall event handlers
		$eventManager = EventManager::getInstance();
		/** @see \Bitrix\AI\Handler\Main */
		$eventManager->unRegisterEventHandler('main', 'onAfterUserDelete', 'ai', '\\Bitrix\\AI\\Handler\\Main', 'onAfterUserDelete');
		/** @see \Bitrix\AI\Rest */
		$eventManager->unRegisterEventHandler('rest', 'onRestServiceBuildDescription', 'ai', '\\Bitrix\\AI\\Rest', 'onRestServiceBuildDescription');
		/** @see \Bitrix\AI\Rest */
		$eventManager->unRegisterEventHandler('rest', 'onRestAppDelete', 'ai', '\\Bitrix\\AI\\Rest', 'onRestAppDelete');
		/** @see \Bitrix\AI\Handler\Intranet */
		$eventManager->unRegisterEventHandler('intranet', 'onSettingsProvidersCollect', 'ai', '\\Bitrix\\AI\\Handler\\Intranet', 'onSettingsProvidersCollect');
		/** @see \Bitrix\AI\Handler\Baas */
		$eventManager->unRegisterEventHandler('baas', 'onPackagePurchased', 'ai', '\\Bitrix\\AI\\Handler\\Baas', 'onPackagePurchased');

		// module
		unregisterModule('ai');

		// delete files finally
		if (isset($arParams['savedata']) && !$arParams['savedata'])
		{
			$res = FileTable::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'=MODULE_ID' => 'ai',
				],
				'order' => [
					'ID' => 'desc',
				],
			]);
			while ($row = $res->fetch())
			{
				CFile::delete($row['ID']);
			}
		}

		return true;
	}

	/**
	 * Uninstall files.
	 * @return bool
	 */
	public function uninstallFiles(): bool
	{
		DeleteDirFilesEx("/bitrix/js/ai/");

		return true;
	}

	private function getConnectionType(): string
	{
		return \Bitrix\Main\Application::getConnection()->getType();
	}
}
