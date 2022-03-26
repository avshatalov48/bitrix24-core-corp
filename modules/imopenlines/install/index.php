<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("imopenlines")) return;

Class imopenlines extends CModule
{
	var $MODULE_ID = "imopenlines";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = IMOPENLINES_VERSION;
			$this->MODULE_VERSION_DATE = IMOPENLINES_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("IMOPENLINES_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IMOPENLINES_MODULE_DESCRIPTION");
	}

	public function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step1.php");
		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB([
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				]);
				$this->InstallFiles();
			}
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step2.php");
		}
		return true;
	}

	public function InstallEvents()
	{
		$orm = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(array(
			'select' => array('ID'),
			'filter' => Array(
				'=EVENT_NAME' => Array('IMOL_HISTORY_LOG', 'IMOL_OPERATOR_ANSWER')
			)
		));

		if(!$orm->fetch())
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php");
		}

		return true;
	}

	public function CheckModules()
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_PULL');
		}

		if (!IsModuleInstalled('imconnector'))
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_CONNECTOR');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_IM');
		}
		else
		{
			$imVersion = \Bitrix\Main\ModuleManager::getVersion('im');
			if (version_compare("16.5.0", $imVersion) == 1)
			{
				$this->errors[] = GetMessage('IMOPENLINES_CHECK_IM_VERSION');
			}
		}

		if(is_array($this->errors) && !empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			return true;
		}
	}

	public function InstallDB($params = [])
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		if ($params['PUBLIC_URL'] <> '' && mb_strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = [];
			}
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_PUBLIC_PATH');
		}

		if(!$this->errors && !$DB->Query("SELECT 'x' FROM b_imopenlines_config", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/db/mysql/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("imopenlines");

		COption::SetOptionString("imopenlines", "portal_url", $params['PUBLIC_URL']);

		RegisterModuleDependences('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		RegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		RegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		RegisterModuleDependences('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		RegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		RegisterModuleDependences('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		RegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		RegisterModuleDependences('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		RegisterModuleDependences('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		RegisterModuleDependences('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		RegisterModuleDependences('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('imconnector', 'OnReceivedPost', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPost');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPostUpdate');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessage', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessage');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageUpdate');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageDel', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageDelete');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusDelivery', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusDelivery');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusReading', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusReading');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusWrites', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusWrites');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusBlock', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedStatusBlock');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedError', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedError');
		$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionStart');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionFinish');

		// imopenlines livechat auth
		/** @see \Bitrix\Main\Engine\Controller::getFullEventName */
		$eventManager->registerEventHandler('main', 'Bitrix\Disk\Controller\File::'.\Bitrix\Main\Engine\Controller::EVENT_ON_BEFORE_ACTION, 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onDiskCheckAuth');
		$eventManager->registerEventHandler('rest', 'onRestCheckAuth', 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onRestCheckAuth');

		//visual constructor
		$eventManager->registerEventHandler('report', 'onReportCategoryCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onCategoriesCollect');
		$eventManager->registerEventHandler('report', 'onReportsCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onReportsCollect');
		$eventManager->registerEventHandler('report', 'onReportViewCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onViewsCollect');
		$eventManager->registerEventHandler('report', 'onDefaultBoardsCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onDefaultBoardsCollect');

		//collect statistics
		$eventManager->registerEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionStart');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionUpdate');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionFinish');
		$eventManager->registerEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onChatAnswer');
		$eventManager->registerEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onChatSkip');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionVote', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionVote');

		$eventManager->registerEventHandler('imopenlines', 'OnImopenlineChangeQueueType', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueTypeChange');
		$eventManager->registerEventHandler('imopenlines', 'OnQueueOperatorsAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsAdd');
		$eventManager->registerEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsDelete');
		$eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsDelete');
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsDelete');
		$eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsUpdate');
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserAdd');
		$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdateBefore');
		$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterUserDelete');
		$eventManager->registerEventHandler('main', 'OnUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserDelete');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayStart', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayStart');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayPause', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayPause');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayContinue', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayContinue');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayEnd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayEnd');
		$eventManager->registerEventHandler('intranet', 'OnStartAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnStartAbsence');
		$eventManager->registerEventHandler('intranet', 'OnEndAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnEndAbsence');

		$eventManager->registerEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnChatAnswer');
		$eventManager->registerEventHandler('imopenlines', 'OnOperatorTransfer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnOperatorTransfer');
		$eventManager->registerEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		$eventManager->registerEventHandler('imopenlines', 'OnChatMarkSpam', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		$eventManager->registerEventHandler('imopenlines', 'OnChatFinish', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');

		$eventManager->registerEventHandler('crm', 'onSiteFormFilledOpenlines', 'imopenlines', '\Bitrix\ImOpenLines\Widget\FormHandler', 'onOpenlinesFormFilled');
		$eventManager->registerEventHandler('crm', 'onSiteFormFillOpenlines', 'imopenlines', '\Bitrix\ImOpenLines\Widget\FormHandler', 'onOpenlinesFormFill');

		CAgent::AddAgent('\Bitrix\ImOpenLines\Integrations\Report\Statistics\Manager::calculateStatisticsInQueue();', 'imopenlines', 'N');

		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::closeByTimeAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::mailByTimeAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Common::deleteBrokenSession();', "imopenlines", "N", 86400, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+86400, "FULL"));
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::dismissedOperatorAgent(0);', "imopenlines", "N", 86400);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session\Agent::sendMessageNoAnswer();', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session\Agent::sendAutomaticMessage();', 'imopenlines', 'N', 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\KpiManager::setExpiredMessagesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));

		if (!IsModuleInstalled('bitrix24'))
		{
			CAgent::AddAgent('\Bitrix\ImOpenLines\Security\Helper::installRolesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		}

		\CModule::IncludeModule("imopenlines");
		$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/db/mysql/install_ft.sql");
		if ($errors === false)
		{
			\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->enableFullTextIndex("SEARCH_CONTENT");
		}

		if (!IsModuleInstalled('bitrix24'))
		{
			$this->InstallChatApps();
		}

		$this->InstallEvents();

		\Bitrix\ImOpenLines\Integrations\Report\Statistic::bind();
		return true;
	}

	public function InstallFiles()
	{
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/components/bitrix", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imconnector/install/pub", $_SERVER["DOCUMENT_ROOT"]."/pub", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);

		return true;
	}

	public function InstallChatApps()
	{
		if (!\CModule::IncludeModule("im"))
		{
			return false;
		}

		$result = \Bitrix\Im\Model\AppTable::getList(Array(
			'filter' => Array('=MODULE_ID' => 'imopenlines', '=CODE' => 'quick')
		))->fetch();

		if (!$result)
		{
			\Bitrix\Im\App::register(Array(
				'MODULE_ID' => 'imopenlines',
				'BOT_ID' => 0,
				'CODE' => 'quick',
				'REGISTERED' => 'Y',
				'ICON_ID' => self::uploadIcon('quick'),
				'IFRAME' => '/desktop_app/iframe/imopenlines_quick.php',
				'IFRAME_WIDTH' => '512',
				'IFRAME_HEIGHT' => '234',
				'CONTEXT' => 'lines',
				'CLASS' => '\Bitrix\ImOpenLines\Chat',
				'METHOD_LANG_GET' => 'onAppLang',
			));
		}

		return true;
	}

	public function UnInstallChatApps()
	{
		if (!\CModule::IncludeModule("im"))
		{
			return false;
		}

		$result = \Bitrix\Im\Model\AppTable::getList(Array(
			'filter' => Array('=MODULE_ID' => 'imopenlines', '=CODE' => 'quick')
		))->fetch();

		if ($result)
		{
			\Bitrix\Im\App::unRegister(Array('ID' => $result['ID'], 'FORCE' => 'Y'));
		}

		return true;
	}

	private static function uploadIcon($iconName)
	{
		if ($iconName == '')
			return false;

		$iconId = false;
		if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imopenlines/install/icon/icon_'.$iconName.'.png'))
		{
			$iconId = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imopenlines/install/icon/icon_'.$iconName.'.png';
		}

		if ($iconId)
		{
			$iconId = \CFile::SaveFile(\CFile::MakeFileArray($iconId), 'imopenlines');
		}

		return $iconId;
	}

	public function UnInstallEvents()
	{
		global $DB;

		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/del_events.php");

		return true;
	}

	public function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imopenlines/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));

			if(!isset($_REQUEST["saveemails"]) || $_REQUEST["saveemails"] != "Y")
				$this->UnInstallEvents();

			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imopenlines/install/unstep2.php");
		}
	}

	public function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/db/mysql/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		UnRegisterModuleDependences('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		UnRegisterModuleDependences('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		UnRegisterModuleDependences('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		UnRegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		UnRegisterModuleDependences('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		UnRegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		UnRegisterModuleDependences('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		UnRegisterModuleDependences('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedPost', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPost');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedPostUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedPostUpdate');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessage', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessage');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedMessageUpdate');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessageDel', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageDelete');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusDelivery', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusDelivery');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusReading', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusReading');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusWrites', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusWrites');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusBlock', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedStatusBlock');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedError', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedError');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('main', 'Bitrix\Disk\Controller\File::'.\Bitrix\Main\Engine\Controller::EVENT_ON_BEFORE_ACTION, 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onDiskCheckAuth');
		$eventManager->unRegisterEventHandler('rest', 'onRestCheckAuth', 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onRestCheckAuth');
		$eventManager->unRegisterEventHandler('rest', 'onRestCheckAuth', 'rest', '\Bitrix\ImOpenLines\Widget\Auth', 'onRestCheckAuth');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnImopenlineChangeQueueType', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueTypeChange');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnQueueOperatorsAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsAdd');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsDelete');
		$eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsDelete');
		$eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsDelete');
		$eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsUpdate');
		$eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserAdd');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdateBefore');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterUserDelete');
		$eventManager->unRegisterEventHandler('main', 'OnUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserDelete');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayStart', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayStart');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayPause', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayPause');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayContinue', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayContinue');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayEnd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayEnd');
		$eventManager->unRegisterEventHandler('intranet', 'OnStartAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnStartAbsence');
		$eventManager->unRegisterEventHandler('intranet', 'OnEndAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnEndAbsence');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnChatAnswer');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnOperatorTransfer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnOperatorTransfer');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatMarkSpam', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatFinish', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionStart');
		$eventManager->unRegisterEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionFinish');

		$this->UnInstallChatApps();

		UnRegisterModule("imopenlines");

		return true;
	}

	public function UnInstallFiles($arParams = array())
	{
		return true;
	}
}
?>
