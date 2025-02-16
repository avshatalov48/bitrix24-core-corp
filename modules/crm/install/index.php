<?php

use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('crm'))
{
	return;
}

class crm extends CModule
{
	var $MODULE_ID = 'crm';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = 'Y';
	var $errors = '';

	function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__ . '/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('CRM_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('CRM_INSTALL_DESCRIPTION');
	}

	public static function installUserFields($moduleId = 'all')
	{
		global $APPLICATION;
		global $USER_FIELD_MANAGER;

		$USER_FIELD_MANAGER->CleanCache();

		$errors = null;

		AddEventHandler("main", "OnUserTypeBuildList", ["CUserTypeCrm", "GetUserTypeDescription"]);
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/lib/userfield/types/elementtype.php');
		require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/crm/classes/general/crm_usertypecrm.php");

		$arMess = self::__GetMessagesForAllLang(__FILE__, ['CRM_UF_NAME', 'CRM_UF_NAME_CAL', 'CRM_UF_NAME_LF_TYPE', 'CRM_UF_NAME_LF_ID']);

		if ('all' == $moduleId)
		{
			// add CRM userfield for CTask
			$rsUserType = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'TASKS_TASK',
					'FIELD_NAME' => 'UF_CRM_TASK',
				]
			);
			if (!$rsUserType->Fetch())
			{
				$arFields = [];
				$arFields['ENTITY_ID'] = 'TASKS_TASK';
				$arFields['FIELD_NAME'] = 'UF_CRM_TASK';
				$arFields['USER_TYPE_ID'] = 'crm';
				$arFields['SETTINGS']['LEAD'] = 'Y';
				$arFields['SETTINGS']['CONTACT'] = 'Y';
				$arFields['SETTINGS']['COMPANY'] = 'Y';
				$arFields['SETTINGS']['DEAL'] = 'Y';
				$arFields['SETTINGS']['ORDER'] = 'Y';
				$arFields['MULTIPLE'] = 'Y';

				if (!empty($arMess['CRM_UF_NAME']))
				{
					$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME'];
				}

				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->Add($arFields, false);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}

			// add CRM userfield for CUser
			$rsUserType = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'USER',
					'FIELD_NAME' => 'UF_USER_CRM_ENTITY',
				]
			);
			if (!$rsUserType->Fetch())
			{
				$arFields = [];
				$arFields['ENTITY_ID'] = 'USER';
				$arFields['FIELD_NAME'] = 'UF_USER_CRM_ENTITY';
				$arFields['USER_TYPE_ID'] = 'crm';
				$arFields['SETTINGS']['LEAD'] = 'Y';
				$arFields['SETTINGS']['CONTACT'] = 'Y';
				$arFields['SETTINGS']['COMPANY'] = 'Y';
				$arFields['MULTIPLE'] = 'N';

				if (!empty($arMess['CRM_UF_NAME']))
				{
					$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME'];
				}

				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->Add($arFields, false);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}

			// add CRM userfield for CTaskTemplates
			$rsUserType = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'TASKS_TASK_TEMPLATE',
					'FIELD_NAME' => 'UF_CRM_TASK',
				]
			);
			if (!$rsUserType->Fetch())
			{
				$arFields = [];
				$arFields['ENTITY_ID'] = 'TASKS_TASK_TEMPLATE';
				$arFields['FIELD_NAME'] = 'UF_CRM_TASK';
				$arFields['USER_TYPE_ID'] = 'crm';
				$arFields['SETTINGS']['LEAD'] = 'Y';
				$arFields['SETTINGS']['CONTACT'] = 'Y';
				$arFields['SETTINGS']['COMPANY'] = 'Y';
				$arFields['SETTINGS']['DEAL'] = 'Y';
				$arFields['SETTINGS']['ORDER'] = 'Y';
				$arFields['MULTIPLE'] = 'Y';

				if (!empty($arMess['CRM_UF_NAME']))
				{
					$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME'];
					$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME'];
				}

				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->Add($arFields, false);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}

			$rsUserType = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'CALENDAR_EVENT',
					'FIELD_NAME' => 'UF_CRM_CAL_EVENT',
				]
			);
			if (!$rsUserType->Fetch())
			{
				$arFields = [];
				$arFields['ENTITY_ID'] = 'CALENDAR_EVENT';
				$arFields['FIELD_NAME'] = 'UF_CRM_CAL_EVENT';
				$arFields['USER_TYPE_ID'] = 'crm';
				$arFields['SETTINGS']['LEAD'] = 'Y';
				$arFields['SETTINGS']['CONTACT'] = 'Y';
				$arFields['SETTINGS']['COMPANY'] = 'Y';
				$arFields['SETTINGS']['DEAL'] = 'Y';
				$arFields['MULTIPLE'] = 'Y';

				if (!empty($arMess['CRM_UF_NAME_CAL']))
				{
					$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
					$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
					$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
				}

				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->Add($arFields, false);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}
		}

		if (in_array($moduleId, ['all', 'disk']) && isModuleInstalled('disk'))
		{
			$rsUserType = CUserTypeEntity::GetList(
				[],
				[
					'ENTITY_ID' => 'CRM_TIMELINE',
					'FIELD_NAME' => 'UF_CRM_COMMENT_FILES',
				]
			);
			if (!$rsUserType->Fetch())
			{
				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->Add([
					'ENTITY_ID' => 'CRM_TIMELINE',
					'FIELD_NAME' => 'UF_CRM_COMMENT_FILES',
					'USER_TYPE_ID' => 'disk_file',
					'XML_ID' => 'CRM_COMMENT_FILES',
					'MULTIPLE' => 'Y',
					'MANDATORY' => null,
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => null,
					'EDIT_IN_LIST' => null,
					'IS_SEARCHABLE' => null,
					'SETTINGS' => [
						'IBLOCK_TYPE_ID' => '0',
						'IBLOCK_ID' => '',
						'UF_TO_SAVE_ALLOW_EDIT' => '',
					],
					'EDIT_FORM_LABEL' => [
						'en' => 'Load files',
						'ru' => 'Load files',
						'de' => 'Load files',
					],
				]);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}

			$rsUserType = CUserTypeEntity::getList(
				[],
				[
					'ENTITY_ID' => 'CRM_MAIL_TEMPLATE',
					'FIELD_NAME' => 'UF_ATTACHMENT',
				]
			);
			if (!$rsUserType->fetch())
			{
				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->add([
					'ENTITY_ID' => 'CRM_MAIL_TEMPLATE',
					'FIELD_NAME' => 'UF_ATTACHMENT',
					'USER_TYPE_ID' => 'disk_file',
					'XML_ID' => '',
					'SORT' => 100,
					'MULTIPLE' => 'Y',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'N',
					'IS_SEARCHABLE' => 'N',
				]);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->getException())
					{
						$errors[] = $strEx->getString();
					}
				}
			}
		}

		if (in_array($moduleId, ['all', 'mail']) && isModuleInstalled('mail'))
		{
			$rsUserType = CUserTypeEntity::getList(
				[],
				[
					'ENTITY_ID' => 'CRM_ACTIVITY',
					'FIELD_NAME' => 'UF_MAIL_MESSAGE',
				]
			);
			if (!$rsUserType->fetch())
			{
				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->add([
					'ENTITY_ID' => 'CRM_ACTIVITY',
					'FIELD_NAME' => 'UF_MAIL_MESSAGE',
					'USER_TYPE_ID' => 'mail_message',
					'XML_ID' => '',
					'SORT' => 100,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'N',
					'IS_SEARCHABLE' => 'N',
				]);
				if (!$intID)
				{
					if ($strEx = $APPLICATION->getException())
					{
						$errors[] = $strEx->getString();
					}
				}
			}
		}

		return $errors;
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		RegisterModule('crm');
		\Bitrix\Main\Loader::includeModule('crm');

		if (!$DB->TableExists('b_crm_lead'))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/db/' . $connection->getType() . '/install.sql');

			COption::SetOptionString('crm', '~crm_install_time', time());

			CCrmStatus::InstallDefault('STATUS');
			CCrmStatus::InstallDefault('SOURCE');
			CCrmStatus::InstallDefault('CONTACT_TYPE');
			CCrmStatus::InstallDefault('COMPANY_TYPE');
			CCrmStatus::InstallDefault('EMPLOYEES');
			CCrmStatus::InstallDefault('CALL_LIST');

			// Create default industry  list
			$CCrmStatus = new CCrmStatus('INDUSTRY');
			$arAdd = [
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_IT'),
					'STATUS_ID' => 'IT',
					'SORT' => 10,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_TELECOM'),
					'STATUS_ID' => 'TELECOM',
					'SORT' => 20,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_MANUFACTURING'),
					'STATUS_ID' => 'MANUFACTURING',
					'SORT' => 30,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_BANKING'),
					'STATUS_ID' => 'BANKING',
					'SORT' => 40,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_CONSULTING'),
					'STATUS_ID' => 'CONSULTING',
					'SORT' => 50,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_FINANCE'),
					'STATUS_ID' => 'FINANCE',
					'SORT' => 60,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_GOVERNMENT'),
					'STATUS_ID' => 'GOVERNMENT',
					'SORT' => 70,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_DELIVERY'),
					'STATUS_ID' => 'DELIVERY',
					'SORT' => 80,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_ENTERTAINMENT'),
					'STATUS_ID' => 'ENTERTAINMENT',
					'SORT' => 90,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_NOTPROFIT'),
					'STATUS_ID' => 'NOTPROFIT',
					'SORT' => 100,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_INDUSTRY_OTHER'),
					'STATUS_ID' => 'OTHER',
					'SORT' => 110,
					'SYSTEM' => 'Y',
				],
			];
			foreach ($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default deal type list
			$CCrmStatus = new CCrmStatus('DEAL_TYPE');
			$arAdd = [
				[
					'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SALE'),
					'STATUS_ID' => 'SALE',
					'SORT' => 10,
					'SYSTEM' => 'Y',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_TYPE_COMPLEX'),
					'STATUS_ID' => 'COMPLEX',
					'SORT' => 20,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_TYPE_GOODS'),
					'STATUS_ID' => 'GOODS',
					'SORT' => 30,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SERVICES'),
					'STATUS_ID' => 'SERVICES',
					'SORT' => 40,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_TYPE_SERVICE'),
					'STATUS_ID' => 'SERVICE',
					'SORT' => 50,
					'SYSTEM' => 'N',
				],
			];
			foreach ($arAdd as $ar)
				$CCrmStatus->Add($ar);

			CCrmStatus::InstallDefault('DEAL_STAGE');

			// Create default deal state list
			$CCrmStatus = new CCrmStatus('DEAL_STATE');
			$arAdd = [
				[
					'NAME' => Loc::getMessage('CRM_DEAL_STATE_PLANNED'),
					'STATUS_ID' => 'PLANNED',
					'SORT' => 10,
					'SYSTEM' => 'N',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_STATE_PROCESS'),
					'STATUS_ID' => 'PROCESS',
					'SORT' => 20,
					'SYSTEM' => 'Y',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_STATE_COMPLETE'),
					'STATUS_ID' => 'COMPLETE',
					'SORT' => 30,
					'SYSTEM' => 'Y',
				],
				[
					'NAME' => Loc::getMessage('CRM_DEAL_STATE_CANCELED'),
					'STATUS_ID' => 'CANCELED',
					'SORT' => 40,
					'SYSTEM' => 'Y',
				],
			];
			foreach ($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default event type
			$CCrmStatus = new CCrmStatus('EVENT_TYPE');
			$arAdd = [
				[
					'NAME' => Loc::getMessage('CRM_EVENT_TYPE_INFO'),
					'STATUS_ID' => 'INFO',
					'SORT' => 10,
					'SYSTEM' => 'Y',
				],
				[
					'NAME' => Loc::getMessage('CRM_EVENT_TYPE_PHONE'),
					'STATUS_ID' => 'PHONE',
					'SORT' => 20,
					'SYSTEM' => 'Y',
				],
				[
					'NAME' => Loc::getMessage('CRM_EVENT_TYPE_MESSAGE'),
					'STATUS_ID' => 'MESSAGE',
					'SORT' => 30,
					'SYSTEM' => 'Y',
				],
			];
			foreach ($arAdd as $ar)
				$CCrmStatus->Add($ar);

			CCrmStatus::InstallDefault('QUOTE_STATUS');
			CCrmStatus::InstallDefault('INVOICE_STATUS');

			\Bitrix\Crm\Honorific::installDefault();

			$CCrmRole = new CCrmRole();
			$roles = RolePreset::GetDefaultRolesPreset();

			$adminRoleID = null;
			foreach ($roles as $presetKey => $role)
			{
				if ($presetKey === RolePreset::ADMIN)
				{
					$adminRoleID = $CCrmRole->Add($roles[RolePreset::ADMIN]);
					continue;
				}

				$CCrmRole->Add($role);
			}

			$dbGroup = CGroup::GetList('', '', ['STRING_ID' => 'MARKETING_AND_SALES']);
			$arGroup = $dbGroup->Fetch();
			if ($adminRoleID && $arGroup)
			{
				$CCrmRole->SetRelation(
					['G' . $arGroup['ID'] => [$adminRoleID]]
				);
			}

			(new \Bitrix\Crm\Copilot\CallAssessment\FillPreliminaryCallAssessments())->execute();
		}

		\Bitrix\Crm\Model\ItemCategoryTable::installBundledCategoriesIfNotExists();

		$this->InstallUserFields();

		//region BUSINESS TYPES
		$arResult = $DB->Query("SELECT COUNT(*) AS QTY FROM b_crm_biz_type")->Fetch();
		$qty = (is_array($arResult) && isset($arResult["QTY"])) ? intval($arResult["QTY"]) : 0;
		if ($qty === 0)
		{
			$allLangIDs = [];
			$langEntity = new CLanguage();
			$dbLangs = $langEntity->GetList();
			while ($lang = $dbLangs->Fetch())
			{
				if (isset($lang['LID']))
				{
					$allLangIDs[] = $lang['LID'];
				}
			}

			foreach ($allLangIDs as $langID)
			{
				$langFile = $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/crm/lang/' . $langID . "/lib/businesstype.php";
				if (!file_exists($langFile))
				{
					continue;
				}

				//include($langFile);
				$messages = __IncludeLang($langFile, true);
				$s = isset($messages["CRM_BIZ_TYPE_DEFAULT"]) ? trim($messages["CRM_BIZ_TYPE_DEFAULT"]) : "";
				if ($s === ' ' || $s === '-')
				{
					continue;
				}

				foreach (explode('|', $s) as $slug)
				{
					$ary = explode(';', $slug);
					if (count($ary) < 2)
					{
						continue;
					}

					$code = $DB->ForSql($ary[0]);
					if (is_array($DB->Query("SELECT 'X' from b_crm_biz_type where CODE = '{$code}'")->Fetch()))
					{
						continue;
					}

					$name = $DB->ForSql($ary[1]);
					$lang = isset($ary[2]) ? $DB->ForSql($ary[2]) : '';
					$DB->Query("INSERT INTO b_crm_biz_type(CODE, NAME, LANG) VALUES('{$code}', '{$name}', '{$lang}')");
				}
			}
		}
		//endregion

		//region FULL TEXT INDEXES
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_lead", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_deal", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_quote", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_contact", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_company", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_act", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_invoice", serialize(["SEARCH_CONTENT" => true]));
		\Bitrix\Main\Config\Option::set("main", "~ft_b_crm_timeline_search", serialize(["SEARCH_CONTENT" => true]));
		//endregion

		\Bitrix\Main\Config\Option::set('crm', 'enable_slider', 'Y');
		\Bitrix\Main\Config\Option::set('crm', 'enable_order_deal_create', 'Y');

		\Bitrix\Crm\Settings\Crm::setLiveFeedRecordsGenerationEnabled(false);
		\Bitrix\Crm\Settings\LiveFeedSettings::getCurrent()->enableLiveFeedMerge(false);
		\Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper::setAvailable(false);

		\Bitrix\Crm\Settings\ActivitySettings::setValue(
			\Bitrix\Crm\Settings\ActivitySettings::ENABLE_CALENDAR_EVENTS_SETTINGS,
			false,
		);

		(new \Bitrix\Crm\Copilot\CallAssessment\FillPreliminaryCallAssessments())->execute();

		\Bitrix\Crm\EntityRequisite::installDefaultPresets();

		// Adjust default address zone
		\Bitrix\Crm\EntityAddress::getZoneId();

		$this->installEventHandlers();
		$this->installAgents();

		if (COption::GetOptionString('crm', '~CRM_CONVERT_COMPANY_UF_ADDRESSES', 'N') === 'Y')
		{
			$progressData = ['OPTIONS' => ['ALLOWED_ENTITY_TYPES' => [1, 2, 4]]];
			COption::SetOptionString('crm', '~CRM_CONVERT_COMPANY_UF_ADDRESSES_PROGRESS', serialize($progressData));
			unset($progressData);
		}
		if (COption::GetOptionString('crm', '~CRM_CONVERT_CONTACT_UF_ADDRESSES', 'N') === 'Y')
		{
			$progressData = ['OPTIONS' => ['ALLOWED_ENTITY_TYPES' => [1, 2, 3]]];
			COption::SetOptionString('crm', '~CRM_CONVERT_CONTACT_UF_ADDRESSES_PROGRESS', serialize($progressData));
			unset($progressData);
		}
		\Bitrix\Crm\Attribute\Entity\FieldAttributeTable::add([
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_SCOPE' => '',
			'TYPE_ID' => \Bitrix\Crm\Attribute\FieldAttributeType::REQUIRED,
			'FIELD_NAME' => 'NAME',
			'CREATED_TIME' => new \Bitrix\Main\Type\DateTime(),
			'START_PHASE' => '',
			'FINISH_PHASE' => '',
			'PHASE_GROUP_TYPE_ID' => \Bitrix\Crm\Attribute\FieldAttributePhaseGroupType::ALL,
			'IS_CUSTOM_FIELD' => false,
		]);
		\Bitrix\Crm\Attribute\Entity\FieldAttributeTable::add([
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_SCOPE' => '',
			'TYPE_ID' => \Bitrix\Crm\Attribute\FieldAttributeType::REQUIRED,
			'FIELD_NAME' => 'TITLE',
			'CREATED_TIME' => new \Bitrix\Main\Type\DateTime(),
			'START_PHASE' => '',
			'FINISH_PHASE' => '',
			'PHASE_GROUP_TYPE_ID' => \Bitrix\Crm\Attribute\FieldAttributePhaseGroupType::ALL,
			'IS_CUSTOM_FIELD' => false,
		]);

		$existedSequenceRecord = $DB->Query("select SEQUENCE_NAME from b_crm_sequences where SEQUENCE_NAME='dynamic_type_id'")->Fetch();
		if (!$existedSequenceRecord)
		{
			$DB->Query("insert into b_crm_sequences (SEQUENCE_NAME, SEQUENCE_VALUE) VALUES ('dynamic_type_id', 1030);");
		}

		if (\Bitrix\Main\Loader::includeModule('intranet'))
		{
			CIntranetUtils::clearMenuCache();
		}

		if (is_array($this->errors))
		{
			$GLOBALS['errors'] = $this->errors;
			$APPLICATION->ThrowException(implode(' ', $this->errors));

			return false;
		}

		return true;
	}

	function UnInstallDB($arParams = [])
	{
		global $DB, $APPLICATION, $CACHE_MANAGER, $stackCacheManager, $USER_FIELD_MANAGER;
		$connection = \Bitrix\Main\Application::getConnection();
		$this->errors = false;

		// register types factories before deleting events.
		\Bitrix\Main\UserField\Internal\Registry::getInstance();

		$this->uninstallEventHandlers();

		if (!array_key_exists('savedata', $arParams) || $arParams['savedata'] != 'Y')
		{
			// delete extra fields for all entities
			$arEntityIds = CCrmFields::GetEntityTypes();
			foreach ($arEntityIds as $entityId => $ar)
			{
				$CCrmFields = new CCrmFields($USER_FIELD_MANAGER, $entityId);
				$arFields = $CCrmFields->GetFields();
				foreach ($arFields as $arField)
				{
					$CCrmFields->DeleteField($arField['ID']);
				}
			}

			$userFieldEntity = new CUserTypeEntity;
			$userField = CUserTypeEntity::getList(
				[],
				[
					'ENTITY_ID' => 'CRM_ACTIVITY',
					'FIELD_NAME' => 'UF_MAIL_MESSAGE',
				]
			)->fetch();
			if ($userField)
			{
				$userFieldEntity->delete($userField['ID']);
			}

			$typeFactory = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.type.factory');
			$types = \Bitrix\Crm\Model\Dynamic\TypeTable::getList()->fetchCollection();
			$tableNamesToDelete = [];
			foreach ($types as $type)
			{
				$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($type->getEntityTypeId());
				if ($factory)
				{
					$connection = \Bitrix\Main\Application::getConnection();
					foreach($factory->getUserFields() as $userField)
					{
						try
						{
							$userFieldEntity->delete($userField['ID']);
						}
						catch(\Bitrix\Main\DB\SqlQueryException)
						{
							// do nothing
						}
					}
				}

				$tableNamesToDelete[] = $type->getTableName();
				$tableNamesToDelete[] = $typeFactory->getItemIndexDataClass($type)::getTableName();
				$tableNamesToDelete[] = $typeFactory->getItemFieldsContextDataClass($type)::getTableName();
				$tableNamesToDelete[] = \Bitrix\Crm\Security\AccessAttribute\Manager::getEntity(CCrmOwnerType::ResolveName($type->getEntityTypeId()))->getDBTableName();
			}
			foreach ($tableNamesToDelete as $tableName)
			{
				if ($connection->isTableExists($tableName))
				{
					$connection->dropTable($tableName);
				}
			}

			\Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache();

			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/db/'.$connection->getType().'/uninstall.sql');

			if (CModule::IncludeModule('socialnetwork'))
			{
				$dbRes = CSocNetLog::GetList(
					[],
					["ENTITY_TYPE" => CCrmLiveFeedEntity::GetAll()],
					false,
					false,
					["ID"]
				);

				if ($dbRes)
				{
					while ($arRes = $dbRes->Fetch())
					{
						CSocNetLog::Delete($arRes["ID"]);
					}
				}
			}

			$DB->Query("DELETE FROM b_user_counter WHERE CODE LIKE 'crm_%'");
			$CACHE_MANAGER->CleanDir("user_counter");

			COption::RemoveOption('crm');
		}

		$stackCacheManager->Clear('b_crm_status');
		$stackCacheManager->Clear('b_crm_perms');

		if (CModule::IncludeModule('search'))
		{
			CSearch::DeleteIndex('crm');
		}

		$this->uninstallAgents();

		UnRegisterModule('crm');

		if (is_array($this->errors))
		{
			$APPLICATION->ThrowException(implode('<br />', $this->errors));

			return false;
		}

		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$res = $DB->query("SELECT COUNT(*) CNT FROM b_event_type WHERE EVENT_NAME IN ('CRM_EMAIL_CONFIRM')")->fetch();
		if ($res['CNT'] > 0)
		{
			return true;
		}

		$langs = CLanguage::getList('', '');
		while ($lang = $langs->fetch())
		{
			$lid = $lang['LID'];
			includeModuleLangFile(__FILE__, $lid);

			$eventTypes = [
				[
					'LID' => $lid,
					'EVENT_NAME' => 'CRM_EMAIL_CONFIRM',
					'NAME' => Loc::getMessage('CRM_EMAIL_CONFIRM_TYPE_NAME'),
					'DESCRIPTION' => Loc::getMessage('CRM_EMAIL_CONFIRM_TYPE_DESC'),
					'SORT' => 1,
				],
			];

			$type = new CEventType;
			foreach ($eventTypes as $item)
				$type->add($item);

			$sitesIds = [];
			$sites = CSite::getList('', '', ['LANGUAGE_ID' => $lid]);
			while ($item = $sites->fetch())
				$sitesIds[] = $item['LID'];

			if (count($sitesIds) > 0)
			{
				$eventMessages = [
					[
						'ACTIVE' => 'Y',
						'EVENT_NAME' => 'CRM_EMAIL_CONFIRM',
						'LID' => $sitesIds,
						'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
						'EMAIL_TO' => '#EMAIL#',
						'SUBJECT' => Loc::getMessage('CRM_EMAIL_CONFIRM_EVENT_NAME'),
						'MESSAGE' => Loc::getMessage('CRM_EMAIL_CONFIRM_EVENT_DESC'),
						'BODY_TYPE' => 'html',
						'SITE_TEMPLATE_ID' => 'mail_join',
					],
				];

				$message = new CEventMessage;
				foreach ($eventMessages as $item)
					$message->add($item);
			}
		}

		return true;
	}

	function UnInstallEvents()
	{
		global $DB;

		$DB->query("DELETE FROM b_event_type WHERE EVENT_NAME in ('CRM_EMAIL_CONFIRM')");
		$DB->query("DELETE FROM b_event_message WHERE EVENT_NAME in ('CRM_EMAIL_CONFIRM')");

		return true;
	}

	function InstallFiles($arParams = [])
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/gadgets', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/gadgets', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/tools/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/activities/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/activities', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/themes/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/services/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/services', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images', true, true);

		//[COPY CUSTOMIZED PAY SYSTEM ACTION FILES]-->
		$customPaySystemPath = IsModuleInstalled('sale') ? COption::GetOptionString('sale', 'path2user_ps_files', '') : '';
		if ($customPaySystemPath === '')
		{
			$customPaySystemPath = BX_ROOT . '/php_interface/include/sale_payment/';
		}

		$sort = 'sort';
		$order = 'asc';
		$langEntity = new CLanguage();
		$dbLangs = $langEntity->GetList($sort, $order);
		while ($lang = $dbLangs->Fetch())
		{
			$langSrcPaySystemPath = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/crm/install/integration/sale.paysystems/' . $lang['LID'] . '/';
			if (!file_exists($langSrcPaySystemPath))
			{
				continue;
			}

			CopyDirFiles(
				$langSrcPaySystemPath,
				$_SERVER['DOCUMENT_ROOT'] . $customPaySystemPath,
				true,
				true
			);
		}
		//<--[COPY CUSTOMIZED PAY SYSTEM ACTION FILES]BX_ROOT

		global $APPLICATION;

		//HACK: bizproc crutch to enable user read only access to service files
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_activity_settings.php', ['2' => 'R']);
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_selector.php', ['2' => 'R']);
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_wf_settings.php', ['2' => 'R']);

		\Bitrix\Main\Loader::includeModule('crm');
		\Bitrix\Crm\Preview\Route::setCrmRoutes();

		CUrlRewriter::Add(
			[
				"CONDITION" => "#^/pub/pay/([\\w\\W]+)/([0-9a-zA-Z]+)/([^/]*)#",
				"RULE" => "account_number=$1&hash=$2",
				"PATH" => "/pub/payment.php",
			]
		);

		CUrlRewriter::Add(
			[
				"CONDITION" => "#^/stssync/contacts_crm/#",
				"RULE" => "",
				"ID" => "bitrix:stssync.server",
				"PATH" => "/bitrix/services/stssync/contacts_crm/index.php",
			]
		);

		return true;
	}

	function __AddMenuItem($menuFile, $menuItem, $siteID, $pos = -1)
	{
		if (CModule::IncludeModule('fileman'))
		{
			$arResult = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"] . $menuFile);
			$arMenuItems = $arResult["aMenuLinks"];
			$menuTemplate = $arResult["sMenuTemplate"];

			$bFound = false;
			foreach ($arMenuItems as $item)
				if ($item[1] == $menuItem[1])
				{
					$bFound = true;
				}

			if (!$bFound)
			{
				if ($pos < 0 || $pos >= count($arMenuItems))
				{
					$arMenuItems[] = $menuItem;
				}
				else
				{
					for ($i = count($arMenuItems); $i > $pos; $i--)
						$arMenuItems[$i] = $arMenuItems[$i - 1];

					$arMenuItems[$pos] = $menuItem;
				}

				CFileMan::SaveMenu([$siteID, $menuFile], $arMenuItems, $menuTemplate);
			}
		}
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js');
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/themes', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes');
		DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/gadgets', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/gadgets');

		return true;
	}

	private function installEventHandlers()
	{
		RegisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterList');
		RegisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterListImap');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrm', 'GetUserTypeDescription');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrmStatus', 'GetUserTypeDescription');
		RegisterModuleDependences('main', 'OnUserDelete', 'crm', '\Bitrix\Crm\Kanban\SortTable', 'clearUser');
		RegisterModuleDependences('search', 'OnReindex', 'crm', 'CCrmSearch', 'OnSearchReindex');
		RegisterModuleDependences('search', 'OnSearchCheckPermissions', 'crm', 'CCrmSearch', 'OnSearchCheckPermissions');
		RegisterModuleDependences('report', 'OnReportAdd', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('report', 'OnReportUpdate', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('report', 'OnReportDelete', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('iblock', 'OnIBlockDelete', 'crm', 'CAllCrmCatalog', 'OnIBlockDelete');
		RegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'crm', '\Bitrix\Crm\Order\Import\Instagram', 'onAfterIblockElementDelete');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmExternalSaleImport', 'OnFillSocNetLogEvents');

		RegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'crm', 'CAllCrmActivity', 'OnBeforeTaskAdd');
		RegisterModuleDependences('tasks', 'OnTaskAdd', 'crm', 'CAllCrmActivity', 'OnTaskAdd');
		RegisterModuleDependences('tasks', 'OnTaskUpdate', 'crm', 'CAllCrmActivity', 'OnTaskUpdate');
		RegisterModuleDependences('tasks', 'OnTaskDelete', 'crm', 'CAllCrmActivity', 'OnTaskDelete');

		RegisterModuleDependences('webdav', 'OnFileDelete', 'crm', 'CCrmWebDavHelper', 'OnWebDavFileDelete');

		RegisterModuleDependences('subscribe', 'BeforePostingSendMail', 'crm', 'CCrmEMail', 'BeforeSendMail');
		RegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'crm', 'CAllCrmActivity', 'OnCalendarEventEdit');
		RegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'crm', 'CAllCrmActivity', 'OnCalendarEventDelete');

		RegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmRestService', 'onRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmInvoiceRestService', 'OnRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'crm', '\Bitrix\Crm\SiteButton\Rest', 'onRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'crm', '\Bitrix\Crm\WebForm\Rest', 'onRestServiceBuildDescription');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'crm', 'CCrmLiveFeed', 'OnFillSocNetAllowedSubscribeEntityTypes');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogEvents');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogFields', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogFields');
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogFilter', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogFilter');
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogOrder', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogOrder');
		RegisterModuleDependences('socialnetwork', 'OnSocNetLogFormatDestination', 'crm', 'CCrmLiveFeed', 'OnSocNetLogFormatDestination');
		RegisterModuleDependences("socialnetwork", "OnAfterSocNetLogFormatDestination", "crm", "CCrmLiveFeed", "OnAfterSocNetLogFormatDestination");
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogPerms', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogPerms');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogRightsAdd', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogRightsAdd');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogCommentCounterIncrement', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogCommentCounterIncrement');
		RegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogEntryCommentAdd', 'crm', 'CCrmLiveFeed', 'OnAfterSocNetLogEntryCommentAdd');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogEntryGetRights', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogEntryGetRights');
		RegisterModuleDependences("socialnetwork", "OnSendMentionGetEntityFields", "crm", "CCrmLiveFeed", "OnSendMentionGetEntityFields");
		RegisterModuleDependences("socialnetwork", "OnSonetLogCounterClear", "crm", "CCrmLiveFeedComponent", "OnSonetLogCounterClear");
		RegisterModuleDependences("socialnetwork", "OnAfterSocNetLogCommentAdd", "crm", "CCrmLiveFeed", "OnAfterSocNetLogCommentAdd");
		RegisterModuleDependences('main', 'OnAddRatingVote', 'crm', 'CCrmLiveFeed', 'OnAddRatingVote');
		RegisterModuleDependences('main', 'OnGetRatingContentOwner', 'crm', 'CCrmLiveFeed', 'OnGetRatingContentOwner');
		RegisterModuleDependences('im', 'OnGetMessageRatingVote', 'crm', 'CCrmLiveFeed', 'OnGetMessageRatingVote');

		RegisterModuleDependences('forum', 'OnAfterCommentAdd', 'crm', 'CCrmLiveFeed', 'onAfterCommentAdd');
		RegisterModuleDependences('imconnector', 'OnAddStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');
		RegisterModuleDependences('imconnector', 'OnUpdateStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');
		RegisterModuleDependences('imconnector', 'OnDeleteStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');

		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Crm\Integration\Application', "OnApplicationsBuildList", 100, "modules/crm/lib/integration/application.php");
		RegisterModuleDependences('disk', 'onAfterDeleteFile', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'OnDiskFileDelete');

		RegisterModuleDependences("im", "OnGetNotifySchema", "crm", "CCrmNotifierSchemeType", "PrepareNotificationSchemes");

		RegisterModuleDependences("main", "OnAfterRegisterModule", "main", "crm", "InstallUserFields", 100, "/modules/crm/install/index.php"); // check crm UF

		RegisterModuleDependences('disk', 'onBuildAdditionalConnectorList', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'onBuildConnectorList');

		RegisterModuleDependences('intranet', 'OnTransferEMailUser', 'intranet', '\Bitrix\Crm\Integration\Intranet\InviteDialog', 'onTransferEMailUser');

		RegisterModuleDependences('main', 'OnMailEventMailChangeStatus', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onMailEventMailChangeStatus');
		RegisterModuleDependences('main', 'OnMailEventMailChangeStatus', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onMailEventSendNotification');

		RegisterModuleDependences(
				'main',
				'OnBeforeUserTypeAdd',
				'crm',
				'\Bitrix\Crm\Service\EventHandler',
				'OnBeforeUserTypeAdd'
		);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('main', 'OnAfterSetOption_~crm_webform_max_activated', 'crm', '\Bitrix\Crm\WebForm\Form', 'onAfterSetOptionCrmWebFormMaxActivated');

		$eventManager->registerEventHandler('mail', 'OnMessageObsolete', 'crm', 'CCrmEMail', 'OnImapEmailMessageObsolete');
		$eventManager->registerEventHandler('crm', 'OnActivityModified', 'crm', 'CCrmEMail', 'OnActivityModified');
		$eventManager->registerEventHandlerCompatible('crm', 'OnActivityDelete', 'crm', 'CCrmEMail', 'OnActivityDelete');
		$eventManager->registerEventHandlerCompatible('main', 'OnMailEventMailRead', 'crm', 'CCrmEMail', 'OnOutgoingMessageRead');
		$eventManager->registerEventHandlerCompatible('main', 'OnMailEventMailClick', 'crm', 'CCrmEMail', 'OnOutgoingMessageClick');
		$eventManager->registerEventHandler('sale', 'OnSalePsServiceProcessRequestBeforePaid', 'crm', '\Bitrix\Crm\InvoiceTable', 'redeterminePaySystem');
		$eventManager->registerEventHandler('sale', 'OnSaleGetHandlerDescription', 'crm', '\CCrmPaySystem', 'getHandlerDescriptionEx');

		$eventManager->registerEventHandler('iblock', 'OnIBlockPropertyBuildList', 'crm', '\Bitrix\Crm\Integration\IBlockElementProperty', 'getUserTypeDescription');
		$eventManager->registerEventHandlerCompatible(
			'iblock',
			'OnBeforeIBlockElementDelete',
			'crm',
			'CCrmProduct',
			'HandlerOnBeforeIBlockElementDelete'
		);
		$eventManager->registerEventHandlerCompatible(
			'iblock',
			'OnAfterIBlockElementDelete',
			'crm',
			'CCrmProduct',
			'HandlerOnAfterIBlockElementDelete'
		);
		$eventManager->registerEventHandlerCompatible(
			'iblock',
			'OnBeforeIBlockElementAdd',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleBeforeIblockElementAdd'
		);
		$eventManager->registerEventHandlerCompatible(
			'iblock',
			'OnAfterIBlockElementAdd',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleAfterIblockElementAdd'
		);
		$eventManager->registerEventHandlerCompatible(
			'iblock',
			'OnAfterIBlockElementDelete',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleAfterIblockElementDelete'
		);

		$eventManager->registerEventHandler('catalog', 'Bitrix\Catalog\Product\Entity::OnAfterUpdate', 'crm', '\CCrmProduct', 'handlerAfterProductUpdate');

		$eventManager->registerEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterAdd', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetAdd');
		$eventManager->registerEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterUpdate', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetUpdate');
		$eventManager->registerEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterDelete', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetDelete');

		$eventManager->registerEventHandler('socialnetwork', 'onUserProfileRedirectGetUrl', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onUserProfileRedirectGetUrl');
		$eventManager->registerEventHandler('main', 'OnUserConsentProviderList', 'crm', '\Bitrix\Crm\Integration\UserConsent', 'onProviderList');
		$eventManager->registerEventHandler('main', 'OnUserConsentDataProviderList', 'crm', '\Bitrix\Crm\Integration\UserConsent', 'onDataProviderList');

		$eventManager->registerEventHandler('main', 'OnAfterUserTypeAdd', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onAdd');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onDelete');

		$eventManager->registerEventHandler('documentgenerator', 'onGetDataProviderList', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'getDataProviders');
		$eventManager->registerEventHandler('documentgenerator', 'onCreateDocument', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onCreateDocument');
		$eventManager->registerEventHandler('documentgenerator', 'onDocumentTransformationComplete', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onDocumentTransformationComplete');
		$eventManager->registerEventHandler('documentgenerator', 'onUpdateDocument', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onUpdateDocument');
		$eventManager->registerEventHandler('documentgenerator', '\Bitrix\DocumentGenerator\Model\Document::OnBeforeDelete', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onDeleteDocument');
		$eventManager->registerEventHandler('documentgenerator', 'onPublicView', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onPublicView');

		$eventManager->registerEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteUserQuotesNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->registerEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteIdNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->registerEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\InvoiceIdNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->registerEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\InvoiceUserInvoicesNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->registerEventHandler('main', '\Bitrix\Main\Numerator\Model\Numerator::OnAfterAdd', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteNumberCompatibilityManager', 'updateQuoteNumberType');
		$eventManager->registerEventHandler('main', '\Bitrix\Main\Numerator\Model\Numerator::OnAfterUpdate', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteNumberCompatibilityManager', 'updateQuoteNumberType');

		$eventManager->registerEventHandler('main', 'OnAfterUserTypeAdd', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldAdd');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeDelete', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldDelete');
		$eventManager->registerEventHandler('main', 'onAfterSetEnumValues', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldSetEnumValues');

		$eventManager->registerEventHandler('sale', 'OnInitRegistryList', 'crm', '\Bitrix\Crm\Order\Order', 'OnInitRegistryList');
		$eventManager->registerEventHandler('sale', 'OnInitRegistryList', 'crm', '\Bitrix\Crm\Invoice\Invoice', 'OnInitRegistryList');

		$eventManager->registerEventHandler('main', 'onAfterSetEnumValues', 'crm', '\Bitrix\Crm\Order\Matcher\FieldSynchronizer', 'onAfterSetEnumValues');
		$eventManager->registerEventHandler('main', 'OnUserLoginExternal', 'crm', '\Bitrix\Crm\Order\Buyer', 'onUserLoginExternalHandler');
		$eventManager->registerEventHandler('main', 'OnBeforeUserAdd', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserAddHandler');
		$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserUpdateHandler');
		$eventManager->registerEventHandler('main', 'OnBeforeUserSendPassword', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserSendPasswordHandler');
		$eventManager->registerEventHandler('main', 'OnBeforeUserChangePassword', 'crm', '\Bitrix\Crm\Order\Buyer', 'OnBeforeUserChangePasswordHandler');
		$eventManager->registerEventHandler('main', 'OnBeforeSendUserInfo', 'crm', '\Bitrix\Crm\Order\Buyer', 'OnBeforeSendUserInfoHandler');
		$eventManager->registerEventHandler('sale', 'OnModuleUnInstall', 'crm', '', 'CrmOnModuleUnInstallSale');

		//analytics, visualconstructor events
		$eventManager->registerEventHandler('report', 'onReportCategoryCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onReportCategoriesCollect');
		$eventManager->registerEventHandler('report', 'onReportsCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onReportHandlerCollect');
		$eventManager->registerEventHandler('report', 'onReportViewCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onViewsCollect');
		$eventManager->registerEventHandler('report', 'onDefaultBoardsCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onDefaultBoardsCollect');
		$eventManager->registerEventHandler('report', 'onAnalyticPageCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onAnalyticPageCollect');
		$eventManager->registerEventHandler('report', 'onAnalyticPageBatchCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');

		$eventManager->registerEventHandler('main', 'onAfterSetEnumValues', 'crm', '\Bitrix\Crm\Synchronization\UserFieldEnumerationSynchronizer', 'onSetEnumerationValues');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', '\Bitrix\Crm\Synchronization\UserFieldLabelSynchronizer', 'onUserFieldUpdate');

		$eventManager->registerEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', '\Bitrix\Crm\Attribute\FieldAttributeManager', 'onUserFieldUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\Attribute\FieldAttributeManager', 'onUserFieldDelete');

		$eventManager->registerEventHandler('im', 'OnChatUserAddEntityTypeCrm', 'crm', '\Bitrix\Crm\Integration\Im\Chat', 'onAddChatUser');
		$eventManager->registerEventHandler('im', 'OnAfterMessagesAdd', 'crm', '\Bitrix\Crm\Integration\Im\Chat', 'OnAfterMessagesAdd');
		$eventManager->registerEventHandler('im', 'OnAfterChatRead', 'crm', '\Bitrix\Crm\Integration\Im\Chat', 'OnAfterChatRead');

		$eventManager->registerEventHandler('main', 'OnUISelectorGetProviderByEntityType', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorGetProviderByEntityType');
		$eventManager->registerEventHandler('main', 'OnUISelectorBeforeSave', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorBeforeSave');
		$eventManager->registerEventHandler('main', 'OnUISelectorFillLastDestination', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorFillLastDestination');

		$eventManager->registerEventHandler('ml', 'onModelStateChange', 'crm', '\Bitrix\Crm\Ml\Scoring', 'onMlModelStateChange');
		$eventManager->registerEventHandler('location', 'onCurrentFormatCodeChanged', 'crm', '\Bitrix\Crm\Integration\Location\Format', 'onCurrentFormatCodeChanged');
		$eventManager->registerEventHandler('location', 'onInitialFormatCodeSet', 'crm', '\Bitrix\Crm\Integration\Location\Format', 'onInitialFormatCodeSet');

		$eventManager->registerEventHandler(
			'iblock',
			'onGetUrlBuilders',
			'crm',
			'\Bitrix\Crm\Product\Url\Registry',
			'getBuilderList'
		);

		$eventManager->registerEventHandler('landing', '\Bitrix\Landing\Internals\Landing::OnBeforeDelete', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeLandingDelete');
		$eventManager->registerEventHandler('landing', 'onBeforeLandingRecycle', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeLandingRecycle');
		$eventManager->registerEventHandler('landing', 'onBeforeSiteRecycle', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeSiteRecycle');

		$eventManager->registerEventHandler(
			'pull',
			'onGetDependentModule',
			'crm',
			'\Bitrix\Crm\Integration\PullManager',
			'onGetDependentModule'
		);

		$eventManager->registerEventHandler('main', 'onAfterSetEnumValues', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onAfterSetEnumValues');
		$eventManager->registerEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onAfterUserTypeDelete');

		$eventManager->registerEventHandler('socialnetwork', 'onLogProviderGetContentId', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onLogProviderGetContentId');
		$eventManager->registerEventHandler('socialnetwork', 'onLogProviderGetProvider', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onLogProviderGetProvider');
		$eventManager->registerEventHandler('socialnetwork', 'onCommentAuxGetPostTypeList', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxGetPostTypeList');
		$eventManager->registerEventHandler('socialnetwork', 'onCommentAuxGetCommentTypeList', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxGetCommentTypeList');
		$eventManager->registerEventHandler('socialnetwork', 'onCommentAuxInitJs', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxInitJs');
		$eventManager->registerEventHandler('socialnetwork', 'onLogIndexGetContent', 'crm', '\Bitrix\Crm\Integration\Socialnetwork\Log', 'onIndexGetContent');

		$eventManager->registerEventHandler('voximplant', 'onCallRegisteredInCrm', 'crm', '\Bitrix\Crm\Integration\VoxImplant\EventHandler', 'onCallRegisteredInCrm');
		$eventManager->registerEventHandler('voximplant', 'onCallEnd', 'crm', '\Bitrix\Crm\Integration\VoxImplant\EventHandler', 'onCallEnd');
		$eventManager->registerEventHandler('recyclebin', 'OnModuleSurvey', 'crm', '\Bitrix\Crm\Integration\Recyclebin\RecyclingManager', 'OnModuleSurvey');
		$eventManager->registerEventHandler('recyclebin', 'onAdditionalDataRequest', 'crm', '\Bitrix\Crm\Integration\Recyclebin\RecyclingManager', 'onAdditionalDataRequest');

		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationImport', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onImport');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationExport', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onExport');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationClear', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onClear');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationEntity', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'getEntityList');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationGetManifest', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Manifest', 'getList');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationFinish', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\ConfigChecker', 'onFinish');
		$eventManager->registerEventHandler('rest', 'onRestAppDelete', 'crm', '\Bitrix\Crm\Integration\Rest\EventHandler', 'onRestAppDelete');

		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterAdd', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormAdd');
		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterUpdate', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormUpdate');
		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterDelete', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormDelete');

		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterAdd', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityAdd');
		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterUpdate', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityUpdate');
		$eventManager->registerEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterDelete', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityDelete');

		$eventManager->registerEventHandler('sale', 'OnCheckCollateDocuments', 'crm', '\Bitrix\Crm\Order\EventsHandler\Check', 'OnCheckCollateDocuments');
		$eventManager->registerEventHandler('sale', 'OnBeforeSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Order\EventsHandler\Payment', 'OnBeforeSalePaymentEntitySaved');
		$eventManager->registerEventHandler('sale', 'OnSaleShipmentEntitySaved', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnSaleShipmentEntitySaved');
		$eventManager->registerEventHandler('sale', 'OnBeforeSaleShipmentDeleted', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnBeforeSaleShipmentDeleted');
		$eventManager->registerEventHandler('sale', 'OnSaleShipmentDeleted', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnSaleShipmentDeleted');
		$eventManager->registerEventHandler('sale', 'onSalePsBeforeInitiatePay', 'crm', '\Bitrix\Crm\Order\EventsHandler\PaySystem', 'onSalePsBeforeInitiatePay');
		$eventManager->registerEventHandler('sale', 'onComponentSaleOrderCheckoutPaymentPayAction', 'crm', '\Bitrix\Crm\Order\EventsHandler\SaleOrderCheckout', 'onPaymentPayAction');
		$eventManager->registerEventHandler('sale', 'onComponentSaleOrderCheckoutPrepareJsonData', 'crm', '\Bitrix\Crm\Order\EventsHandler\SaleOrderCheckout', 'onPrepareJsonData');
		$eventManager->registerEventHandler('sale', 'OnBeforeSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onBeforeSalePaymentEntitySaved');
		$eventManager->registerEventHandler('sale', 'OnSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onSalePaymentEntitySaved');
		$eventManager->registerEventHandler('sale', 'OnSalePaymentEntityDeleted', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onSalePaymentEntityDeleted');
		$eventManager->registerEventHandler('sale', 'OnCheckCollateDocuments', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Check', 'onCheckCollateDocuments');

		$eventManager->registerEventHandler(
			'location', 'AddressOnUpdate',
			'crm', '\Bitrix\Crm\EntityAddress', 'onLocationAddressUpdate'
		);
		$eventManager->registerEventHandler(
			'location', 'AddressOnDelete',
			'crm', '\Bitrix\Crm\EntityAddress', 'onLocationAddressDelete'
		);
		$eventManager->registerEventHandler('sale', 'onSalePsInitiatePayError', 'crm', '\Bitrix\Crm\Order\EventsHandler\PaySystem', 'onSalePsInitiatePayError');

		$eventManager->registerEventHandler('intranet', 'onBuildBindingMenu', 'crm', '\Bitrix\Crm\Integration\Intranet\BindingMenu', 'onBuildBindingMenu');
		$eventManager->registerEventHandler('intranet', 'onBuildBindingMap', 'crm', '\Bitrix\Crm\Integration\Intranet\BindingMenu', 'onBuildBindingMap');

		$eventManager->registerEventHandler('main', 'onGetUserFieldTypeFactory', $this->MODULE_ID, '\Bitrix\Crm\Service\EventHandler', 'onGetUserFieldTypeFactory');

		$eventManager->registerEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Sms',
			'onMessageSent'
		);

		$eventManager->registerEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'crm',
			'\Bitrix\Crm\Activity\Provider\WhatsApp',
			'onMessageSent'
		);

		$eventManager->registerEventHandler(
			'messageservice',
			'messageUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Sms',
			'onMessageStatusUpdated'
		);

		$eventManager->registerEventHandler(
			'messageservice',
			'messageUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\WhatsApp',
			'onMessageStatusUpdated'
		);

		$eventManager->registerEventHandler(
			'notifications',
			'onMessageSuccessfullyEnqueued',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Notification',
			'onMessageSent'
		);

		$eventManager->registerEventHandler(
			'notifications',
			'onMessageSuccessfullyUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Notification',
			'onMessageStatusUpdated'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnDeliveryRequestCreated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Delivery',
			'onDeliveryRequestCreated'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnDeliveryRequestDeleted',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Delivery',
			'onDeliveryRequestDeleted'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnDeliveryRequestMessageReceived',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\DeliveryRequest',
			'OnMessageReceived'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnDeliveryRequestMessageReceived',
			'crm',
			'\Bitrix\Crm\Timeline\DeliveryController',
			'onMessageReceived'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnNeedDeliveryRecipientContact',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Delivery',
			'onNeedRecipientContactData'
		);

		$eventManager->registerEventHandler(
			'imopenlines',
			'OnAfterImopenlineActiveChange',
			'crm',
			'\Bitrix\Crm\SiteButton\Manager',
			'onAfterImopenlineActiveChange'
		);

		$eventManager->registerEventHandler(
			'imopenlines',
			'OnImopenlineDelete',
			'crm',
			'\Bitrix\Crm\SiteButton\Manager',
			'onImopenlineDelete'
		);

		$eventManager->registerEventHandler(
			'imopenlines',
			'OnImOpenLineRegisteredInCrm',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnImOpenLineRegisteredInCrm'
		);

		$eventManager->registerEventHandler(
			'imopenlines',
			'OnChatFinish',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnChatFinish'
		);

		$eventManager->registerEventHandler(
			'imopenlines',
			'OnOperatorTransfer',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnOperatorTransfer'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'Bitrix\Catalog\Model\Price::OnAfterUpdate',
			'crm',
			'\Bitrix\Crm\WebForm\Manager',
			'onCatalogPriceAfterUpdate'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'DocumentCard:onCollectRightColumnContent',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onCollectRightColumnContent'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'OnDocumentAdd',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onDocumentCreate'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'OnDocumentUpdate',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onDocumentUpdate'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'DocumentCard:onConductFailureAfterSave',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onConductFailureAfterSave'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnSaleOrderSaved',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\BasketReservation',
			'OnSaleOrderSaved'
		);

		$eventManager->registerEventHandler(
			'sale',
			'\Bitrix\Sale\Reservation\Internals\BasketReservation::OnAfterDelete',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\BasketReservation',
			'onAfterDelete'
		);

		$eventManager->registerEventHandler(
			'crm',
			'OnBeforeCrmDealDelete',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'onBeforeCrmDealDelete'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Integration\Sale\ShipmentDocumentAnalytics',
			'onSaleShipmentEntitySaved'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Integration\Sale\ShipmentDocumentTimeline',
			'onSaleShipmentEntitySaved'
		);

		$eventManager->registerEventHandler(
			'crm',
			'OnBeforeCrmDealProductRowsSave',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'OnBeforeCrmDealProductRowsSave'
		);

		$eventManager->registerEventHandler(
			'crm',
			'OnAfterCrmDealProductRowsSave',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'OnAfterCrmDealProductRowsSave'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnSalePaymentEntitySaved',
			'crm',
			'\Bitrix\Crm\Reservation\EventsHandler\Payment',
			'OnSalePaymentEntitySaved'
		);

		$eventManager->registerEventHandler(
			'bitrix24',
			'onFeedbackCollectorCheckCanRun',
			'crm',
			'\Bitrix\Crm\Integration\Bitrix24\FeedbackCollector',
			'onFeedbackCollectorCheckCanRun'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnReservationSettingsBuild',
			'crm',
			'\Bitrix\Crm\Integration\Sale\Reservation\Event\ReservationSettingsBuildEventHandler',
			'OnReservationSettingsBuild'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'onGetContractorsProvider',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\EventHandler',
			'onGetContractorsProviderEventHandler'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'onGetContractorsConverter',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\EventHandler',
			'onGetContractorsConverterEventHandler'
		);

		$eventManager->registerEventHandler(
			'catalog',
			'onAfterCatalogRolePermissionSave',
			'crm',
			'\CCrmSaleHelper',
			'updateShopAccess'
		);

		$eventManager->registerEventHandler(
			'calendar',
			'onSharedCrmActions',
			'crm',
			'\Bitrix\Crm\Integration\Calendar\CalendarSharingTimeline',
			'onSharedCrmActions'
		);

		$eventManager->registerEventHandler(
			'ai',
			'onQueueJobExecute',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onQueueJobExecute',
		);
		$eventManager->registerEventHandler(
			'ai',
			'onQueueJobFail',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onQueueJobFail',
		);
		$eventManager->registerEventHandler(
			'ai',
			'onTuningLoad',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onTuningLoad',
		);

		$eventManager->registerEventHandler(
			'ui',
			'onUIFormResetScope',
			'crm',
			'\\Bitrix\\Crm\\Component\\EntityDetails\\Config\\Scope',
			'onUIFormResetScope'
		);

		$eventManager->registerEventHandler(
			'ui',
			'onUIFormSetScope',
			'crm',
			'\\Bitrix\\Crm\\Component\\EntityDetails\\Config\\Scope',
			'onUIFormSetScope'
		);

		$eventManager->registerEventHandler(
			'sale',
			'onSalePsBeforeInitiatePay',
			'crm',
			'\Bitrix\Crm\Terminal\EventsHandler\OnSalePsBeforeInitiatePay',
			'handle'
		);

		$eventManager->registerEventHandler(
			'main',
			'OnAfterSetOption_def_mycompany_id',
			'crm',
			'\Bitrix\Crm\Requisite\EntityLink',
			'clearMyCompanyCache'
		);

		$eventManager->registerEventHandler(
			'biconnector',
			'onBIConnectorDataSources',
			'crm',
			'\Bitrix\Crm\Integration\BiConnector\EventHandler',
			'onBIConnectorDataSources'
		);

		$eventManager->registerEventHandler(
			'bizproc',
			'onAfterWorkflowKill',
			'crm',
			'\Bitrix\Crm\Integration\BizProc\EventHandler',
			'onAfterWorkflowKill'
		);

		$eventManager->registerEventHandler(
			'rest',
			'OnUserFieldPlacementPrepareParams',
			'crm',
			'\Bitrix\Crm\Integration\Rest\EventHandler',
			'onUserFieldPlacementPrepareParams'
		);

		$eventManager->registerEventHandler(
			'booking',
			'onGetProvider',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onGetProviderEventHandler'
		);

		$eventManager->registerEventHandler(
			'booking',
			'onBookingAdd',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingAdd'
		);

		$eventManager->registerEventHandler(
			'booking',
			'onBookingUpdate',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingUpdate'
		);

		$eventManager->registerEventHandler(
			'booking',
			'onBookingDelete',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingDelete'
		);
	}

	private function installAgents()
	{
		//region Search Content
		$startTime = ConvertTimeStamp(time() + CTimeZone::GetOffset() + 60, 'FULL');
		if (COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('\Bitrix\Crm\Agent\Search\LeadSearchContentRebuildAgent::run();', 'crm', 'Y', 2, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('\Bitrix\Crm\Agent\Search\DealSearchContentRebuildAgent::run();', 'crm', 'Y', 2, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('\Bitrix\Crm\Agent\Search\QuoteSearchContentRebuildAgent::run();', 'crm', 'Y', 2, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('\Bitrix\Crm\Agent\Search\CompanySearchContentRebuildAgent::run();', 'crm', 'Y', 2, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('\Bitrix\Crm\Agent\Search\ContactSearchContentRebuildAgent::run();', 'crm', 'Y', 2, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('Bitrix\Crm\Agent\Search\InvoiceSearchContentRebuildAgent::run();', 'crm', 'Y', 0, '', 'Y', $startTime, 100, false, false);
		}
		if (COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SEARCH_CONTENT', 'N') === 'Y')
		{
			CAgent::AddAgent('Bitrix\Crm\Agent\Search\OrderSearchContentRebuildAgent::run();', 'crm', 'Y', 0, '', 'Y', $startTime, 100, false, false);
		}
		//endregion

		if (COption::GetOptionString('crm', '~CRM_DEAL_MANUAL_OPPORTUNITY_INITIATED', 'N') === 'N')
		{
			CAgent::AddAgent('Bitrix\Crm\Agent\Opportunity\DealManualOpportunityAgent::run();', 'crm', 'Y', 0, '', 'Y', $startTime, 100, false, false);
		}

		if (COption::GetOptionString('crm', '~CRM_CONVERT_COMPANY_ADDRESSES', 'N') === 'Y')
		{
			CAgent::AddAgent(
				'Bitrix\\Crm\\Agent\\Requisite\\CompanyAddressConvertAgent::run();',
				'crm', 'Y', 0, '', 'Y', $startTime, 100, false, false
			);
		}
		if (COption::GetOptionString('crm', '~CRM_CONVERT_CONTACT_ADDRESSES', 'N') === 'Y')
		{
			CAgent::AddAgent(
				'Bitrix\\Crm\\Agent\\Requisite\\ContactAddressConvertAgent::run();',
				'crm', 'Y', 0, '', 'Y', $startTime, 100, false, false
			);
		}

		CAgent::AddAgent('\Bitrix\Crm\Ml\PredictionQueue::processQueue();', 'crm', 'N', 300);
		CAgent::AddAgent('\Bitrix\Crm\Ml\Agent\Retraining::run();', 'crm', 'N', 86400);
		CAgent::AddAgent('\Bitrix\Crm\Agent\Recyclebin\RecyclebinAgent::run();', 'crm', 'N', 3600);

		CAgent::AddAgent(
			'Bitrix\\Crm\\Agent\\Duplicate\\Automatic\\LeadDuplicateIndexRebuildAgent::run();',
			'crm', 'N', 3600
		);
		CAgent::AddAgent(
			'Bitrix\\Crm\\Agent\\Duplicate\\Automatic\\ContactDuplicateIndexRebuildAgent::run();',
			'crm', 'N', 3600
		);
		CAgent::AddAgent(
			'Bitrix\\Crm\\Agent\\Duplicate\\Automatic\\CompanyDuplicateIndexRebuildAgent::run();',
			'crm', 'N', 3600
		);

		CAgent::AddAgent(
			/** @see \Bitrix\Crm\Service\Factory\SmartInvoice::createTypeIfNotExists() */
			"\\Bitrix\\Crm\\Service\\Factory\\SmartInvoice::createTypeIfNotExists();",
			"crm",
			"N",
			3600,
			'',
			'Y',
			$startTime
		);

		CAgent::AddAgent(
			/** @see \Bitrix\Crm\Service\Factory\SmartDocument::createTypeIfNotExists() */
			"\\Bitrix\\Crm\\Service\\Factory\\SmartDocument::createTypeIfNotExists();",
			"crm",
			"N",
			3600,
			'',
			'Y',
			$startTime
		);

		CAgent::AddAgent(
			/** @see \Bitrix\Crm\Integration\Sign\Access::installDefaultRoles() */
			'\Bitrix\Crm\Integration\Sign\Access::installDefaultRoles();',
			'crm',
			'N',
			60,
			'',
			'Y',
			$startTime,
		);

		// set initial values for MOVED_BY_ID and MOVED_TIME fields in leads
		if (\Bitrix\Main\Config\Option::get('crm', 'need_set_lead_moved_by_field') === 'Y')
		{
			\Bitrix\Crm\Agent\MovedByField\LeadFieldAgent::bind();
		}
		// set initial values for MOVED_BY_ID and MOVED_TIME fields in deals
		if (\Bitrix\Main\Config\Option::get('crm', 'need_set_deal_moved_by_field') === 'Y')
		{
			\Bitrix\Crm\Agent\MovedByField\DealFieldAgent::bind();
		}
		// set initial values for LAST_ACTIVITY_TIME and LAST_ACTIVITY_BY fields
		\Bitrix\Crm\Update\Entity\LastActivityFields::bindOnCrmModuleInstallIfNeeded();

		\Bitrix\Crm\Update\Entity\ContactId::bindOnCrmModuleInstallIfNeeded();

		// fill b_crm_entity_uncompleted_act table
		if (\Bitrix\Main\Config\Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'N')
		{
			\Bitrix\Crm\Agent\Activity\ProcessEntityUncompletedActivitiesAgent::bind();
		}
		// fill b_crm_entity_countable_act table
		if (\Bitrix\Main\Config\Option::get('crm', 'enable_entity_countable_act', 'Y') === 'N')
		{
			\Bitrix\Crm\Agent\Activity\ProcessEntityCountableActivitiesAgent::bind();
		}

		CAgent::AddAgent('\Bitrix\Crm\Reservation\Agent\ReservedProductCleaner::runAgent();', 'crm', 'N', 86400);

		if (\Bitrix\Main\Config\Option::get('crm', 'CRM_MOVE_OBSERVERS_TO_ACCESS_ATTR_IN_WORK', 'N')  === 'Y') {
			CAgent::AddAgent(
				'Bitrix\Crm\Agent\Security\AssignAccessRightsToObserversAgent::run();',
				'crm',
				'N',
				60,
				'',
				'Y',
				ConvertTimeStamp(time()+CTimeZone::GetOffset()+600)
			);
		}

		CAgent::AddAgent(
			'Bitrix\Crm\Agent\Activity\CompleteOldActivities::run();',
			'crm',
			'N',
			86400,
			'',
			'Y',
			ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL')
		);

		CAgent::AddAgent(
			'Bitrix\Crm\Agent\Activity\LightCounterAgent::run();',
			'crm',
			'N',
			60,
		);

		\Bitrix\Crm\Update\RemoveDuplicatingMultifieldsStepper::bindOnCrmModuleInstall();

		CAgent::AddAgent(
			"Bitrix\\Crm\\Agent\\Activity\\PingAgent::run();",
			"crm",
			"N",
			60,
			'',
			'Y',
			ConvertTimeStamp(time() + CTimeZone::GetOffset() + 600, 'FULL')
		);

		CAgent::AddAgent(
			'Bitrix\Crm\Agent\Badge\RemoveOldEntityBadgesAgent::run();',
			'crm'
		);

		CAgent::AddAgent(
			'Bitrix\Crm\Agent\Duplicate\DedupeCacheCleanerAgent::run();',
			'crm',
			'N',
			3600 * 24,
			'',
			'Y',
			time() + CTimeZone::GetOffset() + 600,
			100,
			false,
			false
		);
	}

	private function uninstallEventHandlers()
	{
		//region ModuleDependences
		UnRegisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterList');
		unregisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterListImap');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrm', 'GetUserTypeDescription');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrmStatus', 'GetUserTypeDescription');
		UnRegisterModuleDependences('main', 'OnUserDelete', 'crm', '\Bitrix\Crm\Kanban\SortTable', 'clearUser');
		UnRegisterModuleDependences('search', 'OnReindex', 'crm', 'CCrmSearch', 'OnSearchReindex');
		UnRegisterModuleDependences('search', 'OnSearchCheckPermissions', 'crm', 'CCrmSearch', 'OnSearchCheckPermissions');
		UnRegisterModuleDependences('report', 'OnReportAdd', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('report', 'OnReportUpdate', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('report', 'OnReportDelete', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('iblock', 'OnIBlockDelete', 'crm', 'CCrmCatalog', 'OnIBlockDelete');
		UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'crm', '\Bitrix\Crm\Order\Import\Instagram', 'onAfterIblockElementDelete');

		UnRegisterModuleDependences("socialnetwork", "OnFillSocNetLogEvents", "crm", "CCrmExternalSaleImport", "OnFillSocNetLogEvents");

		UnRegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'crm', 'CAllCrmActivity', 'OnBeforeTaskAdd');
		UnRegisterModuleDependences('tasks', 'OnTaskAdd', 'crm', 'CAllCrmActivity', 'OnTaskAdd');
		UnRegisterModuleDependences('tasks', 'OnTaskUpdate', 'crm', 'CAllCrmActivity', 'OnTaskUpdate');
		UnRegisterModuleDependences('tasks', 'OnTaskDelete', 'crm', 'CAllCrmActivity', 'OnTaskDelete');

		UnRegisterModuleDependences('webdav', 'OnFileDelete', 'crm', 'CCrmWebDavHelper', 'OnWebDavFileDelete');

		UnRegisterModuleDependences('subscribe', 'BeforePostingSendMail', 'crm', 'CCrmEMail', 'BeforeSendMail');
		UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'crm', 'CAllCrmActivity', 'OnCalendarEventEdit');
		UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'crm', 'CAllCrmActivity', 'OnCalendarEventDelete');

		UnRegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmInvoiceRestService', 'onRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmRestService', 'onRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'crm', '\Bitrix\Crm\SiteButton\Rest', 'onRestServiceBuildDescription');
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'crm', '\Bitrix\Crm\WebForm\Rest', 'onRestServiceBuildDescription');

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'crm', 'CCrmLiveFeed', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogEvents');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogFields', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogFields');
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogFilter', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogFilter');
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogOrder', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogOrder');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetLogFormatDestination', 'crm', 'CCrmLiveFeed', 'OnSocNetLogFormatDestination');
		UnRegisterModuleDependences("socialnetwork", "OnAfterSocNetLogFormatDestination", "crm", "CCrmLiveFeed", "OnAfterSocNetLogFormatDestination");
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogPerms', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogPerms');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogRightsAdd', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogRightsAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogCommentCounterIncrement', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogCommentCounterIncrement');
		UnRegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogEntryCommentAdd', 'crm', 'CCrmLiveFeed', 'OnAfterSocNetLogEntryCommentAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogEntryGetRights', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogEntryGetRights');
		UnRegisterModuleDependences("socialnetwork", "OnSendMentionGetEntityFields", "crm", "CCrmLiveFeed", "OnSendMentionGetEntityFields");
		UnRegisterModuleDependences("socialnetwork", "OnSonetLogCounterClear", "crm", "CCrmLiveFeedComponent", "OnSonetLogCounterClear");
		UnRegisterModuleDependences("socialnetwork", "OnAfterSocNetLogCommentAdd", "crm", "CCrmLiveFeed", "OnAfterSocNetLogCommentAdd");
		UnRegisterModuleDependences('main', 'OnAddRatingVote', 'crm', 'CCrmLiveFeed', 'OnAddRatingVote');
		UnRegisterModuleDependences('main', 'OnGetRatingContentOwner', 'crm', 'CCrmLiveFeed', 'OnGetRatingContentOwner');
		UnRegisterModuleDependences('im', 'OnGetMessageRatingVote', 'crm', 'CCrmLiveFeed', 'OnGetMessageRatingVote');

		UnRegisterModuleDependences('imconnector', 'OnAddStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');
		UnRegisterModuleDependences('imconnector', 'OnUpdateStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');
		UnRegisterModuleDependences('imconnector', 'OnDeleteStatusConnector', 'crm', '\Bitrix\Crm\SiteButton\Manager', 'onImConnectorChange');

		UnRegisterModuleDependences('forum', 'OnAfterCommentAdd', 'crm', 'CCrmLiveFeed', 'onAfterCommentAdd');
		UnRegisterModuleDependences('disk', 'onAfterDeleteFile', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'OnDiskFileDelete');

		UnRegisterModuleDependences("main", "OnAfterRegisterModule", "main", "crm", "InstallUserFields", "/modules/crm/install/index.php"); // check crm UF

		UnRegisterModuleDependences('disk', 'onBuildAdditionalConnectorList', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'onBuildConnectorList');

		UnRegisterModuleDependences('intranet', 'OnTransferEMailUser', 'intranet', '\Bitrix\Crm\Integration\Intranet\InviteDialog', 'onTransferEMailUser');

		UnRegisterModuleDependences('main', 'OnMailEventMailChangeStatus', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onMailEventMailChangeStatus');
		UnRegisterModuleDependences('main', 'OnMailEventMailChangeStatus', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onMailEventSendNotification');

		UnRegisterModuleDependences(
			'main',
			'OnBeforeUserTypeAdd',
			'crm',
			'\Bitrix\Crm\Service\EventHandler',
			'OnBeforeUserTypeAdd'
		);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~crm_webform_max_activated', 'crm', '\Bitrix\Crm\WebForm\Form', 'onAfterSetOptionCrmWebFormMaxActivated');
		$eventManager->unregisterEventHandler('mail', 'OnMessageObsolete', 'crm', 'CCrmEMail', 'OnImapEmailMessageObsolete');
		$eventManager->unregisterEventHandler('crm', 'OnActivityModified', 'crm', 'CCrmEMail', 'OnActivityModified');
		$eventManager->unregisterEventHandler('crm', 'OnActivityDelete', 'crm', 'CCrmEMail', 'OnActivityDelete');
		$eventManager->unregisterEventHandler('main', 'OnMailEventMailRead', 'crm', 'CCrmEMail', 'OnOutgoingMessageRead');
		$eventManager->unregisterEventHandler('main', 'OnMailEventMailClick', 'crm', 'CCrmEMail', 'OnOutgoingMessageClick');
		$eventManager->unregisterEventHandler('sale', 'OnSalePsServiceProcessRequestBeforePaid', 'crm', '\Bitrix\Crm\InvoiceTable', 'redeterminePaySystem');

		$eventManager->unRegisterEventHandler('iblock', 'OnIBlockPropertyBuildList', 'crm', '\Bitrix\Crm\Integration\IBlockElementProperty', 'getUserTypeDescription');
		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnBeforeIBlockElementDelete',
			'crm',
			'CCrmProduct',
			'HandlerOnBeforeIBlockElementDelete'
		);
		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			'crm',
			'CCrmProduct',
			'HandlerOnAfterIBlockElementDelete'
		);
		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnBeforeIBlockElementAdd',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleBeforeIblockElementAdd'
		);
		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementAdd',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleAfterIblockElementAdd'
		);
		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			'crm',
			'\Bitrix\Crm\Config\State',
			'handleAfterIblockElementDelete'
		);

		$eventManager->unRegisterEventHandler('catalog', 'Bitrix\Catalog\Product\Entity::OnAfterUpdate', 'crm', '\CCrmProduct', 'handlerAfterProductUpdate');

		$eventManager->unregisterEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterAdd', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetAdd');
		$eventManager->unregisterEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterUpdate', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetUpdate');
		$eventManager->unregisterEventHandler('crm', '\\Bitrix\\Crm\\Preset::OnAfterDelete', 'crm', '\\Bitrix\\Crm\\EntityRequisite', 'onAfterPresetDelete');

		$eventManager->unRegisterEventHandler('socialnetwork', 'onUserProfileRedirectGetUrl', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onUserProfileRedirectGetUrl');
		$eventManager->unRegisterEventHandler('main', 'OnUserConsentProviderList', 'crm', '\Bitrix\Crm\Integration\UserConsent', 'onProviderList');
		$eventManager->unRegisterEventHandler('main', 'OnUserConsentDataProviderList', 'crm', '\Bitrix\Crm\Integration\UserConsent', 'onDataProviderList');

		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeAdd', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onAdd');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\UserField\UserFieldHistory', 'onDelete');
		$eventManager->unRegisterEventHandler('documentgenerator', 'onGetDataProviderList', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'getDataProviders');
		$eventManager->unRegisterEventHandler('documentgenerator', 'onCreateDocument', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onCreateDocument');
		$eventManager->unRegisterEventHandler('documentgenerator', 'onDocumentTransformationComplete', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onDocumentTransformationComplete');
		$eventManager->unRegisterEventHandler('documentgenerator', 'onUpdateDocument', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onUpdateDocument');
		$eventManager->unRegisterEventHandler('documentgenerator', '\Bitrix\DocumentGenerator\Model\Document::OnBeforeDelete', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onDeleteDocument');
		$eventManager->unRegisterEventHandler('documentgenerator', 'onPublicView', 'crm', '\Bitrix\Crm\Integration\DocumentGeneratorManager', 'onPublicView');

		$eventManager->unRegisterEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteUserQuotesNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->unRegisterEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteIdNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->unRegisterEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\InvoiceIdNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->unRegisterEventHandler('main', 'onNumberGeneratorsClassesCollect', 'crm', '\Bitrix\Crm\Integration\Numerator\InvoiceUserInvoicesNumberGenerator', 'onGeneratorClassesCollect');
		$eventManager->unRegisterEventHandler('main', '\Bitrix\Main\Numerator\Model\Numerator::OnAfterAdd', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteNumberCompatibilityManager', 'updateQuoteNumberType');
		$eventManager->unRegisterEventHandler('main', '\Bitrix\Main\Numerator\Model\Numerator::OnAfterUpdate', 'crm', '\Bitrix\Crm\Integration\Numerator\QuoteNumberCompatibilityManager', 'updateQuoteNumberType');

		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeAdd', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldAdd');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeDelete', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldDelete');
		$eventManager->unRegisterEventHandler('main', 'onAfterSetEnumValues', 'crm', 'CCrmRestEventDispatcher', 'onUserFieldSetEnumValues');

		$eventManager->unRegisterEventHandler('main', 'onAfterSetEnumValues', 'crm', '\Bitrix\Crm\Order\Matcher\FieldSynchronizer', 'onAfterSetEnumValues');
		$eventManager->unRegisterEventHandler('main', 'OnUserLoginExternal', 'crm', '\Bitrix\Crm\Order\Buyer', 'onUserLoginExternalHandler');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserAdd', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserAddHandler');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserUpdate', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserUpdateHandler');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserSendPassword', 'crm', '\Bitrix\Crm\Order\Buyer', 'onBeforeUserSendPasswordHandler');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserChangePassword', 'crm', '\Bitrix\Crm\Order\Buyer', 'OnBeforeUserChangePasswordHandler');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeSendUserInfo', 'crm', '\Bitrix\Crm\Order\Buyer', 'OnBeforeSendUserInfoHandler');

		//analytics, visualconstructor events
		$eventManager->unRegisterEventHandler('report', 'onReportCategoryCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onReportCategoriesCollect');
		$eventManager->unRegisterEventHandler('report', 'onReportsCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onReportHandlerCollect');
		$eventManager->unRegisterEventHandler('report', 'onReportViewCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onViewsCollect');
		$eventManager->unRegisterEventHandler('report', 'onDefaultBoardsCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onDefaultBoardsCollect');
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onAnalyticPageCollect');
		$eventManager->unRegisterEventHandler('report', 'onAnalyticPageBatchCollect', 'crm', '\Bitrix\Crm\Integration\Report\EventHandler', 'onAnalyticPageBatchCollect');


		$eventManager->unRegisterEventHandler('socialnetwork', 'onLogProviderGetContentId', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onLogProviderGetContentId');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onLogProviderGetProvider', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onLogProviderGetProvider');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onCommentAuxGetPostTypeList', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxGetPostTypeList');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onCommentAuxGetCommentTypeList', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxGetCommentTypeList');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onLogIndexGetContent', 'crm', '\Bitrix\Crm\Integration\Socialnetwork\Log', 'onIndexGetContent');

		$eventManager->unRegisterEventHandler('socialnetwork', 'onCommentAuxInitJs', 'crm', '\Bitrix\Crm\Integration\Socialnetwork', 'onCommentAuxInitJs');

		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeUpdate', 'crm', '\Bitrix\Crm\Attribute\FieldAttributeManager', 'onUserFieldUpdate');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\Attribute\FieldAttributeManager', 'onUserFieldDelete');

		$eventManager->unRegisterEventHandler('sale', 'OnModuleUnInstall', 'crm', '', 'CrmOnModuleUnInstallSale');
		$eventManager->unRegisterEventHandler('voximplant', 'onCallRegisteredInCrm', 'crm', '\Bitrix\Crm\Integration\VoxImplant\EventHandler', 'onCallRegisteredInCrm');
		$eventManager->unRegisterEventHandler('voximplant', 'onCallEnd', 'crm', '\Bitrix\Crm\Integration\VoxImplant\EventHandler', 'onCallEnd');

		$eventManager->unRegisterEventHandler('main', 'OnUISelectorGetProviderByEntityType', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorGetProviderByEntityType');
		$eventManager->unRegisterEventHandler('main', 'OnUISelectorBeforeSave', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorBeforeSave');
		$eventManager->unRegisterEventHandler('main', 'OnUISelectorFillLastDestination', 'crm', '\Bitrix\Crm\Integration\Main\UISelector\Handler', 'OnUISelectorFillLastDestination');

		$eventManager->unRegisterEventHandler('ml', 'onModelStateChange', 'crm', '\Bitrix\Crm\Ml\Scoring', 'onMlModelStateChange');

		$eventManager->unRegisterEventHandler('landing', '\Bitrix\Landing\Internals\Landing::OnBeforeDelete', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeLandingDelete');
		$eventManager->unRegisterEventHandler('landing', 'onBeforeLandingRecycle', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeLandingRecycle');
		$eventManager->unRegisterEventHandler('landing', 'onBeforeSiteRecycle', 'crm', '\Bitrix\Crm\Integration\Landing\EventHandler', 'onBeforeSiteRecycle');

		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationImport', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onImport');
		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationExport', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onExport');
		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationClear', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'onClear');
		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationEntity', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Controller', 'getEntityList');
		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationGetManifest', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\Manifest', 'getList');
		$eventManager->unregisterEventHandler('rest', 'OnRestApplicationConfigurationFinish', 'crm', '\Bitrix\Crm\Integration\Rest\Configuration\ConfigChecker', 'onFinish');

		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterAdd', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormAdd');
		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterUpdate', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormUpdate');
		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\WebForm\Internals\Form::OnAfterDelete', 'crm', '\Bitrix\Crm\Order\TradingPlatform\WebForm', 'onWebFormDelete');

		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterAdd', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityAdd');
		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterUpdate', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityUpdate');
		$eventManager->unregisterEventHandler('crm', '\Bitrix\Crm\Model\Dynamic\Type::OnAfterDelete', 'crm', '\Bitrix\Crm\Order\TradingPlatform\DynamicEntity', 'onEntityDelete');

		$eventManager->unregisterEventHandler('sale', 'onSalePsInitiatePayError', 'crm', '\Bitrix\Crm\Order\EventsHandler\PaySystem', 'onSalePsInitiatePayError');

		$eventManager->unRegisterEventHandler('recyclebin', 'OnModuleSurvey', 'crm', '\Bitrix\Crm\Integration\Recyclebin\RecyclingManager', 'OnModuleSurvey');
		$eventManager->unRegisterEventHandler('recyclebin', 'onAdditionalDataRequest', 'crm', '\Bitrix\Crm\Integration\Recyclebin\RecyclingManager', 'onAdditionalDataRequest');
		$eventManager->unRegisterEventHandler('location', 'onCurrentFormatCodeChanged', 'crm', '\Bitrix\Crm\Integration\Location\Format', 'onCurrentFormatCodeChanged');
		$eventManager->unRegisterEventHandler('location', 'onInitialFormatCodeSet', 'crm', '\Bitrix\Crm\Integration\Location\Format', 'onInitialFormatCodeSet');
		$eventManager->unregisterEventHandler(
			'location', 'AddressOnUpdate',
			'crm', '\Bitrix\Crm\EntityAddress', 'onLocationAddressUpdate'
		);
		$eventManager->unregisterEventHandler(
			'location', 'AddressOnDelete',
			'crm', '\Bitrix\Crm\EntityAddress', 'onLocationAddressDelete'
		);
		//endregion

		$eventManager->unRegisterEventHandler(
			'iblock',
			'onGetUrlBuilders',
			'crm',
			'\Bitrix\Crm\Product\Url\Registry',
			'getBuilderList'
		);

		$eventManager->unRegisterEventHandler('intranet', 'onBuildBindingMenu', 'crm', '\Bitrix\Crm\Integration\Intranet\BindingMenu', 'onBuildBindingMenu');
		$eventManager->unRegisterEventHandler('intranet', 'onBuildBindingMap', 'crm', '\Bitrix\Crm\Integration\Intranet\BindingMenu', 'onBuildBindingMap');

		$eventManager->unRegisterEventHandler(
			'pull',
			'onGetDependentModule',
			'crm',
			'\Bitrix\Crm\Integration\PullManager',
			'onGetDependentModule'
		);

		$eventManager->unRegisterEventHandler('main', 'onAfterSetEnumValues', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onAfterSetEnumValues');
		$eventManager->unRegisterEventHandler('main', 'OnAfterUserTypeDelete', 'crm', '\Bitrix\Crm\Integration\Main\EventHandler', 'onAfterUserTypeDelete');

		$eventManager->unRegisterEventHandler('main', 'onGetUserFieldTypeFactory', $this->MODULE_ID, '\Bitrix\Crm\Service\EventHandler', 'onGetUserFieldTypeFactory');

		$eventManager->unRegisterEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Sms',
			'onMessageSent'
		);

		$eventManager->unRegisterEventHandler(
			'messageservice',
			'OnMessageSuccessfullySent',
			'crm',
			'\Bitrix\Crm\Activity\Provider\WhatsApp',
			'onMessageSent'
		);

		$eventManager->unRegisterEventHandler(
			'messageservice',
			'messageUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Sms',
			'onMessageStatusUpdated'
		);

		$eventManager->unRegisterEventHandler(
			'messageservice',
			'messageUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\WhatsApp',
			'onMessageStatusUpdated'
		);

		$eventManager->unRegisterEventHandler(
			'notifications',
			'onMessageSuccessfullyEnqueued',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Notification',
			'onMessageSent'
		);

		$eventManager->unRegisterEventHandler(
			'notifications',
			'onMessageSuccessfullyUpdated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Notification',
			'onMessageStatusUpdated'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnDeliveryRequestCreated',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Delivery',
			'onDeliveryRequestCreated'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnDeliveryRequestDeleted',
			'crm',
			'\Bitrix\Crm\Activity\Provider\Delivery',
			'onDeliveryRequestDeleted'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnDeliveryRequestMessageReceived',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\DeliveryRequest',
			'OnMessageReceived'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnDeliveryRequestMessageReceived',
			'crm',
			'\Bitrix\Crm\Timeline\DeliveryController',
			'onMessageReceived'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnNeedDeliveryRecipientContact',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Delivery',
			'onNeedRecipientContactData'
		);

		$eventManager->unRegisterEventHandler('sale', 'OnCheckCollateDocuments', 'crm', '\Bitrix\Crm\Order\EventsHandler\Check', 'OnCheckCollateDocuments');
		$eventManager->unRegisterEventHandler('sale', 'OnBeforeSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Order\EventsHandler\Payment', 'OnBeforeSalePaymentEntitySaved');
		$eventManager->unRegisterEventHandler('sale', 'OnSaleShipmentEntitySaved', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnSaleShipmentEntitySaved');
		$eventManager->unRegisterEventHandler('sale', 'OnBeforeSaleShipmentDeleted', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnBeforeSaleShipmentDeleted');
		$eventManager->unRegisterEventHandler('sale', 'OnSaleShipmentDeleted', 'crm', '\Bitrix\Crm\Order\EventsHandler\Shipment', 'OnSaleShipmentDeleted');
		$eventManager->unRegisterEventHandler('sale', 'onSalePsBeforeInitiatePay', 'crm', '\Bitrix\Crm\Order\EventsHandler\PaySystem', 'onSalePsBeforeInitiatePay');
		$eventManager->unRegisterEventHandler('sale', 'onComponentSaleOrderCheckoutPaymentPayAction', 'crm', '\Bitrix\Crm\Order\EventsHandler\SaleOrderCheckout', 'onPaymentPayAction');
		$eventManager->unRegisterEventHandler('sale', 'onComponentSaleOrderCheckoutPrepareJsonData', 'crm', '\Bitrix\Crm\Order\EventsHandler\SaleOrderCheckout', 'onPrepareJsonData');
		$eventManager->unRegisterEventHandler('sale', 'OnBeforeSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onBeforeSalePaymentEntitySaved');
		$eventManager->unRegisterEventHandler('sale', 'OnSalePaymentEntitySaved', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onSalePaymentEntitySaved');
		$eventManager->unRegisterEventHandler('sale', 'OnSalePaymentEntityDeleted', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Payment', 'onSalePaymentEntityDeleted');
		$eventManager->unRegisterEventHandler('sale', 'OnCheckCollateDocuments', 'crm', '\Bitrix\Crm\Terminal\EventsHandler\Check', 'onCheckCollateDocuments');

		$eventManager->unRegisterEventHandler(
			'imopenlines',
			'OnAfterImopenlineActiveChange',
			'crm',
			'\Bitrix\Crm\SiteButton\Manager',
			'onAfterImopenlineActiveChange'
		);

		$eventManager->unRegisterEventHandler(
			'imopenlines',
			'OnImopenlineDelete',
			'crm',
			'\Bitrix\Crm\SiteButton\Manager',
			'onImopenlineDelete'
		);

		$eventManager->unRegisterEventHandler(
			'imopenlines',
			'OnImOpenLineRegisteredInCrm',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnImOpenLineRegisteredInCrm'
		);

		$eventManager->unRegisterEventHandler(
			'imopenlines',
			'OnChatFinish',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnChatFinish'
		);

		$eventManager->unRegisterEventHandler(
			'imopenlines',
			'OnOperatorTransfer',
			'crm',
			'\Bitrix\Crm\Integration\ImOpenLines\EventHandler',
			'OnOperatorTransfer'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'Bitrix\Catalog\Model\Price::OnAfterUpdate',
			'crm',
			'\Bitrix\Crm\WebForm\Manager',
			'onCatalogPriceAfterUpdate'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'DocumentCard:onCollectRightColumnContent',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onCollectRightColumnContent'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'OnDocumentAdd',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onDocumentCreate'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'OnDocumentUpdate',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onDocumentUpdate'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'DocumentCard:onConductFailureAfterSave',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\DocumentCardTimeline',
			'onConductFailureAfterSave'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnSaleOrderSaved',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\BasketReservation',
			'OnSaleOrderSaved'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'\Bitrix\Sale\Reservation\Internals\BasketReservation::OnAfterDelete',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\BasketReservation',
			'onAfterDelete'
		);

		$eventManager->unRegisterEventHandler(
			'crm',
			'OnBeforeCrmDealDelete',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'onBeforeCrmDealDelete'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Integration\Sale\ShipmentDocumentAnalytics',
			'onSaleShipmentEntitySaved'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Integration\Sale\ShipmentDocumentTimeline',
			'onSaleShipmentEntitySaved'
		);

		$eventManager->unRegisterEventHandler(
			'crm',
			'OnBeforeCrmDealProductRowsSave',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'OnBeforeCrmDealProductRowsSave'
		);

		$eventManager->unRegisterEventHandler(
			'crm',
			'OnAfterCrmDealProductRowsSave',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Deal',
			'OnAfterCrmDealProductRowsSave'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnSalePaymentEntitySaved',
			'crm',
			'\Bitrix\Crm\Reservation\EventsHandler\Payment',
			'OnSalePaymentEntitySaved'
		);

		$eventManager->unRegisterEventHandler(
			'bitrix24',
			'onFeedbackCollectorCheckCanRun',
			'crm',
			'\Bitrix\Crm\Integration\Bitrix24\FeedbackCollector',
			'onFeedbackCollectorCheckCanRun'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnReservationSettingsBuild',
			'crm',
			'\Bitrix\Crm\Integration\Sale\Reservation\Event\ReservationSettingsBuildEventHandler',
			'OnReservationSettingsBuild'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'onGetContractorsProvider',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\EventHandler',
			'onGetContractorsProviderEventHandler'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'onGetContractorsConverter',
			'crm',
			'\Bitrix\Crm\Integration\Catalog\EventHandler',
			'onGetContractorsConverterEventHandler'
		);

		$eventManager->unRegisterEventHandler(
			'catalog',
			'onAfterCatalogRolePermissionSave',
			'crm',
			'\CCrmSaleHelper',
			'updateShopAccess'
		);

		$eventManager->unRegisterEventHandler('im', 'OnAfterMessagesAdd', 'crm', '\Bitrix\Crm\Integration\Im\Chat', 'OnAfterMessagesAdd');
		$eventManager->unRegisterEventHandler('im', 'OnAfterChatRead', 'crm', '\Bitrix\Crm\Integration\Im\Chat', 'OnAfterChatRead');

		$eventManager->unRegisterEventHandler(
			'calendar',
			'onSharedCrmActions',
			'crm',
			'\Bitrix\Crm\Integration\Calendar\CalendarSharingTimeline',
			'onSharedCrmActions'
		);

		$eventManager->unRegisterEventHandler(
			'ai',
			'onQueueJobExecute',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onQueueJobExecute',
		);
		$eventManager->unRegisterEventHandler(
			'ai',
			'onQueueJobFail',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onQueueJobFail',
		);
		$eventManager->unRegisterEventHandler(
			'ai',
			'onTuningLoad',
			'crm',
			'\Bitrix\Crm\Integration\AI\EventHandler',
			'onTuningLoad',
		);

		$eventManager->unRegisterEventHandler(
			'ui',
			'onUIFormSetScope',
			'crm',
			'\\Bitrix\\Crm\\Component\\EntityDetails\\Config\\Scope',
			'onUIFormSetScope'
		);

		$eventManager->unRegisterEventHandler(
			'ui',
			'onUIFormResetScope',
			'crm',
			'\\Bitrix\\Crm\\Component\\EntityDetails\\Config\\Scope',
			'onUIFormResetScope'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'onSalePsBeforeInitiatePay',
			'crm',
			'\Bitrix\Crm\Terminal\EventsHandler\OnSalePsBeforeInitiatePay',
			'handle'
		);

		$eventManager->unRegisterEventHandler(
			'main',
			'OnAfterSetOption_def_mycompany_id',
			'crm',
			'\Bitrix\Crm\Requisite\EntityLink',
			'clearMyCompanyCache'
		);

		$eventManager->unRegisterEventHandler(
			'bizproc',
			'onAfterWorkflowKill',
			'crm',
			'\Bitrix\Crm\Integration\BizProc\EventHandler',
			'onAfterWorkflowKill'
		);

		$eventManager->unRegisterEventHandler(
			'booking',
			'onGetProvider',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onGetProviderEventHandler'
		);

		$eventManager->unRegisterEventHandler(
			'booking',
			'onBookingAdd',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingAdd'
		);

		$eventManager->unRegisterEventHandler(
			'booking',
			'onBookingUpdate',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingUpdate'
		);

		$eventManager->unRegisterEventHandler(
			'booking',
			'onBookingDelete',
			'crm',
			'\Bitrix\Crm\Integration\Booking\EventHandler',
			'onBookingDelete'
		);
	}

	private function uninstallAgents()
	{
		// there is no reason to remove each agent separately!
		CAgent::RemoveModuleAgents('crm');
	}

	function DoInstall()
	{
		global $step;
		$step = intval($step);

		if (!CBXFeatures::IsFeatureEditable('crm'))
		{
			$this->errors = Loc::getMessage('MAIN_FEATURE_ERROR_EDITABLE');
			$this->showInstallStep(3);
		}
		elseif (!IsModuleInstalled('sale'))
		{
			$this->errors = Loc::getMessage('CRM_UNINS_MODULE_SALE');
			$this->showInstallStep(3);
		}
		elseif ($step < 2)
		{
			$this->showInstallStep(1);
		}
		elseif ($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();
			CBXFeatures::SetFeatureEnabled('crm');
			$this->showInstallStep(3);
		}
	}

	protected function showInstallStep(int $step)
	{
		global $APPLICATION;

		if ($this->errors !== false)
		{
			$GLOBALS['errors'] = (array)$this->errors;
		}

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('CRM_INSTALL_TITLE'),
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/step' . $step . '.php');
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);

		if (ModuleManager::isModuleInstalled('crmmobile'))
		{
			$APPLICATION->throwException(Loc::getMessage('CRM_MODULE_UNINSTALL_ERROR_CRMMOBILE'));
		}

		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage('CRM_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/unstep1.php');
		}
		elseif ($step == 2)
		{
			\Bitrix\Main\Loader::includeModule('crm');

			$this->UnInstallDB([
				'savedata' => $_REQUEST['savedata'],
			]);

			$this->UnInstallFiles();
			CBXFeatures::SetFeatureEnabled('crm', false);
			$GLOBALS['errors'] = $this->errors;
			$APPLICATION->IncludeAdminFile(Loc::getMessage('CRM_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/unstep2.php');
		}
	}

	private static function __GetMessagesForAllLang($file, $MessID, $strDefMess = false, $arLangList = [])
	{
		$arResult = false;

		if (empty($MessID))
		{
			return $arResult;
		}
		if (!is_array($MessID))
		{
			$MessID = [$MessID];
		}

		if (empty($arLangList))
		{
			$rsLangs = CLanguage::GetList("LID", "ASC", ["ACTIVE" => "Y"]);
			while ($arLang = $rsLangs->Fetch())
			{
				$arLangList[] = $arLang['LID'];
			}
		}
		foreach ($arLangList as $strLID)
		{
			$MESS = Loc::loadLanguageFile($file, $strLID);
			foreach ($MessID as $strMessID)
			{
				if ($strMessID == '')
				{
					continue;
				}
				$arResult[$strMessID][$strLID] = (isset($MESS[$strMessID]) ? $MESS[$strMessID] : $strDefMess);
			}
		}

		return $arResult;
	}
}
