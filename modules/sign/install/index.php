<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\UI\Form\EntityEditorConfigScope;
use Bitrix\UI\Form\EntityEditorConfiguration;

Loc::loadMessages(__FILE__);

if (class_exists('sign'))
{
	return;
}

class sign extends CModule
{
	public $MODULE_ID = 'sign';
	public $MODULE_GROUP_RIGHTS = 'N';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	/** @var array[][][]  */
	public array $eventsData = [
		'crm' => [
			'onSiteFormFillSign' => [[\Bitrix\Sign\Integration\CRM\Form::class, 'onSiteFormFillSign']],
		],
		'bitrix24' => [
			'onDomainChange' => [[\Bitrix\Sign\Integration\Bitrix24\Domain::class, 'onChangeDomain']],
		],
		'main' => [
			'OnUserTypeBuildList' => [[\Bitrix\Sign\UserFields\SnilsUserType::class, 'OnUserTypeBuildList']],
			'OnUISelectorGetProviderByEntityType' => [[\Bitrix\Sign\Integration\Main\UiSelector\EventHandler::class, 'OnUISelectorGetProviderByEntityType']],
		],
		'pull' => [
			'OnGetDependentModule' => [[\Bitrix\Sign\SignPullSchema::class, 'OnGetDependentModule']],
		],
		'intranet' => [
			'onProfileConfigAdditionalBlocks' => [[\Bitrix\Sign\Config\LegalInfo::class, 'onProfileConfigAdditionalBlocks']],
		],
		'rest' => [
			'OnRestServiceBuildDescription' => [
				[\Bitrix\Sign\Rest\B2e\MySafe::class, 'onRestServiceBuildDescription'],
				[\Bitrix\Sign\Rest\B2e\Provider::class, 'onRestServiceBuildDescription'],
				[\Bitrix\Sign\Rest\B2e\HcmLink\SignedFile::class, 'onRestServiceBuildDescription'],
			]
		],
	];


	public $installDirs = [
		'components' => 'bitrix',
		'js' => 'sign',
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

		$this->MODULE_NAME = Loc::getMessage('SIGN_CORE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('SIGN_CORE_MODULE_DESCRIPTION');
	}

	private function getDocumentRoot(): string
	{
		$context =
			\Bitrix\Main\Application::getInstance()
				->getContext()
		;

		return $context ? $context->getServer()
			->getDocumentRoot() : $_SERVER['DOCUMENT_ROOT'];
	}

	/**
	 * Calls all install methods.
	 * @returm void
	 */
	public function doInstall()
	{
		global $DB, $APPLICATION;

		$this->installFiles();
		$this->installDB();

		$APPLICATION->includeAdminFile(
			Loc::getMessage('SIGN_CORE_INSTALL_TITLE'),
			$this->getDocumentRoot() . '/bitrix/modules/sign/install/step1.php'
		);
	}

	/**
	 * Calls all uninstall methods, include several steps.
	 * @returm void
	 */
	public function doUninstall()
	{
		global $APPLICATION;

		$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

		if (Main\ModuleManager::isModuleInstalled('signmobile'))
		{
			$APPLICATION->throwException(Loc::getMessage('SIGN_MODULE_UNINSTALL_ERROR_SIGNMOBILE'));
		}

		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				Loc::getMessage('SIGN_CORE_UNINSTALL_TITLE'),
				$this->getDocumentRoot() . '/bitrix/modules/sign/install/unstep1.php'
			);
		}
		elseif ($step === 2)
		{
			$params = [];
			if (isset($_GET['savedata']))
			{
				$params['savedata'] = $_GET['savedata'] == 'Y';
			}
			$this->uninstallDB($params);
			$this->uninstallFiles();
			$APPLICATION->includeAdminFile(
				Loc::getMessage('SIGN_CORE_UNINSTALL_TITLE'),
				$this->getDocumentRoot() . '/bitrix/modules/sign/install/unstep2.php'
			);
		}
	}

	/**
	 * Installs DB, events, etc.
	 * @return bool
	 */
	public function installDB()
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();

		// db
		$errors = $DB->runSQLBatch(
			$this->getDocumentRoot().'/bitrix/modules/sign/install/db/' . $connection->getType() . '/install.sql'
		);
		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		// module
		registerModule($this->MODULE_ID);
		$this->InstallEvents();
		$this->installAgents();

		return true;
	}

	/**
	 * Installs files.
	 * @return bool
	 */
	public function installFiles()
	{
		// needed to read in bxlink
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sign/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sign/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sign/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);

		return true;
	}

	/**
	 * Uninstalls DB, events, etc.
	 * @param array $arParams Some params.
	 * @return bool
	 */
	public function uninstallDB(array $arParams = [])
	{
		global $APPLICATION, $DB;
		$connection = \Bitrix\Main\Application::getConnection();

		$errors = false;

		// db
		if (isset($arParams['savedata']) && !$arParams['savedata'])
		{
			$errors = $DB->runSQLBatch(
				$this->getDocumentRoot().'/bitrix/modules/sign/install/db/' . $connection->getType() . '/uninstall.sql'
			);
		}
		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		// agents
		\CAgent::removeModuleAgents($this->MODULE_ID);

		$this->uninstallUserLegalFields();
		$this->UnInstallEvents();

		// module
		unregisterModule($this->MODULE_ID);

		return true;
	}

	/**
	 * Uninstalls files.
	 * @return bool
	 */
	public function uninstallFiles()
	{
		foreach ($this->installDirs as $dir => $subdir)
		{
			if ($dir != 'components' && $dir != 'activities')
			{
				deleteDirFilesEx('/bitrix/' . $dir . '/' . $subdir);
			}
		}

		return true;
	}

	private function uninstallUserLegalFields(): void
	{
		global $USER_FIELD_MANAGER;
		$userTypeEntity = new \CUserTypeEntity();
		foreach ($USER_FIELD_MANAGER->getUserFields('USER_LEGAL') as $property)
		{
			$res = \CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'USER_LEGAL',
					'FIELD_NAME' => $property['FIELD_NAME']
				]
			);

			$fieldData = $res->Fetch();
			if (isset($fieldData['ID']))
			{
				$userTypeEntity->Delete($fieldData['ID']);
			}
		}
	}

	public function InstallEvents(): void
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		foreach ($this->eventsData as $module => $events)
		{
			foreach ($events as $eventCode => $handlers)
			{
				foreach ($handlers as $callback) {
					[$class, $method] = $callback;
					$eventManager->registerEventHandler(
						$module,
						$eventCode,
						$this->MODULE_ID,
						$class,
						$method
					);
				}
			}
		}
	}

	public function UnInstallEvents(): void
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		foreach ($this->eventsData as $module => $events)
		{
			foreach ($events as $eventCode => $handlers)
			{
				foreach ($handlers as $callback) {
					[$class, $method] = $callback;
					$eventManager->unregisterEventHandler(
						$module,
						$eventCode,
						$this->MODULE_ID,
						$class,
						$method
					);
				}
			}
		}
	}

	public function installAgents(): void
	{
		$startTime = ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 60, 'FULL');
		\CAgent::AddAgent(
			name: '\\Bitrix\\Sign\\Service\\Providers\\LegalInfoProviderAgentService::installLegalConfig();',
			module: 'sign',
			period: 'N',
			interval: 3600,
			next_exec: $startTime,
			existError: false
		);
		\CAgent::AddAgent(
			'Bitrix\\Sign\\Agent\\Converter\\ConvertProviderSchemesAgent::run();',
			'sign',
			period: 'N',
			interval: 900,
			next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 3600, 'FULL'),
			existError: false,
		);

		\CAgent::AddAgent(
			name: '\\Bitrix\\Sign\\Agent\\Permission\\ReinstallAccessPermissionsAgent::run();',
			module: 'sign',
			interval: 60,
			next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 960, 'FULL'),
			existError: false,
		);
	}
}
