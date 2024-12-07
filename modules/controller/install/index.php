<?php
IncludeModuleLangFile(__FILE__);

class controller extends CModule
{
	public $MODULE_ID = 'controller';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_CSS;
	public $MODULE_GROUP_RIGHTS = 'N';
	public $errors = false;

	public function __construct()
	{
		$arModuleVersion = [];

		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

		$this->MODULE_NAME = GetMessage('CTRL_INST_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('CTRL_INST_DESC');
	}

	public function InstallDB()
	{
		/** @var CDatabase $DB */
		/** @var CMain $APPLICATION */
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();

		$this->errors = false;

		if (!$DB->TableExists('b_controller_member'))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/db/' . $connection->getType() . '/install.sql');
			if (!$this->errors)
			{
				$DB->Query("
					INSERT INTO b_controller_group (
						ID, NAME, DATE_CREATE, CREATED_BY, MODIFIED_BY, TIMESTAMP_X
					) VALUES (
						1, '(default)', now(), 1, 1, now()
					)
				");
			}
		}

		if ($this->errors)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}

		RegisterModule('controller');

		CAgent::AddAgent('CControllerMember::UnregisterExpiredAgent();', 'controller', 'Y', 86400);
		CAgent::AddAgent('CControllerAgent::CleanUp();', 'controller', 'N', 86400);

		RegisterModuleDependences('perfmon', 'OnGetTableSchema', 'controller', 'controller', 'OnGetTableSchema');

		$this->InstallTasks();

		return true;
	}

	public function UnInstallDB($arParams = [])
	{
		/** @var CDatabase $DB */
		/** @var CMain $APPLICATION */
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		if (!array_key_exists('savedata', $arParams) || ($arParams['savedata'] != 'Y'))
		{
			if ($DB->Query("SELECT 'x' FROM b_controller_member", true))
			{
				$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/db/' . $connection->getType() . '/uninstall.sql');
			}
		}

		UnRegisterModuleDependences('perfmon', 'OnGetTableSchema', 'controller', 'controller', 'OnGetTableSchema');

		CAgent::RemoveAgent('CControllerMember::UnregisterExpiredAgent();');
		CAgent::RemoveAgent('CControllerAgent::CleanUp();');

		$this->UnInstallTasks();

		UnRegisterModule('controller');

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br>', $this->errors));
			return false;
		}

		return true;
	}

	public function InstallFiles()
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', false);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/controller', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/themes', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/activities', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/activities', true, true);
		if (IsModuleInstalled('bizproc'))
		{
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/bizproc/templates', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bizproc/templates', true, true);
			$langs = CLanguage::GetList();
			while ($lang = $langs->Fetch())
			{
				CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/lang/' . $lang['LID'] . '/install/bizproc/templates', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bizproc/lang/' . $lang['LID'] . '/templates', true, true);
			}
		}
		return true;
	}

	public function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/admin/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/themes/.default/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes/.default');//css
		DeleteDirFilesEx('/bitrix/themes/.default/icons/controller/');//icons
		DeleteDirFilesEx('/bitrix/images/controller/');//images
		return true;
	}

	public function InstallEvents()
	{
		global $DB;
		$sIn = "'CONTROLLER_MEMBER_REGISTER', 'CONTROLLER_MEMBER_CLOSED', 'CONTROLLER_MEMBER_OPENED'";
		$rs = $DB->Query('SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (' . $sIn . ') ');
		$ar = $rs->Fetch();
		if ($ar['C'] <= 0)
		{
			include $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/events/set_events.php';
		}
		return true;
	}

	public function UnInstallEvents()
	{
		global $DB;
		$sIn = "'CONTROLLER_MEMBER_REGISTER', 'CONTROLLER_MEMBER_CLOSED', 'CONTROLLER_MEMBER_OPENED'";
		$DB->Query('DELETE FROM b_event_message WHERE EVENT_NAME IN (' . $sIn . ') ');
		$DB->Query('DELETE FROM b_event_type WHERE EVENT_NAME IN (' . $sIn . ') ');
		return true;
	}

	public function DoInstall()
	{
		/** @var CMain $APPLICATION */
		global $APPLICATION;
		$RIGHT = CMain::GetGroupRight('controller');
		if ($RIGHT < 'W')
		{
			return;
		}

		if (!CBXFeatures::IsFeatureEditable('Controller'))
		{
			$APPLICATION->ThrowException(GetMessage('MAIN_FEATURE_ERROR_EDITABLE'));
			$APPLICATION->IncludeAdminFile(GetMessage('CTRL_INST_STEP1'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/step.php');
		}
		else
		{
			$this->InstallDB();
			$this->InstallFiles();
			$this->InstallEvents();
			CBXFeatures::SetFeatureEnabled('Controller', true);
			$APPLICATION->IncludeAdminFile(GetMessage('CTRL_INST_STEP1'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/step.php');
		}
	}

	public function DoUninstall()
	{
		/** @var CMain $APPLICATION */
		global $APPLICATION, $step;

		$RIGHT = CMain::GetGroupRight('controller');
		if ($RIGHT >= 'W')
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage('CTRL_INST_STEP1_UN'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/unstep1.php');
			}
			elseif ($step == 2)
			{
				$this->UnInstallDB([
					'savedata' => $_REQUEST['savedata'],
				]);
				//message types and templates
				if ($_REQUEST['save_templates'] != 'Y')
				{
					$this->UnInstallEvents();
				}
				$this->UnInstallFiles();
				CBXFeatures::SetFeatureEnabled('Controller', false);
				$GLOBALS['errors'] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage('CTRL_INST_STEP1_UN'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/install/unstep.php');
			}
		}
	}

	public function GetModuleRightList()
	{
		$arr = [
			'reference_id' => ['D', 'L', 'R', 'T', 'V', 'W'],
			'reference' => [
				'[D] ' . GetMessage('CTRL_PERM_D'),
				'[L] ' . GetMessage('CTRL_PERM_L'),
				'[R] ' . GetMessage('CTRL_PERM_R'),
				'[T] ' . GetMessage('CTRL_PERM_T'),
				'[V] ' . GetMessage('CTRL_PERM_V'),
				'[W] ' . GetMessage('CTRL_PERM_W'),
			]
		];
		return $arr;
	}

	public function GetModuleTasks()
	{
		return [
			'controller_deny' => [
				'LETTER' => 'D',
				'BINDING' => 'module',
				'OPERATIONS' => []
			],
			'controller_auth' => [
				'LETTER' => 'L',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'controller_member_auth',
				]
			],
			'controller_read' => [
				'LETTER' => 'R',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'controller_settings_view',
				]
			],
			'controller_add' => [
				'LETTER' => 'T',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'controller_settings_view',
					'controller_member_add',
				]
			],
			'controller_site' => [
				'LETTER' => 'V',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'controller_settings_view',
					'controller_member_view',
					'controller_member_auth',
					'controller_member_auth_admin',
					'controller_member_add',
					'controller_member_edit',
					'controller_member_delete',
					'controller_member_settings_update',
					'controller_member_counters_update',
					'controller_member_history_view',
					'controller_task_view',
					'controller_task_run',
					'controller_task_delete',
					'controller_log_view',
					'controller_log_delete',
					'controller_run_command',
					'controller_upload_file',
				]
			],
			'controller_full' => [
				'LETTER' => 'W',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'controller_settings_view',
					'controller_settings_change',
					'controller_member_view',
					'controller_member_auth',
					'controller_member_auth_admin',
					'controller_member_grant_auth',
					'controller_member_add',
					'controller_member_edit',
					'controller_member_delete',
					'controller_member_disconnect',
					'controller_member_updates_run',
					'controller_member_settings_update',
					'controller_member_counters_update',
					'controller_member_history_view',
					'controller_group_view',
					'controller_group_manage',
					'controller_task_view',
					'controller_task_run',
					'controller_task_delete',
					'controller_log_view',
					'controller_log_delete',
					'controller_run_command',
					'controller_upload_file',
					'controller_counters_view',
					'controller_counters_manage',
					'controller_auth_view',
					'controller_auth_manage',
					'controller_auth_log_view',
				]
			],
		];
	}

	public static function OnGetTableSchema()
	{
		return [
			'controller' => [
				'b_controller_group' => [
					'ID' => [
						'b_controller_member' => 'CONTROLLER_GROUP_ID',
						'b_controller_counter_group' => 'CONTROLLER_GROUP_ID',
					]
				],
				'b_controller_member' => [
					'ID' => [
						'b_controller_task' => 'CONTROLLER_MEMBER_ID',
						'b_controller_log' => 'CONTROLLER_MEMBER_ID',
						'b_controller_counter_value' => 'CONTROLLER_MEMBER_ID',
						'b_controller_member_log' => 'CONTROLLER_MEMBER_ID',
					],
					'MEMBER_ID' => [
						'b_controller_command' => 'MEMBER_ID',
					],
				],
				'b_controller_task' => [
					'ID' => [
						'b_controller_command' => 'TASK_ID',
						'b_controller_log' => 'TASK_ID',
					],
				],
				'b_controller_counter' => [
					'ID' => [
						'b_controller_counter_group' => 'CONTROLLER_COUNTER_ID',
						'b_controller_counter_value' => 'CONTROLLER_COUNTER_ID',
					],
				],
			],
			'main' => [
				'b_user' => [
					'ID' => [
						'b_controller_group' => 'MODIFIED_BY',
						'b_controller_group^' => 'CREATED_BY',
						'b_controller_member' => 'MODIFIED_BY',
						'b_controller_member^' => 'CREATED_BY',
						'b_controller_log' => 'USER_ID',
						'b_controller_member_log' => 'USER_ID',
					]
				],
			],
		];
	}
}
