<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("voximplant")) return;

Class voximplant extends CModule
{
	public $MODULE_ID = "voximplant";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = "Y";
	public $errors;

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
			$this->MODULE_VERSION = VI_VERSION;
			$this->MODULE_VERSION_DATE = VI_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("VI_MODULE_NAME_2");
		$this->MODULE_DESCRIPTION = GetMessage("VI_MODULE_DESCRIPTION_2");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(GetMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step1.php");
		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$publicUrl = (string)($_REQUEST['PUBLIC_URL'] ?? '');

				$this->InstallDB([
					'PUBLIC_URL' => $publicUrl,
				]);
				$this->InstallFiles();

				$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
				CBitrixComponent::clearComponentCache("bitrix:menu");
			}
			$APPLICATION->IncludeAdminFile(GetMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step2.php");
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function CheckModules()
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = GetMessage('VI_CHECK_PULL');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = GetMessage('VI_CHECK_IM');
		}

		$mainVersion = \Bitrix\Main\ModuleManager::getVersion('main');
		if (version_compare("14.9.2", $mainVersion) == 1)
		{
			$this->errors[] = GetMessage('VI_CHECK_MAIN');
		}

		if (IsModuleInstalled('intranet'))
		{
			$intranetVersion = \Bitrix\Main\ModuleManager::getVersion('intranet');
			if (version_compare("14.5.6", $intranetVersion) == 1)
			{
				$this->errors[] = GetMessage('VI_CHECK_INTRANET');
			}
		}
		else
		{
			$this->errors[] = GetMessage('VI_CHECK_INTRANET_INSTALL');
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

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if ($params['PUBLIC_URL'] <> '' && mb_strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = GetMessage('VI_CHECK_PUBLIC_PATH');
		}
		if(!$this->errors && !$DB->Query("SELECT 'x' FROM b_voximplant_phone", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/mysql/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		COption::SetOptionString("voximplant", "portal_url", $params['PUBLIC_URL']);

		RegisterModule("voximplant");

		RegisterModuleDependences('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		RegisterModuleDependences('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		RegisterModuleDependences('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		RegisterModuleDependences('main', 'OnAfterUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnAfterUserUpdate');
		RegisterModuleDependences('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');
		RegisterModuleDependences("perfmon", "OnGetTableSchema", "voximplant", "CVoxImplantTableSchema", "OnGetTableSchema");

		RegisterModuleDependences("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		RegisterModuleDependences("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		RegisterModuleDependences("crm", "OnCrmCallbackFormSubmitted", "voximplant", "CVoxImplantCrmHelper", "OnCrmCallbackFormSubmitted");

		RegisterModuleDependences("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'OnRestAppInstall', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppInstall');
		RegisterModuleDependences('rest', 'OnRestAppDelete', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppDelete');
		RegisterModuleDependences("im", "OnGetNotifySchema", "voximplant", "CVoxImplantEvent", "onGetNotifySchema");


		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandler('main', 'OnUserSetLastActivityDate', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onUserSetLastActivityDate');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayStart', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onAfterTMDayStart');
		$eventManager->registerEventHandler('timeman', 'OnAfterTMDayContinue', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onAfterTMDayContinue');

		//telephony analytics, visualconstructor events
		$eventManager->registerEventHandler('report', 'onReportCategoryCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onReportCategoriesCollect');
		$eventManager->registerEventHandler('report', 'onReportsCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onReportHandlerCollect');
		$eventManager->registerEventHandler('report', 'onReportViewCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onViewsCollect');
		$eventManager->registerEventHandler('report', 'onDefaultBoardsCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onDefaultBoardsCollect');
		$eventManager->registerEventHandler('report', 'onAnalyticPageCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onAnalyticPageCollect');
		$eventManager->registerEventHandler('report', 'onAnalyticPageBatchCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');

		$eventManager->registerEventHandler('disk', 'onAfterDeleteFile', 'voximplant', '\Bitrix\Voximplant\Integration\Disk\EventHandler', 'onAfterDeleteFile');
		$eventManager->registerEventHandler('main', 'OnFileDelete', 'voximplant', '\Bitrix\Voximplant\Integration\Main\EventHandler', 'onFileDelete');

		if (!IsModuleInstalled('bitrix24'))
		{
			CAgent::AddAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant", "N", 300);
		}
		CAgent::AddAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::finishStaleCalls();", "voximplant", "N", 86400);
		CAgent::AddAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::deleteOldCalls();", "voximplant", "N", 864000);

		$this->InstallDefaultData();
		$this->InstallUserFields();

		CModule::IncludeModule("voximplant");

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/voximplant", true, true);

		return true;
	}

	function InstallDefaultData()
	{
		if(!CModule::IncludeModule('voximplant'))
			return;

		$this->CreateDefaultGroup();
		$this->CreateDefaultPermissions();
		$this->CreateDefaultIvr();
	}

	/**
	 * Creates default roles (for b24 roles will be created with wizard)
	 */
	function CreateDefaultPermissions()
	{
		$checkCursor = \Bitrix\Voximplant\Model\RoleTable::getList(array('limit' => 1));
		if(!$checkCursor->fetch() && !\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$defaultRoles = array(
				'admin' => array(
					'NAME' => GetMessage('VOXIMPLANT_ROLE_ADMIN'),
					'PERMISSIONS' => array(
						'CALL_DETAIL' => array(
							'VIEW' => 'X',
						),
						'CALL' => array(
							'PERFORM' => 'X'
						),
						'CALL_RECORD' => array(
							'LISTEN' => 'X'
						),
						'USER' => array(
							'MODIFY' => 'X'
						),
						'SETTINGS' => array(
							'MODIFY' => 'X'
						),
						'LINE' => array(
							'MODIFY' => 'X'
						),
						'BALANCE' => array(
							'MODIFY' => 'X'
						)
					)
				),
				'chief' => array(
					'NAME' => GetMessage('VOXIMPLANT_ROLE_CHIEF'),
					'PERMISSIONS' => array(
						'CALL_DETAIL' => array(
							'VIEW' => 'X',
						),
						'CALL' => array(
							'PERFORM' => 'X'
						),
						'CALL_RECORD' => array(
							'LISTEN' => 'X'
						),
					)
				),
				'department_head' => array(
					'NAME' => GetMessage('VOXIMPLANT_ROLE_DEPARTMENT_HEAD'),
					'PERMISSIONS' => array(
						'CALL_DETAIL' => array(
							'VIEW' => 'D',
						),
						'CALL' => array(
							'PERFORM' => 'X'
						),
						'CALL_RECORD' => array(
							'LISTEN' => 'D'
						),
					)
				),
				'manager' => array(
					'NAME' => GetMessage('VOXIMPLANT_ROLE_MANAGER'),
					'PERMISSIONS' => array(
						'CALL_DETAIL' => array(
							'VIEW' => 'A',
						),
						'CALL' => array(
							'PERFORM' => 'X'
						),
						'CALL_RECORD' => array(
							'LISTEN' => 'A'
						),
					)
				)
			);

			$roleIds = array();
			foreach ($defaultRoles as $roleCode => $role)
			{
				$addResult = \Bitrix\Voximplant\Model\RoleTable::add(array(
					'NAME' => $role['NAME'],
				));

				$roleId = $addResult->getId();
				if ($roleId)
				{
					$roleIds[$roleCode] = $roleId;
					\Bitrix\Voximplant\Security\RoleManager::setRolePermissions($roleId, $role['PERMISSIONS']);
				}
			}

			if (isset($roleIds['admin']))
			{
				\Bitrix\Voximplant\Model\RoleAccessTable::add(array(
					'ROLE_ID' => $roleIds['admin'],
					'ACCESS_CODE' => 'G1'
				));
			}

			if (isset($roleIds['manager']) && \Bitrix\Main\Loader::includeModule('intranet'))
			{
				$departmentTree = CIntranetUtils::GetDeparmentsTree();
				$rootDepartment = (int)$departmentTree[0][0];

				if ($rootDepartment > 0)
				{
					\Bitrix\Voximplant\Model\RoleAccessTable::add(array(
						'ROLE_ID' => $roleIds['manager'],
						'ACCESS_CODE' => 'DR'.$rootDepartment
					));
				}
			}
		}
	}

	/**
	 * Creates default group of users and populates it with members of the administrators group.
	 */
	function CreateDefaultGroup()
	{
		$checkCursor = \Bitrix\Voximplant\Model\QueueTable::getList(array(
			'limit' => 1
		));
		if($checkCursor->fetch())
			return;

		$admins = array();
		$adminCursor = \Bitrix\Main\Application::getConnection()->query('SELECT u.ID as ID FROM b_user u INNER JOIN b_user_group g ON u.ID = g.USER_ID WHERE g.GROUP_ID = 1');
		while ($row = $adminCursor->fetch())
		{
			$admins[] = $row['ID'];
		}

		$insertResult = \Bitrix\Voximplant\Model\QueueTable::add(array(
			'NAME' => GetMessage('VOXIMPLANT_DEFAULT_GROUP'),
			'WAIT_TIME' => 5
		));
		$defaultGroupId = $insertResult->getId();
		COption::SetOptionString("voximplant", "default_group_id", $defaultGroupId);
		foreach ($admins as $adminId)
		{
			\Bitrix\Voximplant\Model\QueueUserTable::add(array(
				'QUEUE_ID' => $defaultGroupId,
				'USER_ID' => $adminId
			));
		}
	}

	/**
	 * Creates default IVR menu
	 */
	function CreateDefaultIvr()
	{
		$checkCursor = \Bitrix\Voximplant\Model\IvrTable::getList(array(
			'limit' => 1
		));

		if($checkCursor->fetch())
			return;

		$insertResult = \Bitrix\Voximplant\Model\IvrTable::add(array(
			'NAME' => GetMessage('VOXIMPLANT_DEFAULT_IVR')
		));
		$ivrId = $insertResult->getId();

		$insertResult = \Bitrix\Voximplant\Model\IvrItemTable::add(array(
			'IVR_ID' => $ivrId,
			'TYPE' => \Bitrix\Voximplant\Ivr\Item::TYPE_MESSAGE,
			'MESSAGE' => GetMessage('VOXIMPLANT_DEFAULT_ITEM'),
			'TTS_VOICE' => \Bitrix\Voximplant\Tts\Language::getDefaultVoice(\Bitrix\Main\Application::getInstance()->getContext()->getLanguage()),
			'TIMEOUT' => 2,
			'TIMEOUT_ACTION' => \Bitrix\Voximplant\Ivr\Action::ACTION_EXIT
		));
		$itemId = $insertResult->getId();

		\Bitrix\Voximplant\Model\IvrTable::update($ivrId, array(
			'FIRST_ITEM_ID' => $itemId
		));

		\Bitrix\Voximplant\Model\IvrActionTable::add(array(
			'ITEM_ID' => $itemId,
			'DIGIT' => '0',
			'ACTION' => \Bitrix\Voximplant\Ivr\Action::ACTION_EXIT
		));
	}

	function InstallUserFields()
	{
		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PASSWORD';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: user password';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_BACKPHONE';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_BACKPHONE'] = 'VoxImplant: user backphone';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
				}
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: phone';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE_PASSWORD';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: phone password';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}
	}

	function UnInstallEvents()
	{
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("VI_UNINSTALL_TITLE_2"), $DOCUMENT_ROOT."/bitrix/modules/voximplant/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
			CBitrixComponent::clearComponentCache("bitrix:menu");

			$APPLICATION->IncludeAdminFile(GetMessage("VI_UNINSTALL_TITLE_2"), $DOCUMENT_ROOT."/bitrix/modules/voximplant/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/mysql/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		UnRegisterModuleDependences('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		UnRegisterModuleDependences('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		UnRegisterModuleDependences('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnAfterUserUpdate');
		UnRegisterModuleDependences('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');

		UnRegisterModuleDependences("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		UnRegisterModuleDependences("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");

		UnRegisterModuleDependences("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'OnRestAppDelete', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppDelete');
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "voximplant", "CVoxImplantEvent", "onGetNotifySchema");

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler('main', 'OnUserSetLastActivityDate', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onUserSetLastActivityDate');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayStart', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onAfterTMDayStart');
		$eventManager->unRegisterEventHandler('timeman', 'OnAfterTMDayContinue', 'voximplant', '\Bitrix\Voximplant\CallQueue', 'onAfterTMDayContinue');

		//telephony analytics, visualconstructor events
		$eventManager->unRegisterEventHandler('report', 'onReportCategoryCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onReportCategoriesCollect');
		$eventManager->unRegisterEventHandler('report', 'onReportsCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onReportHandlerCollect');
		$eventManager->unRegisterEventHandler('report', 'onReportViewCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onViewsCollect');
		$eventManager->unRegisterEventHandler('report', 'onDefaultBoardsCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onDefaultBoardsCollect');
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onAnalyticPageCollect');
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageBatchCollect', 'voximplant', '\Bitrix\Voximplant\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');

		$eventManager->unRegisterEventHandler('disk', 'onAfterDeleteFile', 'voximplant', '\Bitrix\Voximplant\Integration\Disk\EventHandler', 'onAfterDeleteFile');
		$eventManager->unRegisterEventHandler('main', 'OnFileDelete', 'voximplant', '\Bitrix\Voximplant\Integration\Main\EventHandler', 'onFileDelete');

		CAgent::RemoveAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant");
		CAgent::RemoveAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::finishStaleCalls();", "voximplant");
		CAgent::RemoveAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::deleteOldCalls();", "voximplant");


		$this->UnInstallUserFields($arParams);

		UnRegisterModule("voximplant");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		return true;
	}

	function UnInstallUserFields($arParams = Array())
	{
		if (!$arParams['savedata'])
		{
			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_BACKPHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}
		}

		return true;
	}

	public function migrateToBox()
	{
		COption::RemoveOption($this->MODULE_ID);
	}
}
