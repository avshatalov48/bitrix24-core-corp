<?
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("bizproc"))
	return;
if (!CModule::IncludeModule("iblock"))
	return;
if (!CModule::IncludeModule("socialnetwork"))
	return;

define("INTASK_DOCUMENT_OPERATION_VIEW_WORKFLOW", 0);
define("INTASK_DOCUMENT_OPERATION_START_WORKFLOW", 1);
define("INTASK_DOCUMENT_OPERATION_CREATE_WORKFLOW", 4);

define("INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT", 2);
define("INTASK_DOCUMENT_OPERATION_READ_DOCUMENT", 3);
define("INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT", 100);
define("INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT", 101);

class CIntranetTasksDocument
	extends CIBlockDocument
{
	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param string $documentId - код документа.
	* @return string - ссылка на страницу документа в административной части.
	*/
	public function GetDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			throw new CBPArgumentNullException("iblockId");

		$db = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "IBLOCK_SECTION_ID", "PROPERTY_TASKASSIGNEDTO")
		);
		if ($ar = $db->Fetch())
		{
			$dbSectionsChain = CIBlockSection::GetNavChain($ar["IBLOCK_ID"], $ar["IBLOCK_SECTION_ID"]);
			if ($arSect = $dbSectionsChain->Fetch())
			{
				if ($arSect["XML_ID"] == "users_tasks")
				{
					return str_replace(
						array("#USER_ID#", "#TASK_ID#"),
						array($ar["PROPERTY_TASKASSIGNEDTO_VALUE"], $documentId),
						COption::GetOptionString("intranet", "path_task_user_entry", "/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/")
					);
				}
				else
				{
					return str_replace(
						array("#GROUP_ID#", "#TASK_ID#"),
						array($arSect["XML_ID"], $documentId),
						COption::GetOptionString("intranet", "path_task_group_entry", "/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/")
					);
				}
			}
		}

		return null;
	}

	public function GetDocument($documentId, $nameTemplate = false, $bShowLogin = true, $bShowTooltip = false, $arTooltipParams = false)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$isInSecurity = CModule::IncludeModule("security");
		$arResult = false;

		$dbResult = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId));
		if ($objResult = $dbResult->GetNextElement())
		{
			$arResult = array();

			$arFields = $objResult->GetFields();
			foreach ($arFields as $fieldKey => $fieldValue)
			{
				if (substr($fieldKey, 0, 1) == "~")
					continue;

				$arResult[$fieldKey] = $fieldValue;

				if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
				{
					$arResult[$fieldKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($fieldValue, $nameTemplate, $bShowLogin, $bShowTooltip, $arTooltipParams);
				}
				elseif ($fieldKey == "DETAIL_TEXT")
				{
					if ($isInSecurity)
					{
						$filter = new CSecurityFilter;
						$arResult["DETAIL_TEXT_PRINTABLE"] = $filter->TestXSS(
							$arFields["~DETAIL_TEXT_TYPE"] == "text" ? $arFields["DETAIL_TEXT"] : $arFields["~DETAIL_TEXT"],
							'replace'
						);

						$arResult["DETAIL_TEXT"] = ($arFields["~DETAIL_TEXT_TYPE"] == "text" ? nl2br($arFields["~DETAIL_TEXT"]) : $arFields["~DETAIL_TEXT"]);
					}
					else
					{
						$arResult["DETAIL_TEXT_PRINTABLE"] = nl2br($arFields["DETAIL_TEXT"]);
						$arResult["DETAIL_TEXT"] = $arFields["DETAIL_TEXT"];
					}
				}
				else
				{
					$arResult[$fieldKey."_PRINTABLE"] = $fieldValue;
				}
			}

			$arProperties = $objResult->GetProperties();
			foreach ($arProperties as $propertyKey => $propertyValue)
			{
				if (is_array($propertyValue["VALUE"]))
				{
					$arResult["PROPERTY_".$propertyKey] = array();
					foreach ($propertyValue["VALUE"] as $k => $v)
						$arResult["PROPERTY_".$propertyKey][$propertyValue["PROPERTY_VALUE_ID"][$k]] = $v;
				}
				else
				{
					$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
				}
				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $propertyValue["VALUE"];

				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					if ($propertyValue["USER_TYPE"] == "UserID")
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($propertyValue["VALUE"], $nameTemplate, $bShowLogin, $bShowTooltip, $arTooltipParams);
					else
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $propertyValue["VALUE"];
				}
				elseif ($arField["PROPERTY_TYPE"] == "G")
				{
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = array();
					$vx = CIntranetTasks::PrepareSectionForPrint($propertyValue["VALUE"], $propertyValue["LINK_IBLOCK_ID"]);
					foreach ($vx as $vx1 => $vx2)
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$vx1] = $vx2["NAME"];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTY_".$propertyKey] = array();

					$arPropertyValue = $propertyValue["VALUE"];
					$arPropertyKey = $propertyValue["VALUE_ENUM_ID"];
					if (!is_array($arPropertyValue))
					{
						$arPropertyValue = array($arPropertyValue);
						$arPropertyKey = array($arPropertyKey);
					}

					for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
						$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = $arPropertyValue[$i];

					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $arResult["PROPERTY_".$propertyKey];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "S" && $propertyValue["ROW_COUNT"] > 1)
				{
					if (is_array($propertyValue["VALUE"]))
					{
						$arResult["PROPERTY_".$propertyKey] = array();
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = array();

						if ($isInSecurity)
						{
							foreach ($propertyValue["~VALUE"] as $k => $v)
							{
								$filter = new CSecurityFilter;
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k] = $filter->TestXSS($v, 'replace');
								$arResult["PROPERTY_".$propertyKey][$k] = $arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k];
							}
						}
						else
						{
							foreach ($propertyValue["VALUE"] as $k => $v)
							{
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k] = nl2br($v);
								$arResult["PROPERTY_".$propertyKey][$k] = $v;
							}
						}
					}
					else
					{
						if ($isInSecurity)
						{
							$filter = new CSecurityFilter;
							$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $filter->TestXSS($propertyValue["~VALUE"], 'replace');
							$arResult["PROPERTY_".$propertyKey] = $arResult["PROPERTY_".$propertyKey."_PRINTABLE"];
						}
						else
						{
							$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = nl2br($propertyValue["VALUE"]);
							$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
						}
					}
				}
			}

			$arResult["ROOT_SECTION_ID"] = 0;
			$arResult["IBLOCK_SECTION_ID_PRINTABLE"] = array();
			$v = CIntranetTasks::PrepareSectionForPrint($arResult["IBLOCK_SECTION_ID"]);
			foreach ($v as $k1 => $v1)
			{
				if ($arResult["ROOT_SECTION_ID"] == 0)
				{
					$arResult["ROOT_SECTION_ID"] = $k1;
					$arResult["TaskType"] = ($v1["XML_ID"] == "users_tasks" ? "user" : "group");
					$arResult["OwnerId"] = ($arResult["TaskType"] == "user" ? $arResult["PROPERTY_TaskAssignedTo"] : $v1["XML_ID"]);
				}
				else
				{
					$arResult["IBLOCK_SECTION_ID_PRINTABLE"][$k1] = $v1["NAME"];
				}
			}
		}

		return $arResult;
	}

	/**
	* Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод GetDocument возвращает значения свойств для заданного документа.
	*
	* @param string $documentType - тип документа.
	* @return array - массив свойств вида array(код_свойства => array("NAME" => название_свойства, "TYPE" => тип_свойства), ...).
	*/
	public function GetDocumentFields($documentType)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			throw new CBPArgumentNullException("iblockId");

		$ar = explode("_", $documentType);

		$arAllowedSections = array();

		$flag = 0;
		$dbSections = CIBlockSection::GetTreeList(array("IBLOCK_ID" => $iblockId));
		while ($arSections = $dbSections->GetNext())
		{
			if ($flag == 0)
			{
				if ($ar[0] == "user" && $arSections["EXTERNAL_ID"] != "users_tasks" || $ar[0] == "group" && $arSections["EXTERNAL_ID"] != $ar[1])
					continue;

				$flag = $arSections["DEPTH_LEVEL"];
				continue;
			}
			else
			{
				if ($flag == $arSections["DEPTH_LEVEL"])
					break;
			}

			$arAllowedSections[$arSections["ID"]] = str_repeat(" . ", $arSections["DEPTH_LEVEL"] - $flag).$arSections["NAME"];
		}

		$arResult = array(
			"ID" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_TIMESTAMP_X"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_MODIFIED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_CREATED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_SECTION_ID" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_IBLOCK_SECTION_ID"),
				"Type" => "select",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Options" => $arAllowedSections,
			),
			"ACTIVE_FROM" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_DATE_ACTIVE_FROM"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE_TO" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_DATE_ACTIVE_TO"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"NAME" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_NAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
			),
			"DETAIL_TEXT" => array(
				"Name" => GetMessage("INTASK_TD_FIELD_DETAIL_TEXT"),
				"Type" => "text",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
		);

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $key)
			$arResult[$key]["Multiple"] = false;

		$dbProperties = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $iblockId)
		);
		while ($arProperty = $dbProperties->Fetch())
		{
			if (in_array($arProperty["CODE"], array("TaskStatus", "TaskAlert", "VERSION", "FORUM_TOPIC_ID", "FORUM_MESSAGE_CNT", "TASKVERSION")))
				continue;

			if (strlen(trim($arProperty["CODE"])) > 0)
				$key = "PROPERTY_".$arProperty["CODE"];
			else
				$key = "PROPERTY_".$arProperty["ID"];

			$arResult[$key] = array(
				"Name" => $arProperty["NAME"],
				"Filterable" => ($arProperty["FILTRABLE"] == "Y"),
				"Editable" => true,
				"Required" => ($arProperty["IS_REQUIRED"] == "Y"),
				"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
			);

			if (strlen($arProperty["USER_TYPE"]) > 0)
			{
				if ($arProperty["USER_TYPE"] == "UserID")
					$arResult[$key]["Type"] = "user";
				elseif ($arProperty["USER_TYPE"] == "DateTime")
					$arResult[$key]["Type"] = "datetime";
				else
					$arResult[$key]["Type"] = "string";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arResult[$key]["Type"] = "select";

				$arResult[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::GetPropertyEnum($arProperty["ID"]);
				while ($arPropertyEnum = $dbPropertyEnums->GetNext())
					$arResult[$key]["Options"][$arPropertyEnum["ID"]] = $arPropertyEnum["VALUE"];
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "N")
			{
				$arResult[$key]["Type"] = "int";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "F")
			{
				$arResult[$key]["Type"] = "file";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "S")
			{
				if ($arProperty["ROW_COUNT"] > 1)
					$arResult[$key]["Type"] = "text";
				else
					$arResult[$key]["Type"] = "string";
			}
			else
			{
				$arResult[$key]["Type"] = "string";
			}
		}

		return $arResult;
	}


	public function CreateDocument($arFields)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$arFields["ACTIVE"] = "Y";
		$arFields["BP_PUBLISHED"] = "Y";
		$arFields["IBLOCK_ID"] = $iblockId;
		$arFields["DETAIL_TEXT_TYPE"] = (CModule::IncludeModule("security") ? "html" : "text");

		$arPropertyValues = array();

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_")
			{
				$arPropertyValues[substr($key, strlen("PROPERTY_"))] = $arFields[$key];
				unset($arFields[$key]);
			}
		}

		$arFields["PROPERTY_VALUES"] = $arPropertyValues;
		$arFields["PROPERTY_VALUES"]["TASKVERSION"] = 2;

		$iblockElement = new CIBlockElement();
		$id = $iblockElement->Add($arFields);
		if (!$id || $id <= 0)
			throw new Exception($iblockElement->LAST_ERROR);

		CIBlockElement::RecalcSections($id);

		return $id;
	}

	/**
	* Метод изменяет свойства (поля) указанного документа на указанные значения.
	*
	* @param string $documentId - код документа.
	* @param array $arFields - массив новых значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	*/
	public function UpdateDocument($documentId, $arFields)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		CIBlockElement::WF_CleanUpHistoryCopies($documentId, 0);

		$db = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW" => "Y"),
			false,
			false,
			array("ID", "IBLOCK_ID")
		);
		$ar = $db->Fetch();
		if (!$ar)
			throw new Exception("Task is not found");

		$arFields["PROPERTY_VALUES"] = array();

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_")
				$arFields["PROPERTY_VALUES"][substr($key, strlen("PROPERTY_"))] = $arFields[$key];
		}

		$iblockElementObject = new CIBlockElement();

		if (count($arFields["PROPERTY_VALUES"]) > 0)
			$iblockElementObject->SetPropertyValuesEx($documentId, $ar["IBLOCK_ID"], $arFields["PROPERTY_VALUES"]);

		UnSet($arFields["PROPERTY_VALUES"]);

		$res = $iblockElementObject->Update($documentId, $arFields);
		if (!$res)
			throw new Exception($iblockElement->LAST_ERROR);

		CIBlockElement::RecalcSections($documentId);
	}

	/**
	* Метод проверяет права на выполнение операций над заданным документом. Проверяются операции 0 - просмотр данных рабочего потока, 1 - запуск рабочего потока, 2 - право изменять документ, 3 - право смотреть документ.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$userId = intval($userId);

		global $USER;
		if ($USER->IsAuthorized() && $USER->GetID() == $userId && CSocNetUser::IsCurrentUserModuleAdmin())
			return true;

		if (!array_key_exists("AllUserGroups", $arParameters)
			&& (!array_key_exists("Author", $arParameters)
				|| !array_key_exists("Responsible", $arParameters)
				|| !array_key_exists("Trackers", $arParameters)
				)
			|| !array_key_exists("TaskType", $arParameters)
			|| !array_key_exists("OwnerId", $arParameters)
			)
		{
			$dbElementList = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY", "IBLOCK_SECTION_ID", "PROPERTY_TASKASSIGNEDTO")
			);
			$arElement = $dbElementList->Fetch();

			if (!$arElement)
				return false;

			$arParameters["Author"] = $arElement["CREATED_BY"];
			$arParameters["Responsible"] = $arElement["PROPERTY_TASKASSIGNEDTO_VALUE"];

			if (!array_key_exists("Trackers", $arParameters))
			{
				$arParameters["Trackers"] = array();
				$dbElementPropValueList = CIBlockElement::GetProperty($iblockId, $documentId, "sort", "asc", array("CODE"=>"TASKTRACKERS"));
				while ($arElementPropValue = $dbElementPropValueList->Fetch())
					$arParameters["Trackers"][] = $arElementPropValue["VALUE"];
			}

			if (!array_key_exists("TaskType", $arParameters) || !array_key_exists("OwnerId", $arParameters))
			{
				$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $arElement["IBLOCK_SECTION_ID"]);
				if ($arSect = $dbSectionsChain->Fetch())
				{
					$arParameters["TaskType"] = (($arSect["XML_ID"] == "users_tasks") ? "user" : "group");
					$arParameters["OwnerId"] = IntVal(($arParameters["TaskType"] == "user") ?  $arParameters["Responsible"] : $arSect["XML_ID"]);
				}
			}
		}

		if ($arParameters["TaskType"] == "user" && $arParameters["OwnerId"] == $userId)
			return true;

		$o = "";
		if ($operation == INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT)
			$o = "edit_tasks";
		elseif ($operation == INTASK_DOCUMENT_OPERATION_READ_DOCUMENT || $operation == INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT)
			$o = "view_all";
		elseif ($operation == INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT)
			$o = "delete_tasks";

		if (strlen($o) > 0)
		{
			$r = CSocNetFeaturesPerms::CanPerformOperation(
				$userId,
				(($arParameters["TaskType"] == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP),
				$arParameters["OwnerId"],
				"tasks",
				$o
			);
			if ($r)
				return true;
		}

		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
			{
				$arParameters["UserGroups"] = array();

				if ($arParameters["TaskType"] == "group")
				{
					$arParameters["UserGroups"][] = SONET_ROLES_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arParameters["UserGroups"][] = SONET_ROLES_AUTHORIZED;

					$r = CSocNetUserToGroup::GetUserRole($userId, $arParameters["OwnerId"]);
					if (strlen($r) > 0)
						$arParameters["UserGroups"][] = $r;
				}
				else
				{
//					$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_ALL;
//					if (CSocNetUserRelations::IsFriends($userId, $arParameters["OwnerId"]))
//						$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_FRIENDS;
//					elseif (CSocNetUserRelations::IsFriends2($userId, $arParameters["OwnerId"]))
//						$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_FRIENDS2;
				}
			}

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			if ($userId == $arParameters["Author"])
				$arParameters["AllUserGroups"][] = "author";
			if ($userId == $arParameters["Responsible"])
				$arParameters["AllUserGroups"][] = "responsible";
			if (in_array($userId, $arParameters["Trackers"]))
				$arParameters["AllUserGroups"][] = "trackers";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("intranet", "CIntranetTasksDocument", "x".$iblockId),
				array("intranet", "CIntranetTasksDocument", $documentId)
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции
		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case INTASK_DOCUMENT_OPERATION_VIEW_WORKFLOW:
				$r = ($USER->IsAuthorized() && CSocNetUser::IsCurrentUserModuleAdmin());
				break;
			case INTASK_DOCUMENT_OPERATION_START_WORKFLOW:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_CREATE_WORKFLOW:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT:
				$r = in_array("write", $arAllowableOperations);
				break;
			case INTASK_DOCUMENT_OPERATION_READ_DOCUMENT:
				$r = in_array("read", $arAllowableOperations) || in_array("write", $arAllowableOperations) || in_array("comment", $arAllowableOperations);
				break;
			case INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT:
				$r = in_array("comment", $arAllowableOperations) || in_array("write", $arAllowableOperations);
				break;
			case INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT:
				$r = in_array("delete", $arAllowableOperations);
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	* Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции 4 - право изменять шаблоны рабочий потоков для данного типа документа.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код типа документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	public function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$userId = intval($userId);

		global $USER;
		if ($USER->IsAuthorized() && $USER->GetID() == $userId && CSocNetUser::IsCurrentUserModuleAdmin())
			return true;

		$arDt = explode("_", $documentType);
		if (count($arDt) != 2)
			return false;

		$taskType = $arDt[0];
		$ownerId = intval($arDt[1]);

		if (!in_array($taskType, array("user", "group")) || $ownerId <= 0)
			return false;

		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
			{
				$arParameters["UserGroups"] = array();

				if ($taskType == "user")
				{
//					$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_ALL;
//					if (CSocNetUserRelations::IsFriends($userId, $ownerId))
//						$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_FRIENDS;
//					elseif (CSocNetUserRelations::IsFriends2($userId, $ownerId))
//						$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_FRIENDS2;
				}
				else
				{
					
					$arParameters["UserGroups"][] = SONET_ROLES_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arParameters["UserGroups"][] = SONET_ROLES_AUTHORIZED;

					$r = CSocNetUserToGroup::GetUserRole($userId, $ownerId);
					if (strlen($r) > 0)
						$arParameters["UserGroups"][] = $r;
				}
			}

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			$arParameters["AllUserGroups"][] = "author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("intranet", "CIntranetTasksDocument", "x".$iblockId),
				null
			);
		}

		// Если нужно проверить только для одного рабочего потока
		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции
		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case INTASK_DOCUMENT_OPERATION_VIEW_WORKFLOW:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_START_WORKFLOW:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_CREATE_WORKFLOW:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT:
				$r = in_array("write", $arAllowableOperations);
				break;
			case INTASK_DOCUMENT_OPERATION_READ_DOCUMENT:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT:
				$r = false;
				break;
			case INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT:
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	* Метод удаляет указанный документ.
	*
	* @param string $documentId - код документа.
	*/
	public function DeleteDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		CIBlockElement::Delete($documentId);

		CBPDocument::OnDocumentDelete(array("intranet", "CIntranetTasksDocument", $documentId), $arError);
		if (count($arError) > 0)
			throw new Exception($arError[0]["message"]);
	}

	// array("read" => "Ета чтение", "write" => "Ета запысь")
	public function GetAllowableOperations($documentType)
	{
		return array("read" => GetMessage("INTASK_TD_OPERATIONS_READ"), "write" => GetMessage("INTASK_TD_OPERATIONS_WRITE"), "comment" => GetMessage("INTASK_TD_OPERATIONS_COMMENT"), "delete" => GetMessage("INTASK_TD_OPERATIONS_DELETE"));
	}

	// array("1" => "Админы", 2 => "Гости", 3 => ..., "Author" => "Афтар")
	public function GetAllowableUserGroups($documentType)
	{
		$arResult = array(
			"author" => GetMessage("INTASK_TD_USER_GROUPS_AUTHOR"),
			"responsible" => GetMessage("INTASK_TD_USER_GROUPS_RESP"),
			"trackers" => GetMessage("INTASK_TD_USER_GROUPS_TRACK"),
		);

		if ($documentType == "user")
		{
			$arResult[SONET_RELATIONS_TYPE_FRIENDS] = GetMessage("INTASK_TD_USER_GROUPS_FRIEND");
			$arResult[SONET_RELATIONS_TYPE_FRIENDS2] = GetMessage("INTASK_TD_USER_GROUPS_FRIEND2");
			$arResult[SONET_RELATIONS_TYPE_AUTHORIZED] = GetMessage("INTASK_TD_USER_GROUPS_AUTHORIZED");
			$arResult[SONET_RELATIONS_TYPE_ALL] = GetMessage("INTASK_TD_USER_GROUPS_ALL");
		}
		else
		{
			$arResult[SONET_ROLES_OWNER] = GetMessage("INTASK_TD_USER_GROUPS_OWNER");
			$arResult[SONET_ROLES_MODERATOR] = GetMessage("INTASK_TD_USER_GROUPS_MODS");
			$arResult[SONET_ROLES_USER] = GetMessage("INTASK_TD_USER_GROUPS_MEMBERS");
			$arResult[SONET_ROLES_AUTHORIZED] = GetMessage("INTASK_TD_USER_GROUPS_AUTHORIZED");
			$arResult[SONET_ROLES_ALL] = GetMessage("INTASK_TD_USER_GROUPS_ALL");
		}

		return $arResult;
	}

	public function GetUsersFromUserGroup($group, $documentId)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);

		$documentId = intval($documentId);
		if ($documentId <= 0)
			return array();

		$group = strtolower($group);
		if ($group == "author")
		{
			$db = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY")
			);
			if ($ar = $db->Fetch())
				return array($ar["CREATED_BY"]);

			return array();
		}
		elseif ($group == "responsible")
		{
			$db = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId),
				false,
				false,
				array("ID", "IBLOCK_ID", "PROPERTY_TASKASSIGNEDTO")
			);
			if ($ar = $db->Fetch())
				return array($ar["PROPERTY_TASKASSIGNEDTO_VALUE"]);

			return array();
		}
		elseif ($group == "trackers")
		{
			$arR = array();
			$dbElementPropValueList = CIBlockElement::GetProperty($iblockId, $documentId, "sort", "asc", array("CODE" => "TASKTRACKERS"));
			while ($arElementPropValue = $dbElementPropValueList->Fetch())
				$arR[] = $arElementPropValue["VALUE"];

			return $arR;
		}

		$arR = array();

		$dbTaskList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW" => "Y", "IBLOCK_ID" => $iblockId),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PROPERTY_TASKASSIGNEDTO")
		);
		if ($arTask = $dbTaskList->Fetch())
		{
			$dbSectionsChain = CIBlockSection::GetNavChain($arTask["IBLOCK_ID"], $arTask["IBLOCK_SECTION_ID"]);
			if ($arSect = $dbSectionsChain->Fetch())
			{
				if ($arSect["XML_ID"] == "users_tasks")
				{
					if ($group == strtolower(SONET_RELATIONS_TYPE_FRIENDS))
					{
						$db = CSocNetUserRelations::GetRelatedUsers($arTask["PROPERTY_TASKASSIGNEDTO_VALUE"], SONET_RELATIONS_TYPE_FRIENDS);
						while ($ar = $db->Fetch())
						{
							if ($ar["FIRST_USER_ID"] == $arTask["PROPERTY_TASKASSIGNEDTO_VALUE"])
								$arR[] = $ar["SECOND_USER_ID"];
							else
								$arR[] = $ar["FIRST_USER_ID"];
						}
					}
				}
				else
				{
					if ($group == strtolower(SONET_ROLES_OWNER))
					{
						$arGroup = CSocNetGroup::GetByID($arSect["XML_ID"]);
						if ($arGroup)
							$arR[] = $arGroup["OWNER_ID"];
					}
					elseif ($group == strtolower(SONET_ROLES_MODERATOR))
					{
						$db = CSocNetUserToGroup::GetList(
							array(),
							array(
								"GROUP_ID" => $arSect["XML_ID"],
								"<=ROLE" => SONET_ROLES_MODERATOR,
								"USER_ACTIVE" => "Y"
							),
							false,
							false,
							array("USER_ID")
						);
						while ($ar = $db->Fetch())
							$arR[] = $ar["USER_ID"];
					}
					elseif ($group == strtolower(SONET_ROLES_USER))
					{
						$db = CSocNetUserToGroup::GetList(
							array(),
							array(
								"GROUP_ID" => $arSect["XML_ID"],
								"<=ROLE" => SONET_ROLES_USER,
								"USER_ACTIVE" => "Y"
							),
							false,
							false,
							array("USER_ID")
						);
						while ($ar = $db->Fetch())
							$arR[] = $ar["USER_ID"];
					}
				}
			}
		}

		return $arR;
	}
}
?>