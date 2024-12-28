<?php

use Bitrix\HumanResources\Compatibility\Event\NewToOldEventHandler;
use Bitrix\HumanResources\Compatibility\Event\HcmLink\JobEventHandler;
use Bitrix\HumanResources\Compatibility\Event\NodeEventHandler;
use Bitrix\HumanResources\Compatibility\Event\UserEventHandler;
use Bitrix\Main\Localization\Loc;

if (class_exists('humanresources'))
{
	return;
}

class HumanResources extends CModule
{
	public $MODULE_ID = 'humanresources';
	public $MODULE_GROUP_RIGHTS = 'N';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

	private $eventsData =
		[
			'iblock' => [
				'OnBeforeIBlockSectionUpdate' => [NodeEventHandler::class, 'onBeforeIBlockSectionUpdate',],
				'OnAfterIBlockSectionAdd' => [NodeEventHandler::class, 'onAfterIBlockSectionAdd',],
				'OnBeforeIBlockSectionDelete' => [NodeEventHandler::class, 'onBeforeIBlockSectionDelete',],
			],
			'main' => [
				'OnAfterUserUpdate' => [UserEventHandler::class, 'onAfterUserUpdate',],
				'OnAfterUserDelete' => [UserEventHandler::class, 'onAfterUserDelete',],
				'OnAfterUserAdd' => [UserEventHandler::class, 'onAfterUserAdd',],
			],
			'humanresources' => [
				'MEMBER_ADDED' => [NewToOldEventHandler::class, 'onMemberAdded',],
				'MEMBER_DELETED' => [NewToOldEventHandler::class, 'onMemberDeleted',],
				'MEMBER_UPDATED' => [NewToOldEventHandler::class, 'onMemberUpdated',],
				'NODE_ADDED' => [NewToOldEventHandler::class, 'onNodeAdded',],
				'NODE_UPDATED' => [NewToOldEventHandler::class, 'onNodeUpdated',],
				'NODE_DELETED' => [NewToOldEventHandler::class, 'onNodeDeleted',],
				'OnHumanResourcesHcmLinkJobIsDone' => [JobEventHandler::class, 'onUpdateDoneJob'],
			],
			'rest' => [
				'OnRestServiceBuildDescription' => [\Bitrix\HumanResources\Marketplace\Rest\HcmLink::class, 'onRestServiceBuildDescription']
			],
		]
	;

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

		$this->MODULE_NAME = Loc::getMessage('HUMAN_RESOURCES_CORE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('HUMAN_RESOURCES_CORE_MODULE_DESCRIPTION');
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
		global $APPLICATION;

		$this->installFiles();
		$this->installDB();
		$this->installEvents();

		$APPLICATION->includeAdminFile(
			Loc::getMessage('HUMAN_RESOURCES_CORE_INSTALL_TITLE'),
			$this->getDocumentRoot() . '/bitrix/modules/humanresources/install/step1.php'
		);
	}

	/**
	 * Calls all uninstall methods, include several steps.
	 * @returm void
	 */
	public function DoUninstall()
	{
		global $APPLICATION;
		$APPLICATION->IncludeAdminFile(GetMessage("HUMAN_RESOURCES_CORE_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/del_denied.php");
	}

	/**
	 * Installs DB, events, etc.
	 * @return bool
	 */
	public function installDB()
	{
		global $DB, $APPLICATION;
		$application = \Bitrix\Main\HttpApplication::getInstance();

		$connectionType = $application->getConnection()->getType();

		$errors = $DB->runSQLBatch(
			$this->getDocumentRoot() .'/bitrix/modules/' . $this->MODULE_ID . '/install/db/' . $connectionType . '/install.sql'
		);
		if ($errors !== false)
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		// module
		registerModule($this->MODULE_ID);

		$DB->runSQLBatch(
			$this->getDocumentRoot() .'/bitrix/modules/' . $this->MODULE_ID . '/install/db/' . $connectionType . '/install_ft.sql'
		);

		return true;
	}

	/**
	 * Installs files.
	 * @return bool
	 */
	public function installFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);

		return true;
	}

	/**
	 * Uninstalls DB, events, etc.
	 * @param array $uninstallParameters Some params.
	 * @return bool
	 */
	public function uninstallDB(array $uninstallParameters = [])
	{
		return true;
	}

	public function installEvents(): void
	{
		$eventManager = Bitrix\Main\EventManager::getInstance();
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

		$this->installAgents();
	}

	/**
	 * Uninstalls files.
	 * @return bool
	 */
	public function uninstallFiles()
	{
		return true;
	}

	private function installAgents(): void
	{
		$startTime = \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 600, 'FULL');
		\CAgent::AddAgent(
			name: 'Bitrix\HumanResources\Access\Install\AccessInstaller::installAgent();',
			module: $this->MODULE_ID,
			interval: 60,
			next_exec: $startTime,
			existError: false,
		);

		\CAgent::AddAgent(
			name: 'Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::startDefaultConverting();',
			module: $this->MODULE_ID,
			interval: 60,
			next_exec: $startTime,
			existError: false,
		);

		\CAgent::AddAgent(
			name: 'Bitrix\HumanResources\Install\Stepper\UpdateSortAndActiveFieldsStepper::checkDefaultConverting();',
			module: $this->MODULE_ID,
			interval: 600,
			next_exec: $startTime,
			existError: false,
		);

		\CAgent::addAgent(
			'Bitrix\HumanResources\Install\Agent\HcmLink\JobCleaner::run();',
			$this->MODULE_ID,
			'N',
			3600,
			existError: false,
		);

		\CAgent::addAgent(
			'Bitrix\HumanResources\Install\Agent\HcmLink\ExpiredFieldValueCleaner::run();',
			$this->MODULE_ID,
			'N',
			3600,
			existError: false,
		);
	}
}
