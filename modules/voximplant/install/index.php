<?php

if (class_exists("voximplant"))
{
	return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__. '/install.php');


class voximplant extends \CModule
{
	public $MODULE_ID = 'voximplant';
	public $MODULE_GROUP_RIGHTS = 'Y';
	public $errors = [];

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage("VI_MODULE_NAME_2");
		$this->MODULE_DESCRIPTION = Loc::getMessage("VI_MODULE_DESCRIPTION_2");
	}

	public function DoInstall()
	{
		global $APPLICATION;

		$step = (int)($_REQUEST['step'] ?? 1);
		if ($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(Loc::getMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step1.php");
		}
		elseif ($step == 2)
		{
			if ($this->CheckModules())
			{
				$publicUrl = (string)($_REQUEST['PUBLIC_URL'] ?? '');

				$this->InstallDB([
					'PUBLIC_URL' => $publicUrl,
				]);
				$this->InstallFiles();

				$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
				\CBitrixComponent::clearComponentCache("bitrix:menu");
			}
			$APPLICATION->IncludeAdminFile(Loc::getMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step2.php");
		}

		return true;
	}

	public function CheckModules()
	{
		global $APPLICATION;

		if (!\Bitrix\Main\Loader::includeModule('pull') || !\CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = Loc::getMessage('VI_CHECK_PULL');
		}

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('im'))
		{
			$this->errors[] = Loc::getMessage('VI_CHECK_IM');
		}

		$mainVersion = \Bitrix\Main\ModuleManager::getVersion('main');
		if (version_compare("14.9.2", $mainVersion) == 1)
		{
			$this->errors[] = Loc::getMessage('VI_CHECK_MAIN');
		}

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
		{
			$intranetVersion = \Bitrix\Main\ModuleManager::getVersion('intranet');
			if (version_compare("14.5.6", $intranetVersion) == 1)
			{
				$this->errors[] = Loc::getMessage('VI_CHECK_INTRANET');
			}
		}
		else
		{
			$this->errors[] = Loc::getMessage('VI_CHECK_INTRANET_INSTALL');
		}

		if (is_array($this->errors) && !empty($this->errors))
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

		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;
		if ($params['PUBLIC_URL'] <> '' && mb_strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = [];
			}
			$this->errors[] = Loc::getMessage('VI_CHECK_PUBLIC_PATH');
		}
		if (!$this->errors && !$DB->Query("SELECT 'x' FROM b_voximplant_phone", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/".$connection->getType()."/install.sql");
		}

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		\Bitrix\Main\Config\Option::set("voximplant", "portal_url", $params['PUBLIC_URL']);

		\Bitrix\Main\ModuleManager::registerModule("voximplant");

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandlerCompatible('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		$eventManager->registerEventHandlerCompatible('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		$eventManager->registerEventHandlerCompatible('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		$eventManager->registerEventHandlerCompatible('main', 'OnAfterUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnAfterUserUpdate');
		$eventManager->registerEventHandlerCompatible('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');
		$eventManager->registerEventHandlerCompatible("perfmon", "OnGetTableSchema", "voximplant", "CVoxImplantTableSchema", "OnGetTableSchema");

		$eventManager->registerEventHandlerCompatible("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		$eventManager->registerEventHandlerCompatible("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		$eventManager->registerEventHandlerCompatible("crm", "OnCrmCallbackFormSubmitted", "voximplant", "CVoxImplantCrmHelper", "OnCrmCallbackFormSubmitted");

		$eventManager->registerEventHandlerCompatible("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		$eventManager->registerEventHandlerCompatible('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');
		$eventManager->registerEventHandlerCompatible('rest', 'OnRestAppInstall', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppInstall');
		$eventManager->registerEventHandlerCompatible('rest', 'OnRestAppDelete', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppDelete');
		$eventManager->registerEventHandlerCompatible("im", "OnGetNotifySchema", "voximplant", "CVoxImplantEvent", "onGetNotifySchema");

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

		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			\CAgent::AddAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant", "N", 300);
		}
		\CAgent::AddAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::finishStaleCalls();", "voximplant", "N", 86400);
		\CAgent::AddAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::deleteOldCalls();", "voximplant", "N", 864000);

		$this->InstallDefaultData();
		$this->InstallUserFields();

		\Bitrix\Main\Loader::includeModule("voximplant");

		return true;
	}

	public function InstallFiles()
	{
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		\CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/voximplant", true, true);

		return true;
	}

	public function InstallDefaultData()
	{
		if (!\Bitrix\Main\Loader::includeModule('voximplant'))
		{
			return;
		}

		$this->CreateDefaultGroup();
		$this->CreateDefaultPermissions();
		$this->CreateDefaultIvr();
	}

	/**
	 * Creates default roles (for b24 roles will be created with wizard)
	 */
	public function CreateDefaultPermissions()
	{
		$checkCursor = \Bitrix\Voximplant\Model\RoleTable::getList(['limit' => 1]);
		if (!$checkCursor->fetch() && !\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$defaultRoles = array(
				'admin' => array(
					'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_ADMIN'),
					'PERMISSIONS' => array(
						'CALL_DETAIL' => array(
							'VIEW' => 'X',
						),
						'CALL' => array(
							'PERFORM' => 'X'
						),
						'CALL_RECORD' => array(
							'LISTEN' => 'X',
							'MODIFY' => 'X',
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
					'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_CHIEF'),
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
					'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_DEPARTMENT_HEAD'),
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
					'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_MANAGER'),
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

			$roleIds = [];
			foreach ($defaultRoles as $roleCode => $role)
			{
				$addResult = \Bitrix\Voximplant\Model\RoleTable::add([
					'NAME' => $role['NAME'],
				]);

				$roleId = $addResult->getId();
				if ($roleId)
				{
					$roleIds[$roleCode] = $roleId;
					\Bitrix\Voximplant\Security\RoleManager::setRolePermissions($roleId, $role['PERMISSIONS']);
				}
			}

			if (isset($roleIds['admin']))
			{
				\Bitrix\Voximplant\Model\RoleAccessTable::add([
					'ROLE_ID' => $roleIds['admin'],
					'ACCESS_CODE' => 'G1'
				]);
			}

			if (isset($roleIds['manager']) && \Bitrix\Main\Loader::includeModule('intranet'))
			{
				$structureService = \Bitrix\Voximplant\Integration\HumanResources\StructureService::getInstance();
				$rootDepartment = $structureService->getRootDepartmentId();
				if ($rootDepartment > 0)
				{
					\Bitrix\Voximplant\Model\RoleAccessTable::add([
						'ROLE_ID' => $roleIds['manager'],
						'ACCESS_CODE' => 'DR'.$rootDepartment
					]);
				}
			}
		}
	}

	/**
	 * Creates default group of users and populates it with members of the administrators group.
	 */
	public function CreateDefaultGroup()
	{
		$checkCursor = \Bitrix\Voximplant\Model\QueueTable::getList([
			'limit' => 1
		]);
		if ($checkCursor->fetch())
		{
			return;
		}

		$admins = [];
		$adminCursor = \Bitrix\Main\Application::getConnection()->query('SELECT u.ID as ID FROM b_user u INNER JOIN b_user_group g ON u.ID = g.USER_ID WHERE g.GROUP_ID = 1');
		while ($row = $adminCursor->fetch())
		{
			$admins[] = $row['ID'];
		}

		$insertResult = \Bitrix\Voximplant\Model\QueueTable::add([
			'NAME' => Loc::getMessage('VOXIMPLANT_DEFAULT_GROUP'),
			'WAIT_TIME' => 5
		]);
		$defaultGroupId = $insertResult->getId();
		\Bitrix\Main\Config\Option::set("voximplant", "default_group_id", $defaultGroupId);
		foreach ($admins as $adminId)
		{
			\Bitrix\Voximplant\Model\QueueUserTable::add([
				'QUEUE_ID' => $defaultGroupId,
				'USER_ID' => $adminId
			]);
		}
	}

	/**
	 * Creates default IVR menu
	 */
	public function CreateDefaultIvr()
	{
		$checkCursor = \Bitrix\Voximplant\Model\IvrTable::getList([
			'limit' => 1
		]);

		if ($checkCursor->fetch())
		{
			return;
		}

		$insertResult = \Bitrix\Voximplant\Model\IvrTable::add([
			'NAME' => Loc::getMessage('VOXIMPLANT_DEFAULT_IVR')
		]);
		$ivrId = $insertResult->getId();

		$insertResult = \Bitrix\Voximplant\Model\IvrItemTable::add([
			'IVR_ID' => $ivrId,
			'TYPE' => \Bitrix\Voximplant\Ivr\Item::TYPE_MESSAGE,
			'MESSAGE' => Loc::getMessage('VOXIMPLANT_DEFAULT_ITEM'),
			'TTS_VOICE' => \Bitrix\Voximplant\Tts\Language::getDefaultVoice(\Bitrix\Main\Application::getInstance()->getContext()->getLanguage()),
			'TIMEOUT' => 2,
			'TIMEOUT_ACTION' => \Bitrix\Voximplant\Ivr\Action::ACTION_EXIT
		]);
		$itemId = $insertResult->getId();

		\Bitrix\Voximplant\Model\IvrTable::update($ivrId, [
			'FIRST_ITEM_ID' => $itemId
		]);

		\Bitrix\Voximplant\Model\IvrActionTable::add([
			'ITEM_ID' => $itemId,
			'DIGIT' => '0',
			'ACTION' => \Bitrix\Voximplant\Ivr\Action::ACTION_EXIT
		]);
	}

	public function InstallUserFields()
	{
		$arFields = [];
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PASSWORD';

		$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = \CUserTypeEntity::GetList([], array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if (!$rs->Fetch())
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

				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = [];
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_BACKPHONE';

		$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = \CUserTypeEntity::GetList([], array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if (!$rs->Fetch())
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
				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = [];
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE';

		$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = \CUserTypeEntity::GetList([], array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if (!$rs->Fetch())
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

				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = [];
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE_PASSWORD';

		$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = \CUserTypeEntity::GetList([], array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if (!$rs->Fetch())
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

				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}
	}

	public function DoUninstall()
	{
		global $APPLICATION;

		$step = (int)($_REQUEST['step'] ?? 1);
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("VI_UNINSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/unstep1.php");
		}
		elseif ($step == 2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
			\CBitrixComponent::clearComponentCache("bitrix:menu");

			$APPLICATION->IncludeAdminFile(Loc::getMessage("VI_UNINSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/unstep2.php");
		}
	}

	public function UnInstallDB($arParams = [])
	{
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		if (!$arParams['savedata'])
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/".$connection->getType()."/uninstall.sql");
		}

		if (is_array($this->errors))
		{
			$arSQLErrors = $this->errors;
		}

		if (!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnAfterUserUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');

		$eventManager->unRegisterEventHandler("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		$eventManager->unRegisterEventHandler("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");

		$eventManager->unRegisterEventHandler("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestAppDelete', 'voximplant', '\Bitrix\Voximplant\Rest\Helper', 'onRestAppDelete');
		$eventManager->unRegisterEventHandler("im", "OnGetNotifySchema", "voximplant", "CVoxImplantEvent", "onGetNotifySchema");

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

		\CAgent::RemoveAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant");
		\CAgent::RemoveAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::finishStaleCalls();", "voximplant");
		\CAgent::RemoveAgent("\\Bitrix\\Voximplant\\Agent\\CallCleaner::deleteOldCalls();", "voximplant");

		$this->UnInstallUserFields($arParams);

		\Bitrix\Main\ModuleManager::unRegisterModule("voximplant");

		return true;
	}

	public function UnInstallUserFields($arParams = [])
	{
		if (!$arParams['savedata'])
		{
			$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_BACKPHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = \CUserTypeEntity::GetList([], Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new \CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}
		}

		return true;
	}
}
