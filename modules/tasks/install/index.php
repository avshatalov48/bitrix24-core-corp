<?
global $MESS;

IncludeModuleLangFile(__FILE__);

Class tasks extends CModule
{
	var $MODULE_ID = "tasks";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $errors;

	function tasks()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = TASKS_VERSION;
			$this->MODULE_VERSION_DATE = TASKS_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("TASKS_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TASKS_MODULE_DESC");
	}


	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_tasks WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/db/".strtolower($DB->type)."/install.sql");
		}



		$errors = self::InstallUserFields();
		if ( ! empty($errors) )
		{
			if ( ! is_array($this->errors) )
				$this->errors = array();

			$this->errors = array_merge($this->errors, $errors);
		}

		$APPLICATION->ResetException();
		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		RegisterModule("tasks");
		RegisterModuleDependences("search", "OnReindex", "tasks", "CTasks", "OnSearchReindex", 200);
		RegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		RegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		RegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		RegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");
		RegisterModuleDependences(
			'search',
			'BeforeIndex',
			'tasks',
			'CTasksTools',
			'FixForumCommentURL',
			200
		);
		RegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerInit');
		RegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerAction');
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks',
			'CTaskRestService', 'OnRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'onFindMethodDescription', 'tasks',
			'\\Bitrix\\Tasks\\Dispatcher', 'restRegister');

		RegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Topic', 'onBeforeAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Topic', 'onAfterAdd');

		RegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Comment', 'onAfterAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Comment', 'onAfterUpdate');

		RegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onForumUninstall');
		RegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onWebdavUninstall');
		RegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onIntranetUninstall');

		RegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks',
			'CTaskPlannerMaintance', 'OnAfterTMDayStart');

		RegisterModuleDependences(
			'timeman',
			'OnAfterTMEntryUpdate',
			'tasks',
			'CTaskTimerManager',
			'onAfterTMEntryUpdate'
		);

		RegisterModuleDependences(
			'tasks',
			'OnBeforeTaskUpdate',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskUpdate'
		);

		RegisterModuleDependences(
			'tasks',
			'OnBeforeTaskDelete',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskDelete'
		);

		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupDelete', 'tasks', 'CTasks', 'onBeforeSocNetGroupDelete');
		RegisterModuleDependences('socialnetwork', 'OnSonetLogFavorites', 'tasks', '\\Bitrix\\Tasks\\Integration\\Socialnetwork\\Task', 'OnSonetLogFavorites');

		RegisterModuleDependences("main", "OnAfterRegisterModule", "main", "tasks", "InstallUserFields", 100, "/modules/tasks/install/index.php"); // check webdav UF

		RegisterModuleDependences("main", "OnBeforeUserTypeAdd", "tasks", "CTasksRarelyTools", "onBeforeUserTypeAdd");
		RegisterModuleDependences("main", "OnBeforeUserTypeUpdate", "tasks", "CTasksRarelyTools", "onBeforeUserTypeUpdate");
		RegisterModuleDependences("main", "OnBeforeUserTypeDelete", "tasks", "CTasksRarelyTools", "onBeforeUserTypeDelete");

		// im "ilike"
		RegisterModuleDependences("main", "OnGetRatingContentOwner", "tasks", "CTaskNotifications", "OnGetRatingContentOwner");
		RegisterModuleDependences("im", "OnGetMessageRatingVote", "tasks", "CTaskNotifications", "OnGetMessageRatingVote");

		// im "answer" on comment notification
		RegisterModuleDependences("im", "OnAnswerNotify", "tasks", "CTaskNotifications", "OnAnswerNotify");

		// "mail" module integration
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('mail', 'onReplyReceivedTASKS_TASK', 'tasks', '\Bitrix\Tasks\Integration\Mail\Task', 'onReplyReceived');
		$eventManager->registerEventHandler('mail', 'onForwardReceivedTASKS_TASK', 'tasks', '\Bitrix\Tasks\Integration\Mail\Task', 'onForwardReceived');

		$eventManager->registerEventHandler('pull', 'onGetMobileCounter', 'tasks', '\Bitrix\Tasks\Integration\Pull\Counter', 'onGetMobileCounter');
		$eventManager->registerEventHandler('pull', 'onGetMobileCounterTypes', 'tasks', '\Bitrix\Tasks\Integration\Pull\Counter', 'onGetMobileCounterTypes');

		//Recyclebin
		$eventManager->registerEventHandler(
			'recyclebin',
			'OnModuleSurvey',
			'tasks',
			'\Bitrix\Tasks\Integration\Recyclebin\Manager',
			'OnModuleSurvey'
		);

		// user field update
		RegisterModuleDependences("main", "OnAfterUserTypeAdd", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeAdd");
		RegisterModuleDependences("main", "OnAfterUserTypeUpdate", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeUpdate");
		RegisterModuleDependences("main", "OnAfterUserTypeDelete", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeDelete");

		// disk entity controllers
		RegisterModuleDependences('disk', 'onBuildAdditionalConnectorList', 'tasks', '\Bitrix\Tasks\Integration\Disk', 'onBuildConnectorList');

		$eventManager->registerEventHandler('socialnetwork', 'onLogIndexGetContent', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onIndexGetContent');

		// kanban
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onSocNetGroupDelete');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\ProjectsTable', 'onSocNetGroupDelete');
		$eventManager->registerEventHandler('main', 'OnUserDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onUserDelete');

		$eventManager->registerEventHandler('socialnetwork', 'onContentViewed', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');

		CAgent::AddAgent('\Bitrix\Tasks\Util\AgentManager::sendReminder();','tasks', 'N', 60);
		CAgent::AddAgent('\Bitrix\Tasks\Util\AgentManager::notificationThrottleRelease();','tasks', 'N', 300);

		// If sanitize_level not set, set up it
		if (COption::GetOptionString('tasks', 'sanitize_level', 'not installed yet ))') === 'not installed yet ))')
			COption::SetOptionString('tasks', 'sanitize_level', CBXSanitizer::SECURE_LEVEL_LOW);

		// turn on comment editing by default
		COption::SetOptionString('tasks', 'task_comment_allow_edit', 'Y', '', '');
		COption::SetOptionString('tasks', 'task_comment_allow_remove', 'Y', '', '');

		$this->InstallTasks();

		CModule::includeModule('tasks');
		if ($DB->type === "MYSQL")
		{
			$DB->Query("CREATE FULLTEXT INDEX IXF_TASKS_SEARCH_INDEX ON b_tasks (SEARCH_INDEX)", true);
			\Bitrix\Tasks\Internals\TaskTable::getEntity()->enableFullTextIndex("SEARCH_INDEX");

			if ($DB->Query("CREATE FULLTEXT INDEX IXF_TASKS_SEARCH_INDEX_SEARCH_INDEX ON b_tasks_search_index (SEARCH_INDEX)", true))
			{
				\Bitrix\Tasks\Internals\Task\SearchIndexTable::getEntity()->enableFullTextIndex("SEARCH_INDEX");
			}
		}

		return true;
	}


	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;

		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			static::deleteFileField('TASKS_TASK');
			static::deleteFileField('TASKS_TASK_TEMPLATE');
			static::deleteMailField();

			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		//delete agents
		CAgent::RemoveModuleAgents("tasks");

		if (CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("tasks");
		}

		UnRegisterModule("tasks");
		UnRegisterModuleDependences("search", "OnReindex", "tasks", "CTasks", "OnSearchReindex");
		UnRegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");
		UnRegisterModuleDependences(
			'search',
			'BeforeIndex',
			'tasks',
			'CTasksTools',
			'FixForumCommentURL'
		);
		UnRegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerInit');
		UnRegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerAction');
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks',
			'CTaskRestService', 'OnRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'onFindMethodDescription', 'tasks',
			'\\Bitrix\\Tasks\\Dispatcher', 'restRegister');

		UnRegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Topic', 'onBeforeAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Topic', 'onAfterAdd');

		UnRegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Comment', 'onAfterAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks',
			'\\Bitrix\\Tasks\\Integration\\Forum\\Task\\Comment', 'onAfterUpdate');

		UnRegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onForumUninstall');
		UnRegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onWebdavUninstall');
		UnRegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks',
			'CTasksRarelyTools', 'onIntranetUninstall');

		UnRegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks',
			'CTaskPlannerMaintance', 'OnAfterTMDayStart');

		UnRegisterModuleDependences(
			'timeman',
			'OnAfterTMEntryUpdate',
			'tasks',
			'CTaskTimerManager',
			'onAfterTMEntryUpdate'
		);

		UnRegisterModuleDependences(
			'tasks',
			'OnBeforeTaskUpdate',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskUpdate'
		);

		UnRegisterModuleDependences(
			'tasks',
			'OnBeforeTaskDelete',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskDelete'
		);

		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupDelete', 'tasks', 'CTasks', 'onBeforeSocNetGroupDelete');
		UnRegisterModuleDependences('socialnetwork', 'OnSonetLogFavorites', 'tasks', '\\Bitrix\\Tasks\\Integration\\Socialnetwork\\Task', 'OnSonetLogFavorites');
		UnRegisterModuleDependences("main", "OnAfterRegisterModule", "main", "tasks", "InstallUserFields", "/modules/tasks/install/index.php"); // check webdav UF
		UnRegisterModuleDependences("main", "OnBeforeUserTypeAdd", "tasks", "CTasksRarelyTools", "onBeforeUserTypeAdd");
		UnRegisterModuleDependences("main", "OnBeforeUserTypeUpdate", "tasks", "CTasksRarelyTools", "onBeforeUserTypeUpdate");
		UnRegisterModuleDependences("main", "OnBeforeUserTypeDelete", "tasks", "CTasksRarelyTools", "onBeforeUserTypeDelete");

		// im "ilike"
		UnRegisterModuleDependences("main", "OnGetRatingContentOwner", "tasks", "CTaskNotifications", "OnGetRatingContentOwner");
		UnRegisterModuleDependences("im", "OnGetMessageRatingVote", "tasks", "CTaskNotifications", "OnGetMessageRatingVote");

		// im "answer" on comment notification
		UnRegisterModuleDependences("im", "OnAnswerNotify", "tasks", "CTaskNotifications", "OnAnswerNotify");

		// "mail" module integration
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('mail', 'onReplyReceivedTASKS_TASK', 'tasks', '\Bitrix\Tasks\Integration\Mail\Task', 'onReplyReceived');
		$eventManager->unRegisterEventHandler('mail', 'onForwardReceivedTASKS_TASK', 'tasks', '\Bitrix\Tasks\Integration\Mail\Task', 'onForwardReceived');

		// user field update
		UnRegisterModuleDependences("main", "OnAfterUserTypeAdd", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeAdd");
		UnRegisterModuleDependences("main", "OnAfterUserTypeUpdate", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeUpdate");
		UnRegisterModuleDependences("main", "OnAfterUserTypeDelete", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeDelete");

		// disk entity controllers
		UnRegisterModuleDependences('disk', 'onBuildAdditionalConnectorList', 'tasks', '\Bitrix\Tasks\Integration\Disk', 'onBuildConnectorList');

		$eventManager->unRegisterEventHandler('socialnetwork', 'onLogIndexGetContent', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onIndexGetContent');

		// kanban
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\ProjectsTable', 'onSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('main', 'OnUserDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onUserDelete');

		$eventManager->unRegisterEventHandler('socialnetwork', 'onContentViewed', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');

		$eventManager->unRegisterEventHandler('pull', 'onGetMobileCounter', 'tasks', '\Bitrix\tasks\Integration\Pull\Counter', 'onGetMobileCounter');
		$eventManager->unRegisterEventHandler('pull', 'onGetMobileCounterTypes', 'tasks', '\Bitrix\tasks\Integration\Pull\Counter', 'onGetMobileCounterTypes');

		//Recyclebin
		$eventManager->unRegisterEventHandler(
			'recyclebin',
			'OnModuleSurvey',
			'tasks',
			'\Bitrix\Tasks\Integration\Recyclebin\Manager',
			'OnModuleSurvey'
		);

		// remove tasks from socnetlog table
		if (
			(
				!array_key_exists("savedata", $arParams)
				|| $arParams["savedata"] != "Y"
			)
			&& IsModuleInstalled('socialnetwork')
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$dbRes = CSocNetLog::GetList(
				array(),
				array("EVENT_ID" => "tasks"),
				false,
				false,
				array("ID")
			);

			if ($dbRes)
			{
				while ($arRes = $dbRes->Fetch())
				{
					CSocNetLog::Delete($arRes["ID"]);
				}
			}
		}

		// Remove tasks from IM
		if (IsModuleInstalled('im') && CModule::IncludeModule('im'))
		{
			if (method_exists('CIMNotify', 'DeleteByModule'))
				CIMNotify::DeleteByModule('tasks');
		}

		// remove comment edit flags
		COption::RemoveOption('tasks', 'task_comment_allow_edit','');
		COption::RemoveOption('tasks', 'task_comment_allow_remove', '');

		return true;
	}


	public static function InstallUserFields($moduleId = 'all')
	{
		global $APPLICATION;

		$errors = array();

		if($moduleId === 'all' || $moduleId === 'disk')
		{
			$errors = self::installDiskUserFields();
		}

		if (in_array($moduleId, array('all', 'mail')))
		{
			if (isModuleInstalled('mail'))
			{
				$uf = new \CUserTypeEntity;
				$res = \CUserTypeEntity::getList(
					array(),
					array(
						'ENTITY_ID' => 'TASKS_TASK',
						'FIELD_NAME' => 'UF_MAIL_MESSAGE'
					)
				);
				if (!$res->fetch())
				{
					$id = $uf->add(array(
						'ENTITY_ID'     => 'TASKS_TASK',
						'FIELD_NAME'    => 'UF_MAIL_MESSAGE',
						'USER_TYPE_ID'  => 'mail_message',
						'XML_ID'        => '',
						'MULTIPLE'      => 'N',
						'MANDATORY'     => 'N',
						'SHOW_FILTER'   => 'N',
						'SHOW_IN_LIST'  => 'N',
						'EDIT_IN_LIST'  => 'N',
						'IS_SEARCHABLE' => 'N',
					), false);

					if (!$id && $APPLICATION->getException())
					{
						$errors[] = $APPLICATION->getException()->getString();
					}
				}
			}
		}

		return ($errors);
	}

	public static function installDiskUserFields()
	{
		$errors = array();

		if(!IsModuleInstalled('disk'))
		{
			return $errors;
		}

		static::createFileField('TASKS_TASK', $errors);
		static::createFileField('TASKS_TASK_TEMPLATE', $errors);

		static::createChecklistFileField('TASKS_TASK_CHECKLIST', $errors);
		static::createChecklistFileField('TASKS_TASK_TEMPLATE_CHECKLIST', $errors);

		return $errors;
	}

	private static function createFileField($entity, array &$errors)
	{
		global $APPLICATION;

		$uf = new CUserTypeEntity;
		$rsData = CUserTypeEntity::getList(array("ID" => "ASC"), array("ENTITY_ID" => $entity, "FIELD_NAME" => 'UF_TASK_WEBDAV_FILES'));
		if (!($rsData && ($arRes = $rsData->Fetch())))
		{
			$intID = $uf->add(array(
				'ENTITY_ID'     => $entity,
				'FIELD_NAME'    => 'UF_TASK_WEBDAV_FILES',
				'USER_TYPE_ID'  => 'disk_file',
				'XML_ID'        => 'TASK_WEBDAV_FILES',
				'MULTIPLE'      => 'Y',
				'MANDATORY'     =>  null,
				'SHOW_FILTER'   => 'N',
				'SHOW_IN_LIST'  =>  null,
				'EDIT_IN_LIST'  =>  null,
				'IS_SEARCHABLE' =>  'Y',
				'EDIT_FORM_LABEL' => array(
					'en' => 'Load files',
					'ru' => 'Load files',
					'de' => 'Load files'
				)
			), false);

			if (false == $intID && ($strEx = $APPLICATION->getException()))
			{
				$errors[] = $strEx->getString();
			}
		}
	}

	private static function createChecklistFileField($entity, array &$errors)
	{
		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = \CUserTypeEntity::getList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => 'UF_CHECKLIST_FILES']);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->Add([
				'ENTITY_ID' => $entity,
				'FIELD_NAME' => 'UF_CHECKLIST_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			], false);

			if (!$userFieldId && $APPLICATION->getException())
			{
				$errors[] = $APPLICATION->getException()->getString();
			}
		}
	}

	private static function deleteFileField($entity)
	{
		$rsUserType = CUserTypeEntity::getList(
			array(),
			array(
				'ENTITY_ID'  => $entity,
				'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
			)
		);

		if ($arUserType = $rsUserType->fetch())
		{
			$obUserField = new CUserTypeEntity;
			$obUserField->delete($arUserType['ID']);
		}
	}

	private static function deleteMailField()
	{
		$userType = \CUserTypeEntity::getList(
			array(),
			array(
				'ENTITY_ID'  => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_MAIL_MESSAGE',
			)
		)->fetch();

		if ($userType)
		{
			$userField = new \CUserTypeEntity;
			$userField->delete($userType['ID']);
		}
	}

	function InstallEvents()
	{
		global $DB;
		$sIn = "'TASK_REMINDER'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events/set_events.php");
		}
		return true;
	}


	function UnInstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events/del_events.php");
		return true;
	}


	function InstallFiles($arParams = array())
	{
		global $DB;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/admin",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
				false
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/activities",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/activities",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/public/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/public/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/public/services",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/services",
				true,
				true
			);

			CUrlRewriter::Add(array(
				"CONDITION" => "#^/stssync/tasks/#",
				"RULE" => "",
				"ID" => "bitrix:stssync.server",
				"PATH" => "/bitrix/services/stssync/tasks/index.php",
			));
		}

		return true;
	}


	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");

		DeleteDirFilesEx("/bitrix/js/tasks/");//scripts
		return true;
	}

	function getModuleTasks()
	{
		return array(
			'tasks_task_template_read' => array(
				'BINDING' => 'task_template',
				'OPERATIONS' => array(
					'read'
				),
			),
			'tasks_task_template_full' => array(
				'BINDING' => 'task_template',
				'OPERATIONS' => array(
					'read',
					'update',
					'delete',
				)
			),
		);
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION;

		if (!CBXFeatures::IsFeatureEditable('Tasks'))
		{
			$this->errors = array(GetMessage('MAIN_FEATURE_ERROR_EDITABLE'));
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(
				GetMessage('TASKS_INSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php');
		}
		elseif (!IsModuleInstalled("tasks"))
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/step1.php");
		}
	}


	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$GLOBALS["CACHE_MANAGER"]->CleanAll();
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/unstep2.php");
		}
	}
}
