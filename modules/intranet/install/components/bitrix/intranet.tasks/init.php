<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

if (!Function_Exists("__IntaskInitTaskFields"))
{
	CComponentUtil::__IncludeLang(BX_PERSONAL_ROOT."/components/bitrix/intranet.tasks", "init.php");

	function __IntaskInitTaskFields($iblockId, $taskType, $ownerId, $arSelect)
	{
		$arTasksFields = array(
			"ID" => array(
				"NAME" => GetMessage("INTI_ID"),
				"FULL_NAME" => GetMessage("INTI_ID_F"),
				"TYPE" => "int",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 10,
			),
			"NAME" => array(
				"NAME" => GetMessage("INTI_NAME"),
				"FULL_NAME" => GetMessage("INTI_NAME_F"),
				"TYPE" => "string",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "Y",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 60,
			),
			"TIMESTAMP_X" => array(
				"NAME" => GetMessage("INTI_TIMESTAMP_X"),
				"FULL_NAME" => GetMessage("INTI_TIMESTAMP_X_F"),
				"TYPE" => "datetime",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 40,
			),
			"CODE" => array(
				"NAME" => GetMessage("INTI_CODE"),
				"FULL_NAME" => GetMessage("INTI_CODE_F"),
				"TYPE" => "string",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 10,
			),
			"XML_ID" => array(
				"NAME" => GetMessage("INTI_XML_ID"),
				"FULL_NAME" => GetMessage("INTI_XML_ID_F"),
				"TYPE" => "string",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 10,
			),
			"MODIFIED_BY" => array(
				"NAME" => GetMessage("INTI_MODIFIED_BY"),
				"FULL_NAME" => GetMessage("INTI_MODIFIED_BY_F"),
				"TYPE" => "user",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 30,
			),
			"DATE_CREATE" => array(
				"NAME" => GetMessage("INTI_DATE_CREATE"),
				"FULL_NAME" => GetMessage("INTI_DATE_CREATE_F"),
				"TYPE" => "datetime",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 20,
			),
			"CREATED_BY" => array(
				"NAME" => GetMessage("INTI_CREATED_BY"),
				"FULL_NAME" => GetMessage("INTI_CREATED_BY_F"),
				"TYPE" => "user",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 10,
			),
			"DATE_ACTIVE_FROM" => array(
				"NAME" => GetMessage("INTI_DATE_ACTIVE_FROM"),
				"FULL_NAME" => GetMessage("INTI_DATE_ACTIVE_FROM_F"),
				"TYPE" => "datetime",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 80,
			),
			"DATE_ACTIVE_TO" => array(
				"NAME" => GetMessage("INTI_DATE_ACTIVE_TO"),
				"FULL_NAME" => GetMessage("INTI_DATE_ACTIVE_TO_F"),
				"TYPE" => "datetime",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 90,
			),
			"ACTIVE_DATE" => array(
				"NAME" => GetMessage("INTI_ACTIVE_DATE"),
				"FULL_NAME" => GetMessage("INTI_ACTIVE_DATE_F"),
				"TYPE" => "bool",
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => false,
				"FILTERABLE" => true,
				"PSORT" => 10,
			),
			"IBLOCK_SECTION" => array(
				"NAME" => GetMessage("INTI_IBLOCK_SECTION"),
				"FULL_NAME" => GetMessage("INTI_IBLOCK_SECTION_F"),
				"TYPE" => "group",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => true,
				"PSORT" => 55,
			),
			"DETAIL_TEXT" => array(
				"NAME" => GetMessage("INTI_DETAIL_TEXT"),
				"FULL_NAME" => GetMessage("INTI_DETAIL_TEXT_F"),
				"TYPE" => "text",
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"IS_REQUIRED" => "N",
				"SELECTABLE" => true,
				"FILTERABLE" => false,
				"PSORT" => 70,
			),
		);

		$arTasksProps = array(
			"TASKPRIORITY" => array(
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKPRIORITY_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 100,
			),
			"TASKSTATUS" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKSTATUS_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 110,
			),
			"TASKCOMPLETE" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKCOMPLETE_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 130,
			),
			"TASKASSIGNEDTO" => array(
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => true,
				"TYPE" => "user",
				"LIST_NAME" => GetMessage("INTI_TASKASSIGNEDTO_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 50,
			),
			"TASKALERT" => array(
				"EDITABLE_AUTHOR" => true,
				"EDITABLE_RESPONSIBLE" => false,
				"TYPE" => "bool",
				"LIST_NAME" => GetMessage("INTI_TASKALERT_L"),
				"FILTERABLE" => false,
				"DISPLAY" => true,
				"PSORT" => 160,
			),
			"TASKSIZE" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKSIZE_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 140,
			),
			"TASKSIZEREAL" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKSIZEREAL_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 150,
			),
			"TASKFINISH" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"LIST_NAME" => GetMessage("INTI_TASKFINISH_L"),
				"FILTERABLE" => true,
				"DISPLAY" => true,
				"PSORT" => 130,
			),
			"TASKREPORT" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKREPORT_L"),
				"FILTERABLE" => false,
				"DISPLAY" => true,
				"PSORT" => 120,
			),
			"TASKREMIND" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => true,
				"LIST_NAME" => GetMessage("INTI_TASKREMIND_L"),
				"FILTERABLE" => false,
				"DISPLAY" => true,
				"PSORT" => 170,
			),
			"VERSION" => array(
				"EDITABLE_AUTHOR" => false,
				"EDITABLE_RESPONSIBLE" => false,
				"LIST_NAME" => GetMessage("INTI_VERSION_L"),
				"FILTERABLE" => false,
				"DISPLAY" => false,
				"PSORT" => 10,
			),
		);

		if (!Is_Array($arSelect))
			$arSelect = array();

		$arNeedBeSelected = array("ID", "NAME", "TIMESTAMP_X", "MODIFIED_BY", "DATE_CREATE", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "IBLOCK_SECTION", "DETAIL_TEXT", "TASKPRIORITY", "TASKSTATUS", "TASKCOMPLETE", "TASKASSIGNEDTO", "TASKALERT", "TASKSIZE", "TASKSIZEREAL", "TASKFINISH", "TASKREPORT", "TASKREMIND", 'VERSION');

		foreach ($arNeedBeSelected as $v)
		{
			if (!In_Array($v, $arSelect))
				$arSelect[] = $v;
		}

		$iblockId = IntVal($iblockId);
		if ($iblockId <= 0)
			return false;

		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
			return false;


		$arResult = array();

		$bArSelectIsEmpty = true;
		foreach ($arSelect as $field)
		{
			$field = StrToUpper(Trim($field));
			if (Array_Key_Exists($field, $arTasksFields))
			{
				$arResult[$field] = $arTasksFields[$field];
				$arResult[$field]["IS_FIELD"] = true;
				$bArSelectIsEmpty = false;
			}
		}

		if ($bArSelectIsEmpty)
		{
			foreach ($arTasksFields as $key => $value)
			{
				$arResult[$key] = $value;
				$arResult[$key]["IS_FIELD"] = true;
			}
		}

		if (Array_Key_Exists('IBLOCK_SECTION', $arResult))
		{
			$arResult['IBLOCK_SECTION']["IBLOCK_ID"] = $iblockId;
			$arResult['IBLOCK_SECTION']["ROOT_ID"] = (($taskType == "group") ? $ownerId : (($taskType == "user") ? "users_tasks" : ""));
		}

		$arTasksCustomProps = array();
		$dbTasksCustomProps = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockId)
		);
		while ($arTasksCustomProp = $dbTasksCustomProps->Fetch())
		{
			$ind = ((StrLen($arTasksCustomProp["CODE"]) > 0) ? $arTasksCustomProp["CODE"] : $arTasksCustomProp["ID"]);
			$arTasksCustomProps[StrToUpper($ind)] = $arTasksCustomProp;
			if (StrLen($arTasksCustomProp["USER_TYPE"]) > 0)
				$arTasksCustomProps[StrToUpper($ind)]["USER_TYPE_DETAILS"] = CIBlockProperty::GetUserType($arTasksCustomProp["USER_TYPE"]);
		}

		if (!$bArSelectIsEmpty)
		{
			foreach ($arSelect as $field)
			{
				$field = StrToUpper(Trim($field));
				if (Array_Key_Exists($field, $arTasksCustomProps))
				{
					$arResult[$field] = $arTasksCustomProps[$field];
					$arResult[$field]["IS_FIELD"] = false;
					$arResult[$field]["SELECTABLE"] = true;
					$arResult[$field]["FILTERABLE"] = ($arResult[$field]["FILTERABLE"] == "Y");
					if (Array_Key_Exists($field, $arTasksProps))
					{
						$arResult[$field]["FULL_NAME"] = $arResult[$field]["NAME"];
						$arResult[$field]["NAME"] = $arTasksProps[$field]["LIST_NAME"];

						foreach ($arTasksProps[$field] as $key => $value)
							$arResult[$field][$key] = $value;
					}
					else
					{
						$arResult[$field]["FULL_NAME"] = $arResult[$field]["NAME"];
						$arResult[$field]["EDITABLE_AUTHOR"] = true;
						$arResult[$field]["EDITABLE_RESPONSIBLE"] = true;
					}
				}
			}
		}
		else
		{
			foreach ($arTasksProps as $key => $value)
			{
				if (Array_Key_Exists($key, $arTasksCustomProps))
				{
					$arResult[$key] = $arTasksCustomProps[$key];
					$arResult[$key]["IS_FIELD"] = false;
					$arResult[$key]["SELECTABLE"] = true;
					$arResult[$key]["FILTERABLE"] = ($arResult[$field]["FILTERABLE"] == "Y");

					$arResult[$key]["FULL_NAME"] = $arResult[$key]["NAME"];
					$arResult[$key]["NAME"] = $value["LIST_NAME"];

					foreach ($value as $k => $v)
						$arResult[$key][$k] = $v;
				}
			}
		}

		foreach (array("TASKASSIGNEDTO", "TASKSTATUS") as $key)
		{
			if (!Array_Key_Exists($key, $arResult))
			{
				if (Array_Key_Exists($key, $arTasksCustomProps))
				{
					$arResult[$key] = $arTasksCustomProps[$key];
					$arResult[$key]["IS_FIELD"] = false;
					$arResult[$key]["SELECTABLE"] = true;
					$arResult[$key]["FILTERABLE"] = true;

					$arResult[$key]["FULL_NAME"] = $arResult[$key]["NAME"];
					$arResult[$key]["NAME"] = $arTasksProps[$key]["LIST_NAME"];

					foreach ($arTasksProps[$key] as $k => $v)
						$arResult[$key][$k] = $v;
				}
				else
				{
					return false;
				}
			}
		}

		return $arResult;
	}

	function __TaskPropertyUser($value)
	{
		$arReturn = array();

		if (!Is_Array($value))
			$value = array($value);

		foreach ($value as $val)
		{
			$dbUser = CUser::GetByID($val);
			if ($arUser = $dbUser->GetNext())
			{
				$name = Trim($arUser["NAME"]);
				$lastName = Trim($arUser["LAST_NAME"]);
				$login = Trim($arUser["LOGIN"]);

				$formatName = $name;
				if (StrLen($formatName) > 0 && StrLen($lastName) > 0)
					$formatName .= " ";
				$formatName .= $lastName;
				if (StrLen($formatName) <= 0)
					$formatName = $login;

				$arReturn[] = $formatName;//." [".$arUser["ID"]."]";
			}
		}

		return ((Count($arReturn) > 1) ? $arReturn : ((Count($arReturn) == 1) ? $arReturn[0] : ""));
	}

	function __TaskPropertySection($value, $iblockId, $mainIBlockId, &$arBaseSection)
	{
		$arReturn = array();
		$arBaseSection = array();

		if (!Is_Array($value))
			$value = array($value);

		foreach ($value as $val)
		{
			$dbSectionsList = CIBlockSection::GetNavChain($iblockId, $val);
			$ar = array();
			$bFirst = true;
			while ($arSection = $dbSectionsList->GetNext())
			{
				if ($bFirst)
					$arBaseSection[] = $arSection;
				if (!$bFirst || $mainIBlockId != $iblockId)
					$ar[$arSection["ID"]] = $arSection["NAME"];
				$bFirst = false;
			}

			$arReturn[] = $ar;
		}

		return $arReturn;
	}

	function __InTaskInitPerms($taskType, $ownerId)
	{
		$arResult = array(
			"view" => false,
			"view_all" => false,
			"create_tasks" => false,
			"edit_tasks" => false,
			"delete_tasks" => false,
			"modify_folders" => false,
			"modify_common_views" => false,
		);

		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
			return $arResult;

		if ($taskType == "group")
		{
			$arGroupTmp = CSocNetGroup::GetByID($ownerId);
			if ($arGroupTmp["CLOSED"] == "Y" && COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
				$HideArchiveLinks = true;
		}
		foreach ($arResult as $key => $val)
		{
			if (!$HideArchiveLinks && $GLOBALS["USER"]->IsAdmin())
				$arResult[$key] = true;
			else
				$arResult[$key] = CSocNetFeaturesPerms::CanPerformOperation(
					$GLOBALS["USER"]->GetID(),
					(($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP),
					$ownerId,
					"tasks",
					$key
				);
		}

		if ($HideArchiveLinks)
			$arResult["HideArchiveLinks"] = true;
		return $arResult;
	}

	function __InTaskCheckActiveFeature($taskType, $ownerId)
	{
		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
			return false;

		return CSocNetFeatures::IsActiveFeature((($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP), $ownerId, "tasks");
	}

	function __InTaskGetPropertyValue($arFields, $key)
	{
		$value = 0;

		if (Array_Key_Exists($key, $arFields))
		{
			if (Is_Array($arFields[$key]))
			{
				foreach ($arFields[$key] as $v)
				{
					$v = IntVal($v);
					if ($v > 0)
					{
						$value = $v;
						break;
					}
				}
			}
			else
			{
				$value = IntVal($arFields[$key]);
			}
		}

		return $value;
	}

	function __InTaskGetStringPropertyValue($arFields, $key)
	{
		$value = 0;

		if (Array_Key_Exists($key, $arFields))
		{
			if (Is_Array($arFields[$key]))
			{
				foreach ($arFields[$key] as $v)
				{
					if (is_array($v))
					{
						$value = $v["VALUE"];
						break;
					}
					elseif (StrLen($v) > 0)
					{
						$value = $v;
						break;
					}
				}
			}
			else
			{
				$value = $arFields[$key];
			}
		}

		return $value;
	}

	function __InTaskGetTask($taskId, $iblockId, $taskType, $ownerId)
	{
		$taskId = IntVal($taskId);
		$iblockId = IntVal($iblockId);
		$taskType = Trim($taskType);
		$ownerId = IntVal($ownerId);
		if ($taskId <= 0 || $iblockId <= 0 || $ownerId <= 0 || StrLen($taskType) <= 0)
			return false;

		$globalParentSection = 0;
		$dbSectionsList = CIBlockSection::GetList(
			array(),
			array(
				"GLOBAL_ACTIVE" => "Y",
				"EXTERNAL_ID" => (($taskType == "group") ? $ownerId : "users_tasks"),
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => 0
			),
			false
		);
		if ($arSection = $dbSectionsList->GetNext())
			$globalParentSection = $arSection["ID"];

		if ($globalParentSection <= 0)
			return false;

		$dbTasksList = CIBlockElement::GetList(
			array(),
			array(
				"SECTION_ID" => $globalParentSection,
				"INCLUDE_SUBSECTIONS" => "Y",
				"ID" => $taskId
			),
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "CODE", "XML_ID", "MODIFIED_BY", "DATE_CREATE", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "ACTIVE_DATE", "DETAIL_TEXT")
		);

		$arResult = false;
		if ($obTask = $dbTasksList->GetNextElement())
		{
			$arTaskFields = $obTask->GetFields();
			$arTaskProps = $obTask->GetProperties();

			$arTaskPropsTmp = array();
			foreach ($arTaskProps as $k => $v)
				$arTaskPropsTmp[StrToUpper($k)] = $v;
			$arTaskProps = $arTaskPropsTmp;

			$arResult = array(
				"FIELDS" => $arTaskFields,
				"PROPS" => $arTaskProps
			);
		}

		return $arResult;
	}

	function __InTaskPrepareIBlock($iblockId)
	{
		$iblockId = IntVal($iblockId);
		if ($iblockId <= 0)
			return;

		$dbIBlock = CIBlock::GetList(array(), array("ID" => $iblockId, "ACTIVE" => "Y"));
		if ($arIBlock = $dbIBlock->Fetch())
		{
			$arIBlockProperties = array();

			$dbIBlockProps = CIBlock::GetProperties($iblockId);
			while ($arIBlockProps = $dbIBlockProps->Fetch())
			{
				$ind = ((StrLen($arIBlockProps["CODE"]) > 0) ? $arIBlockProps["CODE"] : $arIBlockProps["ID"]);
				$arIBlockProperties[StrToUpper($ind)] = $arIBlockProps;
			}

			$arTasksProps = array(
				"TASKPRIORITY" => array(
					"NAME" => GetMessage("INTI_TASKPRIORITY"),
					"ACTIVE" => "Y",
					"SORT" => 100,
					"CODE" => "TaskPriority",
					"PROPERTY_TYPE" => "L",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "Y",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
					"VALUES" => array(
						array(
							"VALUE" => "   ".GetMessage("INTI_TASKPRIORITY_1"),
							"DEF" => "N",
							"SORT" => 100,
							"XML_ID" => "1"
						),
						array(
							"VALUE" => "  ".GetMessage("INTI_TASKPRIORITY_2"),
							"DEF" => "Y",
							"SORT" => 200,
							"XML_ID" => "2"
						),
						array(
							"VALUE" => " ".GetMessage("INTI_TASKPRIORITY_3"),
							"DEF" => "N",
							"SORT" => 300,
							"XML_ID" => "3"
						),
					),
				),
				"TASKSTATUS" => array(
					"NAME" => GetMessage("INTI_TASKSTATUS"),
					"ACTIVE" => "Y",
					"SORT" => 200,
					"CODE" => "TaskStatus",
					"PROPERTY_TYPE" => "L",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "Y",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
					"VALUES" => array(
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_1"),
							"DEF" => "Y",
							"SORT" => 100,
							"XML_ID" => "NotAccepted"
						),
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_2"),
							"DEF" => "N",
							"SORT" => 200,
							"XML_ID" => "NotStarted"
						),
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_3"),
							"DEF" => "N",
							"SORT" => 300,
							"XML_ID" => "InProgress"
						),
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_4"),
							"DEF" => "N",
							"SORT" => 400,
							"XML_ID" => "Completed"
						),
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_5"),
							"DEF" => "N",
							"SORT" => 500,
							"XML_ID" => "Waiting"
						),
						array(
							"VALUE" => GetMessage("INTI_TASKSTATUS_6"),
							"DEF" => "N",
							"SORT" => 600,
							"XML_ID" => "Deferred"
						),
					),
				),
				"TASKCOMPLETE" => array(
					"NAME" => GetMessage("INTI_TASKCOMPLETE"),
					"ACTIVE" => "Y",
					"SORT" => 300,
					"CODE" => "TaskComplete",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKASSIGNEDTO" => array(
					"NAME" => GetMessage("INTI_TASKASSIGNEDTO"),
					"ACTIVE" => "Y",
					"SORT" => 400,
					"CODE" => "TaskAssignedTo",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "UserID",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "Y",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKALERT" => array(
					"NAME" => GetMessage("INTI_TASKALERT"),
					"ACTIVE" => "Y",
					"SORT" => 500,
					"CODE" => "TaskAlert",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => false,
					"DEFAULT_VALUE" => "Y",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKSIZE" => array(
					"NAME" => GetMessage("INTI_TASKSIZE"),
					"ACTIVE" => "Y",
					"SORT" => 600,
					"CODE" => "TaskSize",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKSIZEREAL" => array(
					"NAME" => GetMessage("INTI_TASKSIZEREAL"),
					"ACTIVE" => "Y",
					"SORT" => 700,
					"CODE" => "TaskSizeReal",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKFINISH" => array(
					"NAME" => GetMessage("INTI_TASKFINISH"),
					"ACTIVE" => "Y",
					"SORT" => 800,
					"CODE" => "TaskFinish",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "DateTime",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKFILES" => array(
					"NAME" => GetMessage("INTI_TASKFILES"),
					"ACTIVE" => "Y",
					"SORT" => 900,
					"CODE" => "TaskFiles",
					"PROPERTY_TYPE" => "F",
					"USER_TYPE" => false,
					"ROW_COUNT" => 10,
					"COL_COUNT" => 60,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "Y",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKREPORT" => array(
					"NAME" => GetMessage("INTI_TASKREPORT"),
					"ACTIVE" => "Y",
					"SORT" => 1000,
					"CODE" => "TaskReport",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => false,
					"ROW_COUNT" => 10,
					"COL_COUNT" => 60,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKREMIND" => array(
					"NAME" => GetMessage("INTI_TASKREMIND"),
					"ACTIVE" => "Y",
					"SORT" => 300,
					"CODE" => "TaskRemind",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "DateTime",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"VERSION" => array(
					"NAME" => GetMessage("INTI_VERSION"),
					"ACTIVE" => "Y",
					"SORT" => 1100,
					"CODE" => "VERSION",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 10,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
			);

			foreach ($arTasksProps as $propKey => $arProp)
			{
				if (!Array_Key_Exists($propKey, $arIBlockProperties))
				{
					$ibp = new CIBlockProperty;
					$ibp->Add($arProp);
				}
			}
		}
	}

	function __InTaskInstallViews($iblockId, $taskType, $ownerId)
	{
		$iblockId = IntVal($iblockId);
		$ownerId = IntVal($ownerId);
		if (!In_Array($taskType, array("user", "group")))
			$taskType = "user";

		$newID = 0;

		$dbUserOptionsList = CUserOptions::GetList(
			array("ID" => "DESC"),
			array()
		);
		if ($arUserOptionTmp = $dbUserOptionsList->Fetch())
			$newID = IntVal($arUserOptionTmp["ID"]);

		$arTaskStatus = array();
		$dbRes = CIBlockProperty::GetPropertyEnum("TASKSTATUS", Array("SORT" => "ASC"), Array("IBLOCK_ID" => $iblockId));
		while ($arRes = $dbRes->Fetch())
			$arTaskStatus[StrToUpper($arRes["XML_ID"])] = $arRes;

		if ($taskType == "group")
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_BY_PRIORITY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"CREATED_BY" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"TASKASSIGNEDTO" => 3,
						"DATE_ACTIVE_FROM" => 4,
						"DATE_ACTIVE_TO" => 5,
						"TASKSTATUS" => 6,
						"TASKCOMPLETE" => 7,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_TODAY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"<DATE_ACTIVE_FROM" => "current",
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);
		}
		elseif ($taskType == "user" && $ownerId == $GLOBALS["USER"]->GetID())
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_BY_PRIORITY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"CREATED_BY" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"TASKASSIGNEDTO" => 3,
						"DATE_ACTIVE_FROM" => 4,
						"DATE_ACTIVE_TO" => 5,
						"TASKSTATUS" => 6,
						"TASKCOMPLETE" => 7,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_TODAY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"<DATE_ACTIVE_FROM" => "current",
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$userIBlockSectionId = 0;
			$dbSectionsList = CIBlockSection::GetList(
				array(),
				array(
					"GLOBAL_ACTIVE" => "Y",
					"EXTERNAL_ID" => "users_tasks",
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => 0
				),
				false
			);
			if ($arSection = $dbSectionsList->GetNext())
				$userIBlockSectionId = $arSection["ID"];

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_PERSONAL"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => $ownerId,
						"IBLOCK_SECTION" => $userIBlockSectionId,
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);
		}
		elseif ($taskType == "user")
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$userIBlockSectionId = 0;
			$dbSectionsList = CIBlockSection::GetList(
				array(),
				array(
					"GLOBAL_ACTIVE" => "Y",
					"EXTERNAL_ID" => "users_tasks",
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => 0
				),
				false
			);
			if ($arSection = $dbSectionsList->GetNext())
				$userIBlockSectionId = $arSection["ID"];

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_PERSONAL"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => $ownerId,
						"IBLOCK_SECTION" => $userIBlockSectionId,
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);
		}
	}

	function __InTaskCompare($arTask1, $arTask2, $arOrder)
	{
		foreach ($arOrder as $ord)
		{
			if (Array_Key_Exists($ord["ORDER"], $arTask1["FIELDS"]))
			{
				$v1 = $arTask1["FIELDS"][$ord["ORDER"]];
				$v2 = $arTask2["FIELDS"][$ord["ORDER"]];
				if ($ord["TYPE"] == "datetime")
				{
					$v1 = CDatabase::FormatDate($v1, CLang::GetDateFormat("FULL"), "YYYY-MM-DD HH:MI:SS");
					$v2 = CDatabase::FormatDate($v2, CLang::GetDateFormat("FULL"), "YYYY-MM-DD HH:MI:SS");
				}

				if ($ord["NULLS"])
				{
					if (StrLen($v1) <= 0 && StrLen($v2) > 0)
						return (($ord["DIRECTION"] == "ASC") ? true : false);
					elseif (StrLen($v1) > 0 && StrLen($v2) <= 0)
						return (($ord["DIRECTION"] == "ASC") ? false : true);
				}

				if ($v1 > $v2)
					return (($ord["DIRECTION"] == "ASC") ? true : false);
				elseif ($v1 < $v2)
					return (($ord["DIRECTION"] == "ASC") ? false : true);
			}
			else
			{
				foreach ($arTask1["PROPS"] as $key => $value)
				{
					if (StrToUpper($key) == $ord["ORDER"])
					{
						$v1 = $value["VALUE"];
						$v2 = $arTask2["PROPS"][$key]["VALUE"];
						if ($ord["TYPE"] == "datetime")
						{
							$v1 = CDatabase::FormatDate($v1, CLang::GetDateFormat("FULL"), "YYYY-MM-DD HH:MI:SS");
							$v2 = CDatabase::FormatDate($v2, CLang::GetDateFormat("FULL"), "YYYY-MM-DD HH:MI:SS");
						}

						if ($ord["NULLS"])
						{
							if (StrLen($v1) <= 0 && StrLen($v2) > 0)
								return (($ord["DIRECTION"] == "ASC") ? true : false);
							elseif (StrLen($v1) > 0 && StrLen($v2) <= 0)
								return (($ord["DIRECTION"] == "ASC") ? false : true);
						}

						if ($v1 > $v2)
							return (($ord["DIRECTION"] == "ASC") ? true : false);
						elseif ($v1 < $v2)
							return (($ord["DIRECTION"] == "ASC") ? false : true);

						break;
					}
				}
			}
		}

		return true;
	}

	function __InTaskAdd2Log($entityType, $entityId, $type, $title, $message, $url)
	{
		if ($type == "add")
			$template = GetMessage("INTASK_2LOG_ADD");
		elseif ($type == "update")
			$template = GetMessage("INTASK_2LOG_UPDATE");
		elseif ($type == "delete")
			$template = GetMessage("INTASK_2LOG_DELETE");

		$logID = CSocNetLog::Add(
			array(
				"ENTITY_TYPE" 		=> $entityType,
				"ENTITY_ID" 		=> $entityId,
				"EVENT_ID" 			=> "tasks",
				"=LOG_DATE" 		=> $GLOBALS["DB"]->CurrentTimeFunction(),
				"TITLE_TEMPLATE" 	=> $template,
				"TITLE" 			=> $title,
				"MESSAGE"			=> nl2br($message),
				"TEXT_MESSAGE" 		=> $message,
				"URL" 				=> $url,
				"MODULE_ID" 		=> false,
				"CALLBACK_FUNC" 	=> false
			)
		);
		if (intval($logID) > 0)
			CSocNetLog::Update($logID, array("TMP_ID" => $logID));
	}
}
?>