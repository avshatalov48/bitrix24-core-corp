<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (class_exists('imbot'))
{
	return;
}

final class imbot extends \CModule
{
	public $MODULE_ID = "imbot";
	public $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage("IMBOT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("IMBOT_MODULE_DESCRIPTION");
	}

	public function DoInstall()
	{
		global $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$this->CheckModules();
			$this->getApplication()->IncludeAdminFile(Loc::getMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step1.php");
		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB(Array(
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				));
				$this->InstallFiles();
			}
			$this->getApplication()->IncludeAdminFile(Loc::getMessage("IMBOT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/step2.php");
		}
		return true;
	}

	public function CheckModules()
	{
		if (!Loader::includeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_PULL');
		}

		if (!ModuleManager::isModuleInstalled('im'))
		{
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_IM');
		}
		else
		{
			$imVersion = ModuleManager::getVersion('im');
			if (version_compare("16.1.0", $imVersion) == 1)
			{
				$this->errors[] = Loc::getMessage('IMBOT_CHECK_IM_VERSION');
			}
		}

		if(is_array($this->errors) && !empty($this->errors))
		{
			$this->getApplication()->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;
	}

	public function InstallDB($params = Array())
	{
		$this->errors = false;
		$connection = \Bitrix\Main\Application::getConnection();

		if (!$this->getInstanceDB()->TableExists('b_im_bot_network_session'))
		{
			$errors = $this->getInstanceDB()->runSqlBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imbot/install/db/' . $connection->getType() . '/install.sql');
			if ($errors !== false)
			{
				if (!$this->errors)
				{
					$this->errors = $errors;
				}
			}
		}

		if (
			strlen($params['PUBLIC_URL']) > 0
			&& strlen($params['PUBLIC_URL']) < 12
			||
			!empty($params['PUBLIC_URL'])
			&& (
				!($parsedUrl = \parse_url($params['PUBLIC_URL']))
				|| empty($parsedUrl['host'])
				|| !in_array($parsedUrl['scheme'], ['http', 'https'])
			)
		)
		{
			if (!$this->errors)
			{
				$this->errors = [];
			}
			$this->errors[] = Loc::getMessage('IMBOT_CHECK_PUBLIC_PATH');
		}

		if($this->errors !== false)
		{
			$this->getApplication()->ThrowException(implode("", $this->errors));
			return false;
		}

		ModuleManager::registerModule($this->MODULE_ID);

		Option::set('imbot', 'portal_url', $params['PUBLIC_URL']);

		$eventManager = Main\EventManager::getInstance();
		/** @see \Bitrix\ImBot\Event::onUserRead */
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterUserRead', 'imbot', '\Bitrix\ImBot\Event', 'onUserRead');
		/** @see \Bitrix\ImBot\Event::onChatRead */
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterChatRead', 'imbot', '\Bitrix\ImBot\Event', 'onChatRead');
		/** @see \Bitrix\ImBot\Event::onMessageLike */
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterMessagesLike', 'imbot', '\Bitrix\ImBot\Event', 'onMessageLike');
		/** @see \Bitrix\ImBot\Event::onStartWriting */
		$eventManager->registerEventHandlerCompatible('im', 'OnStartWriting', 'imbot', '\Bitrix\ImBot\Event', 'onStartWriting');
		/** @see \Bitrix\ImBot\Event::onSessionVote */
		$eventManager->registerEventHandlerCompatible('im', 'OnSessionVote', 'imbot', '\Bitrix\ImBot\Event', 'onSessionVote');
		/** @see \Bitrix\ImBot\RestService::onRestServiceBuildDescription */
		$eventManager->registerEventHandlerCompatible('rest', 'OnRestServiceBuildDescription', 'imbot', '\Bitrix\ImBot\RestService', 'onRestServiceBuildDescription');
		/** @see imbot::getTableSchema */
		$eventManager->registerEventHandlerCompatible('perfmon', 'OnGetTableSchema', 'imbot', 'imbot', 'getTableSchema');

		/** @see \Bitrix\ImBot\DialogSession::clearClosedSessions */
		\CAgent::AddAgent('Bitrix\ImBot\DialogSession::clearClosedSessions();', 'imbot', 'N', 3600);

		/** @see \Bitrix\ImBot\DialogSession::clearDeprecatedSessions */
		\CAgent::AddAgent('Bitrix\ImBot\DialogSession::clearDeprecatedSessions();', 'imbot');

		Loader::includeModule('imbot');

		return true;
	}

	public function InstallFiles()
	{
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/",true, true);

		return true;
	}

	public function DoUninstall()
	{
		global $step;
		$step = IntVal($step);

		$botCount = 0;
		if ($step < 2)
		{
			$botCount = Main\UserTable::getCount(['=EXTERNAL_AUTH_ID' => 'bot']);
		}
		if ($botCount > 0)
		{
			$this->getApplication()->IncludeAdminFile(
				Loc::getMessage("IMBOT_UNINSTALL_TITLE"),
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep0.php"
			);
		}
		elseif ($step < 2)
		{
			$this->getApplication()->IncludeAdminFile(
				Loc::getMessage("IMBOT_UNINSTALL_TITLE"),
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep1.php"
			);
		}
		elseif ($step == 2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$this->getApplication()->IncludeAdminFile(
				Loc::getMessage("IMBOT_UNINSTALL_TITLE"),
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/install/unstep2.php"
			);
		}
	}

	public function UnInstallDB($arParams = Array())
	{
		$this->errors = false;

		Loader::includeModule('imbot');

		$eventManager = Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('im', 'OnAfterUserRead', 'imbot', '\Bitrix\ImBot\Event', 'onUserRead');
		$eventManager->unRegisterEventHandler('im', 'OnAfterChatRead', 'imbot', '\Bitrix\ImBot\Event', 'onChatRead');
		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesLike', 'imbot', '\Bitrix\ImBot\Event', 'onMessageLike');
		$eventManager->unRegisterEventHandler('im', 'OnStartWriting', 'imbot', '\Bitrix\ImBot\Event', 'onStartWriting');
		$eventManager->unRegisterEventHandler('im', 'OnSessionVote', 'imbot', '\Bitrix\ImBot\Event', 'onSessionVote');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imbot', '\Bitrix\ImBot\RestService', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('perfmon', 'OnGetTableSchema', 'imbot', 'imbot', 'getTableSchema');

		$dir = new Main\IO\Directory(Main\Application::getDocumentRoot().'/bitrix/modules/imbot/lib/bot/');
		$dirList = $dir->getChildren();
		foreach ($dirList as $dirElement)
		{
			$className = $dirElement->getName();
			$className = explode('.', $className);
			if ($className[0] == 'base')
			{
				continue;
			}

			if (class_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0])) && method_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'))
			{
				call_user_func_array(array('\\Bitrix\\ImBot\\Bot\\'.ucfirst($className[0]), 'unRegister'), Array());
			}
		}

		if (!isset($arParams['savedata']) || $arParams['savedata'] !== true)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$errors = $this->getInstanceDB()->runSqlBatch(sprintf(
				'%s/bitrix/modules/%s/install/db/'.$connection->getType().'/uninstall.sql',
				$_SERVER['DOCUMENT_ROOT'],
				strtolower($this->MODULE_ID)
			));
			if ($errors !== false)
			{
				$this->getApplication()->ThrowException(implode("<br>", $errors));

				return false;
			}
		}

		ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	public function UnInstallFiles()
	{
		\DeleteDirFilesEx("/bitrix/js/imbot/");

		return true;
	}

	/**
	 * Event handler 'perfmon::OnGetTableSchema'.
	 * @see \CPerfomanceSchema::Init
	 * @return array
	 */
	public static function getTableSchema(): array
	{
		return [
			'main' => [
				'b_user' => [
					'ID' => [
						'b_im_bot_network_session' => 'BOT_ID',
					]
				],
			],
		];
	}

	/**
	 * @return \CMain
	 */
	private static function getApplication(): \CMain
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * @return CDatabase
	 */
	private function getInstanceDB(): \CDatabase
	{
		/** @global \CDatabase $DB */
		global $DB;
		return $DB;
	}
}
