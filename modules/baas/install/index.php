<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Baas extends CModule
{
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_VERSION = '23.0.0';
	var $MODULE_VERSION_DATE = '2023-12-07 11:39:58';
	var $MODULE_ID = 'baas';
	var $MODULE_SORT = 10000;
	var $MODULE_GROUP_RIGHTS = 'Y';

	public array $eventsData = [
		'baas' => [
			'onDomainRegistered' => [\Bitrix\Baas\Integration\Main\AdminNotification::class, 'onDomainRegistered'],
		],
	];

	function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('BAAS_MODULE_NAME_24');
		$this->MODULE_DESCRIPTION = Loc::getMessage('BAAS_MODULE_DESCRIPTION_24');
	}

	function GetModuleTasks()
	{
		return array(
			'baas_denied' => array(
				'LETTER' => 'D',
				'BINDING' => 'module',
				'OPERATIONS' => array(
				),
			),
			'baas_read' => array(
				'LETTER' => 'R',
				'BINDING' => 'module',
				'OPERATIONS' => array(
					'baas_read',
				),
			),
			'baas_full' => array(
				'LETTER' => 'F',
				'BINDING' => 'module',
				'OPERATIONS' => array(
					'baas_read',
					'baas_buy',
				),
			),
		);
	}

	function InstallDB()
	{
		$this->errors = false;
		global $DB, $APPLICATION;
		$connection = Main\Application::getConnection();

		if (!$DB->TableExists('b_baas_services'))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/baas/install/db/' . $connection->getType() . '/install.sql');
		}

		if (!empty($this->errors))
		{
			$APPLICATION->ThrowException(implode('', $this->errors));
			return false;
		}

		$this->InstallTasks();

		RegisterModule('baas');

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $errors;

		$connection = Main\Application::getConnection();
		$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/baas/install/db/' . $connection->getType() . '/uninstall.sql');

		CAgent::RemoveAgent('\\Bitrix\\Baas\\Integration\\Main\\Agent::sendLogs();', 'baas');

		UnRegisterModule('baas');
		Main\Config\Option::delete('baas');
		return true;
	}

	function InstallFiles()
	{
		if (!IsModuleInstalled('bxtest'))
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/baas/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/baas/install/components', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components', true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/baas/install/js', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js', true, true);
		}

		return true;
	}

	function DoInstall()
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			return;
		}

		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();

		if (Main\Loader::includeModule('baas'))
		{
			try
			{
				if (\Bitrix\Baas\Service\BillingService::getInstance()->register(true)->isSuccess() !== true)
				{
					throw new Main\SystemException('Error while auto registering the host');
				}
			}
			catch (\Exception)
			{
				(new \Bitrix\Baas\Integration\Main\AdminNotification())->show();
			}
		}
	}

	function DoUninstall()
	{
		global $USER;

		CAdminNotify::DeleteByTag('baas_registration_failed');

		if ($USER->IsAdmin())
		{
			$this->UnInstallEvents();
			$this->UnInstallTasks();
			$this->UnInstallDB();
		}
	}


	function InstallEvents()
	{
		// install event handlers
		$eventManager = Main\EventManager::getInstance();
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

	function UnInstallEvents()
	{
		// uninstall event handlers
		$eventManager = Main\EventManager::getInstance();
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

	function UnInstallFiles()
	{
		return true;
	}


	function GetModuleRightList(): array
	{
		return [
			'reference_id' => ['D', 'R', 'W'],
			'reference' => [
				'[D] ' . Loc::getMessage('BAAS_PERM_D'),
				'[R] ' . Loc::getMessage('BAAS_PERM_R'),
				'[W] ' . Loc::getMessage('BAAS_PERM_W'),
			]
		];
	}
}
