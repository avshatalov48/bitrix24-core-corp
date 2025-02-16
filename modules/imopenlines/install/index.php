<?php

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__. '/install.php');

if (class_exists("imopenlines"))
{
	return;
}

final class imopenlines extends \CModule
{
	public $MODULE_ID = "imopenlines";
	public $MODULE_GROUP_RIGHTS = "Y";

	private $errors = [];

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage("IMOPENLINES_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("IMOPENLINES_MODULE_DESCRIPTION");
	}

	public function DoInstall()
	{
		global $APPLICATION, $step;
		$step = (int)$step;
		if ($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step1.php");
		}
		elseif ($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB([
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				]);
				$this->InstallFiles();
			}
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step2.php");
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

		if (!$orm->fetch())
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php");
		}

		return true;
	}

	public function CheckModules()
	{
		global $APPLICATION;

		if (!\Bitrix\Main\Loader::includeModule('pull') || !\CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = Loc::getMessage('IMOPENLINES_CHECK_PULL');
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('imconnector'))
		{
			$this->errors[] = Loc::getMessage('IMOPENLINES_CHECK_CONNECTOR');
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('im'))
		{
			$this->errors[] = Loc::getMessage('IMOPENLINES_CHECK_IM');
		}
		else
		{
			$imVersion = \Bitrix\Main\ModuleManager::getVersion('im');
			if (version_compare("16.5.0", $imVersion) == 1)
			{
				$this->errors[] = Loc::getMessage('IMOPENLINES_CHECK_IM_VERSION');
			}
		}

		if (!empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;
	}

	public function InstallDB($params = [])
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();

		$this->errors = [];

		if ($params['PUBLIC_URL'] <> '' && mb_strlen($params['PUBLIC_URL']) < 12)
		{
			$this->errors[] = Loc::getMessage('IMOPENLINES_CHECK_PUBLIC_PATH');
		}

		if (empty($this->errors) && !$DB->TableExists('b_imopenlines_config'))
		{
			$arSQLErrors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imopenlines/install/db/' . $connection->getType() . '/install.sql');
			if (!empty($arSQLErrors))
			{
				$this->errors = $arSQLErrors;
			}
		}

		if (!empty($this->errors))
		{
			$APPLICATION->ThrowException(implode('', $this->errors));
			return false;
		}

		\Bitrix\Main\ModuleManager::registerModule("imopenlines");

		\Bitrix\Main\Config\Option::set("imopenlines", "portal_url", $params['PUBLIC_URL']);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		$eventManager->registerEventHandlerCompatible('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		$eventManager->registerEventHandlerCompatible('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		$eventManager->registerEventHandlerCompatible('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		$eventManager->registerEventHandlerCompatible('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		$eventManager->registerEventHandlerCompatible('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		$eventManager->registerEventHandlerCompatible('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');

		/** @see \Bitrix\Imopenlines\MessageParameter::onInitTypes */
		$eventManager->registerEventHandler('im', 'OnMessageParamTypesInit', 'imopenlines', '\Bitrix\ImOpenLines\MessageParameter', 'onInitTypes');

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
		$eventManager->registerEventHandler('imconnector', 'OnNewChatName', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onNewChatName');
		/** @see \Bitrix\ImOpenLines\Connector::onReceivedCommandStart */
		$eventManager->registerEventHandler('imconnector', 'OnReceivedCommandStart', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedCommandStart');
		$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionStart');
		$eventManager->registerEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionFinish');

		// imopenlines livechat auth
		/** @see \Bitrix\Main\Engine\Controller::getFullEventName */
		$eventManager->registerEventHandler('main', 'Bitrix\Disk\Controller\File::'.\Bitrix\Main\Engine\Controller::EVENT_ON_BEFORE_ACTION, 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onDiskCheckAuth');
		$eventManager->registerEventHandler('rest', 'onRestCheckAuth', 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onRestCheckAuth');

		// imopenlines livechat cache
		$eventManager->registerEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Livechat::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->registerEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Config::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->registerEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterAdd', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->registerEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterDelete', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->registerEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');

		//visual constructor
		$eventManager->registerEventHandler('report', 'onReportCategoryCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onCategoriesCollect');
		$eventManager->registerEventHandler('report', 'onReportsCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onReportsCollect');
		$eventManager->registerEventHandler('report', 'onReportViewCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onViewsCollect');
		$eventManager->registerEventHandler('report', 'onDefaultBoardsCollect', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\EventHandler', 'onDefaultBoardsCollect');

		//collect statistics
		$eventManager->registerEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionStart', 500);
		$eventManager->registerEventHandler('imopenlines', 'OnSessionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionUpdate', 500);
		$eventManager->registerEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionFinish', 500);
		$eventManager->registerEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onChatAnswer', 500);
		$eventManager->registerEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onChatSkip', 500);
		$eventManager->registerEventHandler('imopenlines', 'OnSessionVote', 'imopenlines', '\Bitrix\ImOpenLines\Integrations\Report\Statistics\EventHandler', 'onSessionVote', 500);

		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueTypeChange */
		$eventManager->registerEventHandler('imopenlines', 'OnImopenlineChangeQueueType', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueTypeChange');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueOperatorsAdd */
		$eventManager->registerEventHandler('imopenlines', 'OnQueueOperatorsAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsAdd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueOperatorsDelete */
		$eventManager->registerEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsDelete');
		/** @see \Bitrix\ImOpenLines\Recent::onQueueOperatorsDelete */
		$eventManager->registerEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Recent', 'onQueueOperatorsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnBeforeDepartmentsDelete */
		$eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterDepartmentsDelete */
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnBeforeDepartmentsUpdate */
		$eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterDepartmentsUpdate */
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserAdd */
		$eventManager->registerEventHandler('main', 'OnAfterUserAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserAdd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserUpdateBefore */
		$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdateBefore');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserUpdate */
		$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterUserDelete */
		$eventManager->registerEventHandler('main', 'OnAfterUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterUserDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserDelete */
		$eventManager->registerEventHandler('main', 'OnUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayStart  */
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayStart', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayStart');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayPause  */
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayPause', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayPause');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayContinue  */
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayContinue', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayContinue');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayEnd  */
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayEnd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayEnd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnStartAbsence  */
		$eventManager->registerEventHandler('intranet', 'OnStartAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnStartAbsence');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnEndAbsence  */
		$eventManager->registerEventHandler('intranet', 'OnEndAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnEndAbsence');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnChatAnswer  */
		$eventManager->registerEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnChatAnswer', 50);
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnOperatorTransfer  */
		$eventManager->registerEventHandler('imopenlines', 'OnOperatorTransfer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnOperatorTransfer', 50);
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish  */
		$eventManager->registerEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish', 50);
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish  */
		$eventManager->registerEventHandler('imopenlines', 'OnChatMarkSpam', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish', 50);
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish  */
		$eventManager->registerEventHandler('imopenlines', 'OnChatFinish', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish', 50);

		$eventManager->registerEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\SalesCenter\Catalog', 'OnChatAnswer', 500);

		$eventManager->registerEventHandler('crm', 'onSiteFormFilledOpenlines', 'imopenlines', '\Bitrix\ImOpenLines\Widget\FormHandler', 'onOpenlinesFormFilled');
		$eventManager->registerEventHandler('crm', 'onSiteFormFillOpenlines', 'imopenlines', '\Bitrix\ImOpenLines\Widget\FormHandler', 'onOpenlinesFormFill');

		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentUpdate */
		$eventManager->registerEventHandler('humanresources', 'NODE_UPDATED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentDelete */
		$eventManager->registerEventHandler('humanresources', 'NODE_DELETED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentMemberUpdated */
		$eventManager->registerEventHandler('humanresources', 'MEMBER_UPDATED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentMemberUpdated');


		/** @see \Bitrix\ImOpenLines\Integrations\Report\Statistics\Manager::calculateStatisticsInQueue */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Integrations\Report\Statistics\Manager::calculateStatisticsInQueue();', 'imopenlines', 'N');

		/** @see \Bitrix\ImOpenLines\Session\Agent::transferToNextInQueue */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::transferToNextInQueue(0);', 'imopenlines', "N", 60);
		/** @see \Bitrix\ImOpenLines\Session\Agent::closeByTime */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::closeByTime(0);', 'imopenlines', "N", 60);
		/** @see \Bitrix\ImOpenLines\Session\Agent::mailByTime */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::mailByTime(0);', 'imopenlines', "N", 60);
		/** @see \Bitrix\ImOpenLines\Session\Agent::deleteBrokenSession */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::deleteBrokenSession();', 'imopenlines', "N", 86400, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+86400, "FULL"));
		/** @see \Bitrix\ImOpenLines\Session\Agent::sendMessageNoAnswer */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::sendMessageNoAnswer();', 'imopenlines', "N", 60);
		/** @see \Bitrix\ImOpenLines\Session\Agent::sendAutomaticMessage */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::sendAutomaticMessage();', 'imopenlines', 'N', 60);
		/** @see \Bitrix\ImOpenLines\KpiManager::setExpiredMessagesAgent */
		\CAgent::AddAgent('Bitrix\ImOpenLines\KpiManager::setExpiredMessagesAgent();', 'imopenlines', "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		/** @see \Bitrix\ImOpenLines\Session\Agent::correctionStatusClosedSessionsAgent */
		\CAgent::AddAgent('Bitrix\ImOpenLines\Session\Agent::correctionStatusClosedSessionsAgent();', 'imopenlines', "N", 86400, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+86400, "FULL"));

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			/** @see Bitrix\ImOpenLines\Security\Helper::installRolesAgent */
			\CAgent::AddAgent('Bitrix\ImOpenLines\Security\Helper::installRolesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		}

		\Bitrix\Main\Loader::includeModule("imopenlines");

		$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imopenlines/install/db/' . $connection->getType() . '/install_ft.sql');
		if ($errors === false)
		{
			\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->enableFullTextIndex("SEARCH_CONTENT");
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$this->InstallChatApps();
		}

		$this->InstallEvents();

		/** @see imopenlines::delayInstall */
		\CAgent::AddAgent('imopenlines::delayInstall(0);', 'imopenlines', 'N', 60, '', 'Y', \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+120, "FULL"));

		\Bitrix\ImOpenLines\Integrations\Report\Statistic::bind();

		return true;
	}

	/**
	 * Delayed action until
	 * @param int $retry
	 * @return string
	 */
	public static function delayInstall($retry = 0)
	{
		$retry ++;

		// wait for all necessary modules
		if (
			\Bitrix\Main\Loader::includeModule('imopenlines')
			&& \Bitrix\Main\Loader::includeModule('crm')
			&& \Bitrix\Main\Loader::includeModule('sale')
			&& (count(\Bitrix\ImOpenlines\Security\Helper::getAdministrators()) > 0)
		)
		{
			(new \Bitrix\ImOpenLines\Config)->createPreset();
			return '';
		}

		return $retry <= 100 ? __METHOD__. "({$retry});" : '';
	}

	public function InstallFiles()
	{
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);

		return true;
	}

	/**
	 * @return bool
	 */
	public function UnInstallFiles()
	{
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imopenlines/install/js', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js');
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imopenlines/install/components/bitrix', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix');
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imopenlines/install/activities/bitrix', $_SERVER['DOCUMENT_ROOT'].'/bitrix/activities/bitrix');
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imopenlines/install/tools', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools');

		return true;
	}

	public function InstallChatApps()
	{
		if (!\Bitrix\Main\Loader::includeModule("im"))
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
		if (!\Bitrix\Main\Loader::includeModule("im"))
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
		{
			return false;
		}

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
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/del_events.php");
	}

	public function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = (int)$step;
		if ($step<2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMOPENLINES_UNINSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/unstep1.php");
		}
		elseif ($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));

			if (!isset($_REQUEST["saveemails"]) || $_REQUEST["saveemails"] != "Y")
			{
				$this->UnInstallEvents();
			}

			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(Loc::getMessage("IMOPENLINES_UNINSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/unstep2.php");
		}
	}

	public function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB;
		$connection = \Bitrix\Main\Application::getConnection();

		$this->errors = [];

		if (empty($arParams['savedata']))
		{
			$arSQLErrors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/imopenlines/install/db/' . $connection->getType() . '/uninstall.sql');
			if (!empty($arSQLErrors))
			{
				$this->errors = $arSQLErrors;
			}
		}

		if (!empty($this->errors))
		{
			$APPLICATION->ThrowException(implode('', $this->errors));
			return false;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		$eventManager->unRegisterEventHandler('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		$eventManager->unRegisterEventHandler('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		$eventManager->unRegisterEventHandler('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		$eventManager->unRegisterEventHandler('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		$eventManager->unRegisterEventHandler('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		$eventManager->unRegisterEventHandler('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');

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
		$eventManager->unRegisterEventHandler('imconnector', 'OnNewChatName', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onNewChatName');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('main', 'Bitrix\Disk\Controller\File::'.\Bitrix\Main\Engine\Controller::EVENT_ON_BEFORE_ACTION, 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onDiskCheckAuth');
		$eventManager->unRegisterEventHandler('rest', 'onRestCheckAuth', 'imopenlines', '\Bitrix\ImOpenLines\Widget\Auth', 'onRestCheckAuth');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueTypeChange */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnImopenlineChangeQueueType', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueTypeChange');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueOperatorsAdd */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnQueueOperatorsAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsAdd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onQueueOperatorsDelete */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onQueueOperatorsDelete');
		/** @see \Bitrix\ImOpenLines\Recent::onQueueOperatorsDelete */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnQueueOperatorsDelete', 'imopenlines', '\Bitrix\ImOpenLines\Recent', 'onQueueOperatorsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnBeforeDepartmentsDelete */
		$eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterDepartmentsDelete */
		$eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnBeforeDepartmentsUpdate */
		$eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnBeforeDepartmentsUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterDepartmentsUpdate */
		$eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterDepartmentsUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentUpdate */
		$eventManager->unRegisterEventHandler('humanresources', 'NODE_UPDATED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentDelete */
		$eventManager->unRegisterEventHandler('humanresources', 'NODE_DELETED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onDepartmentMemberUpdated */
		$eventManager->unRegisterEventHandler('humanresources', 'MEMBER_UPDATED', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onDepartmentMemberUpdated');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserAdd */
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserAdd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserAdd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserUpdateBefore */
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdateBefore');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserUpdate */
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserUpdate');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnAfterUserDelete */
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnAfterUserDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onUserDelete */
		$eventManager->unRegisterEventHandler('main', 'OnUserDelete', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onUserDelete');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayStart */
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayStart', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayStart');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayPause */
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayPause', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayPause');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayContinue */
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayContinue', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayContinue');
		/** @see \Bitrix\ImOpenLines\Queue\Event::onAfterTMDayEnd */
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayEnd', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'onAfterTMDayEnd');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnStartAbsence */
		$eventManager->unRegisterEventHandler('intranet', 'OnStartAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnStartAbsence');
		/** @see \Bitrix\ImOpenLines\Queue\Event::OnEndAbsence */
		$eventManager->unRegisterEventHandler('intranet', 'OnEndAbsence', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'OnEndAbsence');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnChatAnswer */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnChatAnswer');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnOperatorTransfer */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnOperatorTransfer', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnOperatorTransfer');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatSkip', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatMarkSpam', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		/** @see \Bitrix\ImOpenLines\Queue\Event::checkFreeSlotOnFinish */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatFinish', 'imopenlines', '\Bitrix\ImOpenLines\Queue\Event', 'checkFreeSlotOnFinish');
		/** @see \Bitrix\ImOpenLines\Connector::onSessionStart */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnSessionStart', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionStart');
		/** @see \Bitrix\ImOpenLines\Connector::onSessionFinish */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnSessionFinish', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onSessionFinish');
		/** @see \Bitrix\ImOpenLines\SalesCenter\Catalog::OnChatAnswer */
		$eventManager->unRegisterEventHandler('imopenlines', 'OnChatAnswer', 'imopenlines', '\Bitrix\ImOpenLines\SalesCenter\Catalog', 'OnChatAnswer');

		$eventManager->unRegisterEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Livechat::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->unRegisterEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Config::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->unRegisterEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterAdd', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->unRegisterEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterDelete', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');
		$eventManager->unRegisterEventHandler('imopenlines', '\Bitrix\Imopenlines\Model\Queue::OnAfterUpdate', 'imopenlines', '\Bitrix\Imopenlines\Widget\Config', 'clearCache');

		$this->UnInstallChatApps();

		\Bitrix\Main\ModuleManager::unRegisterModule("imopenlines");

		return true;
	}
}

