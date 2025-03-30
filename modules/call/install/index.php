<?php

if (class_exists('call'))
{
	return;
}

use Bitrix\Main\Localization\Loc;


class call extends \CModule
{
	public $MODULE_ID = 'call';

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('CALL_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CALL_MODULE_DESCRIPTION');
	}

	public function doInstall()
	{
		global $APPLICATION;
		$this->installFiles();
		$this->installDB();

		$APPLICATION->includeAdminFile(
			Loc::getMessage('CALL_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/step1.php'
		);
	}

	public function installDB()
	{
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();

		$errors = [];
		if (!$connection->isTableExists('b_im_call'))
		{
			$APPLICATION->resetException();
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/call/install/db/' . $connection->getType() . '/install.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		/** @see \Bitrix\Call\Integration\AI\CallAIService::onQueueTaskExecute */
		$eventManager->registerEventHandler(
			'ai',
			'onQueueJobExecute',
			'call',
			'\Bitrix\Call\Integration\AI\CallAIService',
			'onQueueTaskExecute'
		);
		/** @see \Bitrix\Call\Integration\AI\CallAIService::onQueueTaskFail */
		$eventManager->registerEventHandler(
			'ai',
			'onQueueJobFail',
			'call',
			'\Bitrix\Call\Integration\AI\CallAIService',
			'onQueueTaskFail'
		);

		/** @see \Bitrix\Call\Integration\AI\CallAISettings::onTuningLoad */
		$eventManager->registerEventHandler(
			'ai',
			'onTuningLoad',
			'call',
			'\Bitrix\Call\Integration\AI\CallAISettings',
			'onTuningLoad'
		);

		/** @see \Bitrix\Call\Integration\AI\EventService::onCallAiTaskStart */
		$eventManager->registerEventHandler(
			'call',
			'onCallAiTask',
			'call',
			'\Bitrix\Call\Integration\AI\EventService',
			'onCallAiTaskStart'
		);
		/** @see \Bitrix\Call\Integration\AI\EventService::onCallAiTaskComplete */
		$eventManager->registerEventHandler(
			'call',
			'onCallAiOutcome',
			'call',
			'\Bitrix\Call\Integration\AI\EventService',
			'onCallAiTaskComplete'
		);
		/** @see \Bitrix\Call\Integration\AI\EventService::onCallAiTaskFailed */
		$eventManager->registerEventHandler(
			'call',
			'onCallAiFailed',
			'call',
			'\Bitrix\Call\Integration\AI\EventService',
			'onCallAiTaskFailed'
		);
		/** @see \Bitrix\Call\Integration\AI\EventService::onCallFinished */
		$eventManager->registerEventHandler(
			'call',
			'onCallFinished',
			'call',
			'\Bitrix\Call\Integration\AI\EventService',
			'onCallFinished'
		);

		/** @see \Bitrix\Call\EventHandler::onChatUserLeave */
		$eventManager->registerEventHandler(
			'im',
			'OnChatUserDelete',
			'call',
			'\Bitrix\Call\EventHandler',
			'onChatUserLeave'
		);

		/** @see \Bitrix\Call\Integration\AI\CallAIService::finishTasks */
		\CAgent::AddAgent(
			'Bitrix\Call\Integration\AI\CallAIService::finishTasks();',
			'call',
			'N',
			86400,
			'',
			'Y',
			\ConvertTimeStamp(time()+\CTimeZone::GetOffset() + rand(4320, 86400), 'FULL')
		);

		return true;
	}

	public function installFiles()
	{
		\CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/call/install/js',
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/js',
			true,
			true
		);
		\CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/call/install/components',
			$_SERVER['DOCUMENT_ROOT'].'/bitrix/components',
			true,
			true
		);

		return true;
	}

	public function doUninstall()
	{
		global $APPLICATION;

		$step = (int)($_REQUEST['step'] ?? 1);
		$saveData = ($_REQUEST['savedata'] ?? 'N') == 'Y';
		if ($step < 2)
		{
			$APPLICATION->includeAdminFile(
				Loc::getMessage('CALL_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/unstep1.php'
			);
		}
		elseif ($step == 2)
		{
			$this->unInstallDB($saveData);
			$this->unInstallFiles();

			$APPLICATION->includeAdminFile(
				Loc::getMessage('CALL_UNINSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/install/unstep2.php'
			);
		}
	}

	public function unInstallDB(bool $saveData = true)
	{
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();
		$errors = [];
		if (!$saveData)
		{
			$APPLICATION->resetException();
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/call/install/db/' . $connection->getType() . '/uninstall.sql');
		}

		if (!empty($errors))
		{
			$APPLICATION->throwException(implode('', $errors));
			return false;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$res = $connection->query("SELECT * FROM b_module_to_module WHERE FROM_MODULE_ID='call' OR TO_MODULE_ID='call'");
		while ($row = $res->fetch())
		{
			$eventManager->unRegisterEventHandler(
				$row['FROM_MODULE_ID'],
				$row['MESSAGE_ID'],
				$row['TO_MODULE_ID'],
				$row['TO_CLASS'],
				$row['TO_METHOD']
			);
		}

		\CAgent::RemoveModuleAgents('call');

		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}
}
