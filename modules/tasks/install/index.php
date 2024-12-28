<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class tasks extends CModule
{
	var $MODULE_ID = 'tasks';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $errors;

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
		$this->MODULE_NAME = Loc::getMessage('TASKS_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('TASKS_MODULE_DESC');
	}

	function InstallDB($arParams = [])
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		// Database tables creation
		if (!$DB->TableExists('b_tasks'))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/tasks/install/db/' . $connection->getType() . '/install.sql');
		}

		$errors = static::installUserFields();
		if (!empty($errors))
		{
			if (!is_array($this->errors))
			{
				$this->errors = [];
			}
			$this->errors = array_merge($this->errors, $errors);
		}

		$APPLICATION->ResetException();
		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		RegisterModule("tasks");
		RegisterModuleDependences('search', 'OnReindex', 'tasks', 'CTasks', 'OnSearchReindex', 200);
		RegisterModuleDependences('search', 'BeforeIndex', 'tasks', 'CTasksTools', 'FixForumCommentURL', 200);
		RegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		RegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		RegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		RegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");

		RegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks', 'CTaskPlannerMaintance', 'OnPlannerInit');
		RegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks', 'CTaskPlannerMaintance', 'OnPlannerAction');

		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks', 'CTaskRestService', 'OnRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'onFindMethodDescription', 'tasks', '\\Bitrix\\Tasks\\Dispatcher', 'restRegister');

		RegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Topic', 'onBeforeAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Topic', 'onAfterAdd');
		RegisterModuleDependences('forum', 'OnBeforeCommentDelete', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeDelete');
		RegisterModuleDependences('forum', 'OnCommentDelete', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterDelete');
		RegisterModuleDependences('forum', 'OnBeforeCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeAdd');
		RegisterModuleDependences('forum', 'OnBeforeCommentUpdate', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeUpdate');
		RegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterUpdate');

		RegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onForumUninstall');
		RegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onWebdavUninstall');
		RegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onIntranetUninstall');

		RegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks', 'CTaskPlannerMaintance', 'OnAfterTMDayStart');
		RegisterModuleDependences('timeman', 'OnAfterTMEntryUpdate', 'tasks', 'CTaskTimerManager', 'onAfterTMEntryUpdate');

		RegisterModuleDependences('tasks', 'OnBeforeTaskUpdate', 'tasks', 'CTaskTimerManager', 'onBeforeTaskUpdate');
		RegisterModuleDependences('tasks', 'OnBeforeTaskDelete', 'tasks', 'CTaskTimerManager', 'onBeforeTaskDelete');

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
		$eventManager->registerEventHandler('recyclebin', 'OnModuleSurvey', 'tasks', '\Bitrix\Tasks\Integration\Recyclebin\Manager', 'OnModuleSurvey');
		$eventManager->registerEventHandler('recyclebin', 'onAdditionalDataRequest', 'tasks', '\Bitrix\Tasks\Integration\Recyclebin\Manager', 'OnAdditionalDataRequest');

		// user field update
		RegisterModuleDependences("main", "OnAfterUserTypeAdd", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeAdd");
		RegisterModuleDependences("main", "OnAfterUserTypeUpdate", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeUpdate");
		RegisterModuleDependences("main", "OnAfterUserTypeDelete", "tasks", "\\Bitrix\\Tasks\\Util\\UserField", "onAfterUserTypeDelete");

		// disk entity controllers
		RegisterModuleDependences('disk', 'onBuildAdditionalConnectorList', 'tasks', '\Bitrix\Tasks\Integration\Disk', 'onBuildConnectorList');

		// kanban
		$eventManager->registerEventHandler('main', 'OnUserDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onUserDelete');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onSocNetGroupDelete');

		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\ProjectsTable', 'onSocNetGroupDelete');
		$eventManager->registerEventHandler('socialnetwork', 'onContentViewed', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');
		$eventManager->registerEventHandler('socialnetwork', 'onContentFinalizeView', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentFinalizeView');
		$eventManager->registerEventHandler('socialnetwork', 'onLogIndexGetContent', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onIndexGetContent');
		$eventManager->registerEventHandler('socialnetwork', 'onAfterLogFollowSet', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onAfterLogFollowSet');

		//Scrum
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', 'Bitrix\Tasks\Scrum\Internal\EntityTable', 'onSocNetGroupDelete');
		$eventManager->registerEventHandler('socialnetwork', 'OnAfterSocNetLogCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onAfterSocNetLogCommentAdd');
		$eventManager->registerEventHandler('tasks', 'OnTaskAdd', 'tasks', '\Bitrix\Tasks\Scrum\Service\TaskService', 'onAfterTaskAdd');
		$eventManager->registerEventHandler('tasks', 'OnTaskUpdate', 'tasks', '\Bitrix\Tasks\Scrum\Service\TaskService', 'onAfterTaskUpdate');

		// Socialnetwork, project counters
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupAdd', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupAdd');
		$eventManager->registerEventHandler('socialnetwork', 'onBeforeSocNetGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onBeforeGroupUpdate');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUpdate');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupDelete');
		$eventManager->registerEventHandler('socialnetwork', 'OnSocNetFeaturesPermsUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupPermissionUpdate');
		$eventManager->registerEventHandler('socialnetwork', 'OnSocNetUserToGroupAdd', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserAdd');
		$eventManager->registerEventHandler('socialnetwork', 'OnSocNetUserToGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserUpdate');
		$eventManager->registerEventHandler('socialnetwork', 'OnSocNetUserToGroupDelete', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserDelete');

		// bitrix24
		$eventManager->registerEventHandler('bitrix24', 'onFeedbackCollectorCheckCanRun', 'tasks', '\Bitrix\Tasks\Integration\Bitrix24\FeedbackCollector', 'onFeedbackCollectorCheckCanRun');

		// ai
		$eventManager->registerEventHandler('ai', 'onContextGetMessages', 'tasks', '\Bitrix\Tasks\Integration\AI\EventHandler', 'onContextGetMessages');
		$eventManager->registerEventHandler('ai', 'onTuningLoad', 'tasks', '\Bitrix\Tasks\Integration\AI\Settings', 'onTuningLoad');
		$eventManager->registerEventHandler('ai', 'onQueueJobExecute', 'tasks', '\Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestHandler', 'onQueueJobExecute');

		// flow
		$eventManager->registerEventHandler(
			'tasks',
			'OnTaskAdd',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'OnTaskAdd'
		);

		$eventManager->registerEventHandler(
			'tasks',
			'OnTaskUpdate',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onTaskUpdate'
		);

		$eventManager->registerEventHandler(
			'tasks',
			'OnTaskDelete',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onTaskDelete'
		);

		$eventManager->registerEventHandler(
			'socialnetwork',
			'OnBeforeSocNetGroupDelete',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'OnBeforeSocNetGroupDelete'
		);

		$eventManager->registerEventHandler(
			'main',
			'OnAfterUserUpdate',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onAfterUserUpdate',
		);

		$eventManager->registerEventHandler(
			'main',
			'OnAfterUserDelete',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onAfterUserDelete',
		);

		$eventManager->registerEventHandler(
			'tasks',
			'OnTaskAdd',
			'tasks',
			'\Bitrix\Tasks\Flow\Integration\AI\Event\TaskEventListener',
			'OnTaskAdd'
		);

		$eventManager->registerEventHandler(
			'ai',
			'onTuningLoad',
			'tasks',
			'\Bitrix\Tasks\Flow\Integration\AI\FlowSettings',
			'onTuningLoad',
		);

		$this->InstallTasks();

		CModule::includeModule('tasks');

		(new \Bitrix\Tasks\Access\Install\AccessInstaller($DB))->install();
		(new \Bitrix\Tasks\Access\Install\Migration($DB))->migrateTemplateRights();

		$this->addAgents();
		$this->runSteppers();
		$this->setOptions();

		return true;
	}

	public static function installUserFields($moduleId = 'all'): array
	{
		$errors = [];

		if (in_array($moduleId, ['all', 'disk'], true))
		{
			$errors = static::installDiskUserFields($errors);
		}
		if (in_array($moduleId, ['all', 'mail'], true))
		{
			$errors = static::installMailUserFields($errors);
		}

		return $errors;
	}

	private static function installDiskUserFields(array $errors): array
	{
		if (!IsModuleInstalled('disk'))
		{
			return $errors;
		}

		$errors = static::createFileField('TASKS_TASK', $errors);
		$errors = static::createFileField('TASKS_TASK_TEMPLATE', $errors);

		$errors = static::createChecklistFileField('TASKS_TASK_CHECKLIST', $errors);
		$errors = static::createChecklistFileField('TASKS_TASK_TEMPLATE_CHECKLIST', $errors);

		$errors = static::createScrumItemFileField('TASKS_SCRUM_ITEM', $errors);
		$errors = static::createScrumEpicFileField('TASKS_SCRUM_EPIC', $errors);

		$errors = static::createResultFileField($errors);

		return $errors;
	}

	private static function createResultFileField(array $errors): array
	{
		global $APPLICATION;
		$userField = new CUserTypeEntity();

		$fieldRes = CUserTypeEntity::getList([], ['ENTITY_ID'  => 'TASKS_TASK_RESULT', 'FIELD_NAME' => 'UF_RESULT_FILES']);
		if (!$fieldRes->Fetch())
		{
			$userFieldId = $userField->Add([
				'ENTITY_ID' => 'TASKS_TASK_RESULT',
				'FIELD_NAME' => 'UF_RESULT_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			]);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		$fieldRes = CUserTypeEntity::getList([], ['ENTITY_ID'  => 'TASKS_TASK_RESULT', 'FIELD_NAME' => 'UF_RESULT_PREVIEW']);
		if (!$fieldRes->Fetch())
		{
			$userFieldId =  $userField->Add([
				"ENTITY_ID" => "TASKS_TASK_RESULT",
				"FIELD_NAME" => "UF_RESULT_PREVIEW",
				"XML_ID" => "UF_BLOG_POST_URL_PRV",
				"USER_TYPE_ID" => "url_preview",
				"MULTIPLE" => "N",
			]);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private static function createFileField(string $entityId, array $errors): array
	{
		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = CUserTypeEntity::getList(
			['ID' => 'ASC'],
			[
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
			]
		);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->add([
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'XML_ID' => 'TASK_WEBDAV_FILES',
				'MULTIPLE' => 'Y',
				'MANDATORY' =>  null,
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' =>  null,
				'EDIT_IN_LIST' =>  null,
				'IS_SEARCHABLE' =>  'Y',
				'EDIT_FORM_LABEL' => [
					'en' => 'Load files',
					'ru' => 'Load files',
					'de' => 'Load files',
				],
			], false);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private static function createChecklistFileField(string $entityId, array $errors): array
	{
		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_CHECKLIST_FILES',
			]
		);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->Add([
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_CHECKLIST_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			], false);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private static function createScrumItemFileField(string $entityId, array $errors): array
	{
		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_ITEM_FILES',
			]
		);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->Add([
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_ITEM_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			], false);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private static function createScrumEpicFileField(string $entityId, array $errors): array
	{
		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_EPIC_FILES',
			]
		);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->Add([
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_EPIC_FILES',
				'USER_TYPE_ID' => 'disk_file',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			], false);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private static function installMailUserFields(array $errors): array
	{
		if (!isModuleInstalled('mail'))
		{
			return $errors;
		}

		global $APPLICATION;

		$userField = new CUserTypeEntity();
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_MAIL_MESSAGE',
			]
		);

		if (!$userFieldRes->Fetch())
		{
			$userFieldId = $userField->add([
				'ENTITY_ID' => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_MAIL_MESSAGE',
				'USER_TYPE_ID' => 'mail_message',
				'XML_ID' => '',
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'N',
			], false);

			if (!$userFieldId && ($exception = $APPLICATION->getException()))
			{
				$errors[] = $exception->getString();
			}
		}

		return $errors;
	}

	private function addAgents(): void
	{
		$agents = [
			[
				'name' => '\Bitrix\Tasks\Util\AgentManager::sendReminder();',
				'interval' => 60,
			],
			[
				'name' => '\Bitrix\Tasks\Util\AgentManager::notificationThrottleRelease();',
				'interval' => 300,
			],
			[
				'name' => '\Bitrix\Tasks\Util\AgentManager::createOverdueChats();',
				'interval' => 900,
			],
			[
				'name' => '\Bitrix\Tasks\Internals\Effective::agent();',
				'nextExec' => Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime('23:50:00')),
				'disableTimeZone' => true,
			],
			[
				'name' => '\Bitrix\Tasks\Integration\Recyclebin\AutoRemoveTaskAgent::execute();',
				'nextExec' => ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, "FULL"),
			],
		];
		foreach ($agents as $agent)
		{
			$preparedAgent = $this->prepareAgent($agent);
			$this->addAgent($preparedAgent);
		}
	}

	private function prepareAgent(array $agent): array
	{
		$defaultData = [
			'module' => $this->MODULE_ID,
			'period' => 'N',
			'interval' => 86400,
			'dateCheck' => '',
			'active' => 'Y',
			'nextExec' => '',
			'sort' => 100,
			'userId' => false,
			'existError' => true,
			'disableTimeZone' => false,
		];
		foreach ($defaultData as $field => $value)
		{
			$agent[$field] = ($agent[$field] ?? $value);
		}

		return $agent;
	}

	private function addAgent(array $agent): void
	{
		if ($agent['disableTimeZone'])
		{
			CTimeZone::Disable();
		}

		CAgent::AddAgent(
			$agent['name'],
			$agent['module'],
			$agent['period'],
			$agent['interval'],
			$agent['dateCheck'],
			$agent['active'],
			$agent['nextExec'],
			$agent['sort'],
			$agent['userId'],
			$agent['existError']
		);

		if ($agent['disableTimeZone'])
		{
			CTimeZone::Enable();
		}
	}

	private function runSteppers(): void
	{
		$steppers = [
			[
				'class' => \Bitrix\Tasks\Update\EfficiencyRecount::class,
				'delay' => 300,
			],
			[
				'class' => \Bitrix\Tasks\Update\ExpiredAgentCreator::class,
				'delay' => 300,
			],
		];

		if (Option::get('tasks', 'needTaskCheckListConversion', 'Y') === 'Y')
		{
			$steppers[] = [
				'class' => \Bitrix\Tasks\Update\TaskCheckListConverter::class,
				'delay' => 300,
			];
		}
		if (Option::get('tasks', 'needTemplateCheckListConversion', 'Y') === 'Y')
		{
			$steppers[] = [
				'class' => \Bitrix\Tasks\Update\TemplateCheckListConverter::class,
				'delay' => 300,
			];
		}

		foreach ($steppers as $stepper)
		{
			/** @var Bitrix\Main\Update\Stepper $class */
			$class = $stepper['class'];
			$delay = $stepper['delay'];

			$class::bind($delay);
		}
	}

	private function setOptions(): void
	{
		$options = [
			[
				'name' => 'task_comment_allow_edit',
				'value' => 'Y',
			],
			[
				'name' => 'task_comment_allow_remove',
				'value' => 'Y',
			],
			[
				'condition' => Option::get('tasks', 'sanitize_level', 'not installed yet ))') === 'not installed yet ))',
				'name' => 'sanitize_level',
				'value' => CBXSanitizer::SECURE_LEVEL_LOW,
			],
			[
				'name' => 'tasksDisableDefaultListGroups',
				'value' => (new \Bitrix\Main\Type\DateTime())->format('Y-m-d H:i:s'),
			],
		];
		foreach ($options as $option)
		{
			if (!isset($option['condition']) || $option['condition'])
			{
				Option::set('tasks', $option['name'], $option['value']);
			}
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB;

		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		if (!array_key_exists('savedata', $arParams) || $arParams['savedata'] !== 'Y')
		{
			$this->uninstallUserFields();

			$this->errors = $DB->RunSQLBatch(
				$_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/tasks/install/db/".$connection->getType()."/uninstall.sql"
			);
		}

		//delete agents
		CAgent::RemoveModuleAgents("tasks");

		if (CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("tasks");
		}

		UnRegisterModule("tasks");
		UnRegisterModuleDependences('search', 'OnReindex', 'tasks', 'CTasks', 'OnSearchReindex');
		UnRegisterModuleDependences('search', 'BeforeIndex', 'tasks', 'CTasksTools', 'FixForumCommentURL');
		UnRegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");

		UnRegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks', 'CTaskPlannerMaintance', 'OnPlannerInit');
		UnRegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks', 'CTaskPlannerMaintance', 'OnPlannerAction');

		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks', 'CTaskRestService', 'OnRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'onFindMethodDescription', 'tasks', '\\Bitrix\\Tasks\\Dispatcher', 'restRegister');

		UnRegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Topic', 'onBeforeAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Topic', 'onAfterAdd');
		UnRegisterModuleDependences('forum', 'OnBeforeCommentDelete', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeDelete');
		UnRegisterModuleDependences('forum', 'OnCommentDelete', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterDelete');
		UnRegisterModuleDependences('forum', 'OnBeforeCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeAdd');
		UnRegisterModuleDependences('forum', 'OnBeforeCommentUpdate', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onBeforeUpdate');
		UnRegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks', '\Bitrix\Tasks\Integration\Forum\Task\Comment', 'onAfterUpdate');

		UnRegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onForumUninstall');
		UnRegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onWebdavUninstall');
		UnRegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks', 'CTasksRarelyTools', 'onIntranetUninstall');

		UnRegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks', 'CTaskPlannerMaintance', 'OnAfterTMDayStart');
		UnRegisterModuleDependences('timeman', 'OnAfterTMEntryUpdate', 'tasks', 'CTaskTimerManager', 'onAfterTMEntryUpdate');

		UnRegisterModuleDependences('tasks', 'OnBeforeTaskUpdate', 'tasks', 'CTaskTimerManager', 'onBeforeTaskUpdate');
		UnRegisterModuleDependences('tasks', 'OnBeforeTaskDelete', 'tasks', 'CTaskTimerManager', 'onBeforeTaskDelete');

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
		$eventManager->unRegisterEventHandler('socialnetwork', 'onAfterLogFollowSet', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onAfterLogFollowSet');

		// kanban
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Kanban\ProjectsTable', 'onSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('main', 'OnUserDelete', 'tasks', '\Bitrix\Tasks\Kanban\StagesTable', 'onUserDelete');

		$eventManager->unRegisterEventHandler('socialnetwork', 'onContentViewed', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onContentFinalizeView', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\ContentViewHandler', 'onContentFinalizeView');

		$eventManager->unRegisterEventHandler('pull', 'onGetMobileCounter', 'tasks', '\Bitrix\tasks\Integration\Pull\Counter', 'onGetMobileCounter');
		$eventManager->unRegisterEventHandler('pull', 'onGetMobileCounterTypes', 'tasks', '\Bitrix\tasks\Integration\Pull\Counter', 'onGetMobileCounterTypes');

		//Recyclebin
		$eventManager->unRegisterEventHandler('recyclebin', 'OnModuleSurvey', 'tasks', '\Bitrix\Tasks\Integration\Recyclebin\Manager', 'OnModuleSurvey');
		$eventManager->unRegisterEventHandler('recyclebin', 'onAdditionalDataRequest', 'tasks', '\Bitrix\Tasks\Integration\Recyclebin\Manager', 'onAdditionalDataRequest');

		//Scrum
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', 'Bitrix\Tasks\Scrum\Internal\EntityTable', 'onSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnAfterSocNetLogCommentAdd', 'tasks', '\Bitrix\Tasks\Integration\Socialnetwork\Log', 'onAfterSocNetLogCommentAdd');
		$eventManager->unRegisterEventHandler('tasks', 'OnTaskAdd', 'tasks', '\Bitrix\Tasks\Scrum\Service\TaskService', 'onAfterTaskAdd');
		$eventManager->unRegisterEventHandler('tasks', 'OnTaskUpdate', 'tasks', '\Bitrix\Tasks\Scrum\Service\TaskService', 'onAfterTaskUpdate');

		// Socialnetwork, project counters
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupAdd', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupAdd');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onBeforeSocNetGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onBeforeGroupUpdate');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUpdate');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onSocNetGroupDelete', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupDelete');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSocNetFeaturesPermsUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupPermissionUpdate');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSocNetUserToGroupAdd', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserAdd');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSocNetUserToGroupUpdate', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserUpdate');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSocNetUserToGroupDelete', 'tasks', '\Bitrix\Tasks\Integration\SocialNetwork\EventListener', 'onGroupUserDelete');

		// bitrix24
		$eventManager->unRegisterEventHandler('bitrix24', 'onFeedbackCollectorCheckCanRun', 'tasks', '\Bitrix\Tasks\Integration\Bitrix24\FeedbackCollector', 'onFeedbackCollectorCheckCanRun');

		// ai
		$eventManager->unRegisterEventHandler('ai', 'onContextGetMessages', 'tasks', '\Bitrix\Tasks\Integration\AI\EventHandler', 'onContextGetMessages');
		$eventManager->unRegisterEventHandler('ai', 'onTuningLoad', 'tasks', '\Bitrix\Tasks\Integration\AI\Settings', 'onTuningLoad');
		$eventManager->unRegisterEventHandler('ai', 'onQueueJobExecute', 'tasks', '\Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestHandler', 'onQueueJobExecute');

		// flow
		$eventManager->unRegisterEventHandler(
			'tasks',
			'OnTaskAdd',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\FlowTaskTable',
			'onTaskAdd'
		);
		$eventManager->unRegisterEventHandler(
			'tasks',
			'OnTaskUpdate',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\FlowTaskTable',
			'onTaskUpdate'
		);
		$eventManager->unRegisterEventHandler(
			'tasks',
			'OnTaskDelete',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\FlowTaskTable',
			'onTaskDelete'
		);
		$eventManager->unRegisterEventHandler(
			'main',
			'OnAfterUserUpdate',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onAfterUserUpdate',
		);

		$eventManager->unRegisterEventHandler(
			'main',
			'OnAfterUserDelete',
			'tasks',
			'\Bitrix\Tasks\Flow\Internal\Event\FlowEventListener',
			'onAfterUserDelete',
		);

		$eventManager->unRegisterEventHandler(
			'tasks',
			'OnTaskAdd',
			'tasks',
			'\Bitrix\Tasks\Flow\Integration\AI\Event\TaskEventListener',
			'OnTaskAdd'
		);

		$eventManager->unRegisterEventHandler(
			'ai',
			'onTuningLoad',
			'tasks',
			'\Bitrix\Tasks\Flow\Integration\AI\FlowSettings',
			'onTuningLoad',
		);


		// remove tasks from socnetlog table
		if (
			(!array_key_exists('savedata', $arParams) || $arParams['savedata'] !== 'Y')
			&& IsModuleInstalled('socialnetwork')
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$socNetLogRes = CSocNetLog::GetList([], ['EVENT_ID' => 'tasks'], false, false, ['ID']);
			while ($log = $socNetLogRes->Fetch())
			{
				CSocNetLog::Delete($log['ID']);
			}
		}

		// Remove tasks from 'im' module
		if (IsModuleInstalled('im') && CModule::IncludeModule('im'))
		{
			if (method_exists('CIMNotify', 'DeleteByModule'))
			{
				CIMNotify::DeleteByModule('tasks');
			}
		}

		$this->removeOptions();

		return true;
	}

	public function uninstallUserFields(): void
	{
		$this->deleteFileFields('TASKS_TASK');
		$this->deleteFileFields('TASKS_TASK_TEMPLATE');

		$this->deleteChecklistFileFields('TASKS_TASK_CHECKLIST');
		$this->deleteChecklistFileFields('TASKS_TASK_TEMPLATE_CHECKLIST');

		$this->deleteMailFields('TASKS_TASK');

		$this->deleteScrumItemFileFields('TASKS_SCRUM_ITEM');
		$this->deleteScrumEpicFileFields('TASKS_SCRUM_EPIC');

		$this->deleteResultFields();
	}

	private function deleteResultFields()
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => 'TASKS_TASK_RESULT',
				'FIELD_NAME' => 'UF_RESULT_FILES',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}

		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => 'TASKS_TASK_RESULT',
				'FIELD_NAME' => 'UF_RESULT_PREVIEW',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function deleteFileFields(string $entityId): void
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => $entityId,
				'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function deleteChecklistFileFields(string $entityId): void
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => $entityId,
				'FIELD_NAME' => 'UF_CHECKLIST_FILES',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function deleteMailFields(string $entityId): void
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => $entityId,
				'FIELD_NAME' => 'UF_MAIL_MESSAGE',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function deleteScrumItemFileFields(string $entityId): void
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_ITEM_FILES',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function deleteScrumEpicFileFields(string $entityId): void
	{
		$userFieldRes = CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID'  => $entityId,
				'FIELD_NAME' => 'UF_SCRUM_EPIC_FILES',
			]
		);
		if ($userField = $userFieldRes->Fetch())
		{
			(new CUserTypeEntity())->delete($userField['ID']);
		}
	}

	private function removeOptions(): void
	{
		$options = [
			'task_comment_allow_edit',
			'task_comment_allow_remove',
		];
		foreach ($options as $name)
		{
			Option::delete('tasks', ['name' => $name]);
		}
	}

	function InstallEvents()
	{
		global $DB;
		$sIn = "'TASK_REMINDER'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ");
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
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/js",
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
		global $APPLICATION;

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
		global $APPLICATION, $step;

		$step = intval($step);
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
