<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists('stafftrack'))
{
	return;
}

class stafftrack extends CModule
{
	public $MODULE_ID = 'stafftrack';
	public $MODULE_GROUP_RIGHTS = 'Y';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	public $eventsData = [
		'timeman' => [
			'OnAfterTMDayStart' => ['\Bitrix\StaffTrack\Integration\Timeman\WorkDayService', 'onAfterTmDayStart'],
		],
	];
	public $installDirs = [
		'components' => 'bitrix',
		'js' => 'stafftrack',
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

		$this->MODULE_NAME = Loc::getMessage('STAFFTRACK_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('STAFFTRACK_MODULE_DESCRIPTION');
	}

	/**
	 * Calls all install methods.
	 * @returm void
	 */
	public function DoInstall()
	{
		global $APPLICATION;

		$this->InstallFiles();
		$this->InstallDB();

		$APPLICATION->includeAdminFile(
			Loc::getMessage('STAFFTRACK_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/step1.php'
		);
	}

	/**
	 * Calls all uninstall methods, include several steps.
	 * @returm void
	 */
	public function DoUninstall()
	{
		global $APPLICATION;

		$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				Loc::getMessage('STAFFTRACK_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/unstep1.php'
			);
		}
		elseif ($step === 2)
		{
			$params = [];
			if (isset($_GET['savedata']))
			{
				$params['savedata'] = $_GET['savedata'] === 'Y';
			}
			$this->UnInstallDB($params);
			$this->UnInstallFiles();

			$APPLICATION->includeAdminFile(
				Loc::getMessage('STAFFTRACK_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/unstep2.php'
			);
		}
	}

	/**
	 * Installs DB, events, etc.
	 * @return bool
	 */
	public function InstallDB()
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		// db
		if (!$DB->TableExists('b_stafftrack_shift'))
		{
			$errors = $DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/db/' . $connection->getType() . '/install.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		// module
		RegisterModule($this->MODULE_ID);

		$this->InstallEvents();
		$this->InstallAgents();

		return true;
	}

	/**
	 * Installs files.
	 * @return bool
	 */
	public function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/js',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/js',
			true,
			true
		);

		return true;
	}

	/**
	 * Uninstalls DB, events, etc.
	 * @param array $arParams Some params.
	 * @return bool
	 */
	public function UnInstallDB(array $arParams = [])
	{
		global $APPLICATION, $DB;
		$connection = \Bitrix\Main\Application::getConnection();
		$errors = false;

		// db
		if (isset($arParams['savedata']) && !$arParams['savedata'])
		{
			$errors = $DB->runSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/stafftrack/install/db/' . $connection->getType() . '/uninstall.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		// agents
		\CAgent::removeModuleAgents($this->MODULE_ID);

		$this->UnInstallEvents();

		// module
		UnRegisterModule($this->MODULE_ID);

		return true;
	}

	/**
	 * Uninstalls files.
	 * @return bool
	 */
	public function UnInstallFiles()
	{
		foreach ($this->installDirs as $dir => $subDir)
		{
			if ($dir !== 'components' && $dir !== 'activities')
			{
				deleteDirFilesEx('/bitrix/' . $dir . '/' . $subDir);
			}
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function InstallEvents(): void
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
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
	}

	/**
	 * @return void
	 */
	protected function InstallAgents(): void
	{
		CAgent::AddAgent("Bitrix\\StaffTrack\\Agent\\ClearGeoAgent::run();", $this->MODULE_ID);
	}

	/**
	 * @return void
	 */
	public function UnInstallEvents(): void
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
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
	}
}
