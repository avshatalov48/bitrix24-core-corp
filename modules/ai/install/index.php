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

	public array $eventsData = [
		'main' => [
			/** @see \Bitrix\AI\Handler\Main::onAfterUserDelete */
			'onAfterUserDelete' => [Main::class, 'onAfterUserDelete'],
		],
		'rest' => [
			/** @see \Bitrix\AI\Rest::onRestServiceBuildDescription */
			'onRestServiceBuildDescription' => [Rest::class, 'onRestServiceBuildDescription'],
			/** @see \Bitrix\AI\Rest::onRestAppDelete */
			'onRestAppDelete' => [Rest::class, 'onRestAppDelete'],
		],
		'intranet' => [
			/** @see \Bitrix\AI\Handler\Intranet::onSettingsProvidersCollect */
			'onSettingsProvidersCollect' => [Intranet::class, 'onSettingsProvidersCollect'],
		],
		'baas' => [
			/** @see \Bitrix\AI\Handler\Baas::onPackagePurchased **/
			'onPackagePurchased' => [Baas::class, 'onPackagePurchased'],
		],
	];

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
		registerModule($this->MODULE_ID);

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

		//should be deleted after the next release, because it is not used anymore in the next version main, fileman
		if (Loader::includeModule('ai') && Facade\Bitrix24::shouldUseB24() === false)
		{
			\COption::SetOptionString('fileman', 'isCopilotFeatureEnabled', 'Y');
			\COption::SetOptionString('main', 'bitrix:main.post.form:AIImage', 'Y');
			\COption::SetOptionString('main', 'bitrix:main.post.form:AIText', 'Y');
			\COption::SetOptionString('main', 'bitrix:main.post.form:Copilot', 'Y');
		}


		// install event handlers
		$eventManager = EventManager::getInstance();
		foreach ($this->eventsData as $module => $events)
		{
			foreach ($events as $eventCode => $callback)
			{
				$eventManager->registerEventHandler(
					$module,
					$eventCode,
					$this->MODULE_ID,
					$callback[0],
					$callback[1]
				);
			}
		}

		// agents
		/** @see \Bitrix\AI\QueueJob::clearOldAgent */
		CAgent::AddAgent('Bitrix\AI\QueueJob::clearOldAgent();', $this->MODULE_ID, 'N', 120);
		/** @see \Bitrix\AI\Updater::refreshDbAgent */
		CAgent::AddAgent('Bitrix\AI\Updater::refreshDbAgent();', $this->MODULE_ID, 'N', 3600);
		/** @see \Bitrix\AI\Cloud\Agent\PropertiesSync::retrieveModelsAgent */
		CAgent::addAgent(
			'Bitrix\\AI\\Cloud\\Agent\\PropertiesSync::retrieveModelsAgent();',
			$this->MODULE_ID,
		);


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
		CAgent::removeModuleAgents($this->MODULE_ID);
		$this->unInstallTasks();

		// uninstall event handlers
		$eventManager = EventManager::getInstance();
		foreach ($this->eventsData as $module => $events)
		{
			foreach ($events as $eventCode => $callback)
			{
				$eventManager->unregisterEventHandler(
					$module,
					$eventCode,
					$this->MODULE_ID,
					$callback[0],
					$callback[1]
				);
			}
		}

		// module
		unregisterModule($this->MODULE_ID);

		// delete files finally
		if (isset($arParams['savedata']) && !$arParams['savedata'])
		{
			$res = FileTable::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'=MODULE_ID' => $this->MODULE_ID,
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
