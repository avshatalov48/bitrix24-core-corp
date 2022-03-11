<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */

if (!$USER->CanDoOperation("controller_member_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$entity_id = "CONTROLLER_MEMBER";
$sTableID = "t_controll_admin";
$oSort = new CAdminUiSorting($sTableID, "id", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminUiList($sTableID, $oSort);

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
while ($ar_groups = $dbr_groups->Fetch())
{
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];
}

$filterFields = array(
	array(
		"id" => "NAME",
		"name" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_NAME"),
		"filterable" => "%",
		"default" => true,
	),
	array(
		"id" => "ID",
		"name" => "ID",
		"filterable" => "",
		"default" => true,
	),
	array(
		"id" => "URL",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_URL"),
		"filterable" => "%",
	),
	array(
		"id" => "CONTROLLER_GROUP_ID",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_GROUP"),
		"type" => "list",
		"items" => $arGroups,
		"params" => array("multiple" => "Y"),
		"filterable" => "=",
	),
	array(
		"id" => "MEMBER_ID",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_UNIQID"),
		"filterable" => "%",
	),
	array(
		"id" => "ACTIVE",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACTIVE"),
		"type" => "list",
		"items" => array(
			"Y" => GetMessage("MAIN_YES"),
			"N" => GetMessage("MAIN_NO")
		),
		"filterable" => "=",
	),
	array(
		"id" => "DISCONNECTED",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_DISCONN"),
		"type" => "list",
		"items" => array(
			"Y" => GetMessage("MAIN_YES"),
			"N" => GetMessage("MAIN_NO")
		),
		"filterable" => "=",
	),
	array(
		"id" => "TIMESTAMP_X",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_MODIFIED"),
		"type" => "date",
	),
	array(
		"id" => "DATE_CREATE",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_CREATED"),
		"type" => "date",
	),
	array(
		"id" => "DATE_ACTIVE_FROM",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_FROM"),
		"type" => "date",
	),
	array(
		"id" => "DATE_ACTIVE_TO",
		"name" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_TO"),
		"type" => "date",
	),
	array(
		"id" => "CONTACT_PERSON",
		"name" => GetMessage("CTRL_MEMB_ADMIN_CONTACT_PERSON"),
		"filterable" => "%",
	),
	array(
		"id" => "EMAIL",
		"name" => GetMessage("CTRL_MEMB_ADMIN_EMAIL"),
		"filterable" => "%",
	),
);

$USER_FIELD_MANAGER->AdminListAddFilterFieldsV2($entity_id, $filterFields);
$arFilter = array();
$lAdmin->AddFilter($filterFields, $arFilter);

$filterOption = new Bitrix\Main\UI\Filter\Options($sTableID);
$filterData = $filterOption->getFilter($filterFields);
if (!empty($filterData["FIND"]))
{
	$arFilter["=%NAME"] = $filterData["FIND"].'%';
}

$names = explode(" ", $arFilter['%NAME']);
foreach ($names as $i => $name)
{
	$name = trim($name, " \t\n\r");
	if (!$name)
		unset($names[$i]);
}

if (count($names) > 1)
{
	$arFilter["=NAME"] = $names;
	unset($arFilter["%NAME"]);
}

$USER_FIELD_MANAGER->AdminListAddFilterV2($entity_id, $arFilter, $sTableID, $filterFields);

if ($USER->CanDoOperation("controller_member_edit") && $lAdmin->EditAction())
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$ID = intval($ID);
		if (!$lAdmin->IsUpdated($ID))
		{
			continue;
		}

		$DB->StartTransaction();
		$USER_FIELD_MANAGER->AdminListPrepareFields($entity_id, $arFields);
		if (!CControllerMember::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRL_MEMB_ADMIN_SAVE_ERR")." #".$ID.": ".$e->GetString(), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

$arID = $lAdmin->GroupAction();
if (
	!empty($arID)
	&& (
		($_REQUEST['action'] === 'delete' && $USER->CanDoOperation("controller_member_delete"))
		|| ($_REQUEST['action'] === 'activate' && $USER->CanDoOperation("controller_member_edit"))
		|| ($_REQUEST['action'] === 'deactivate' && $USER->CanDoOperation("controller_member_edit"))
		|| ($_REQUEST['action'] === 'disconnect' && $USER->CanDoOperation("controller_member_disconnect"))
		|| ($_REQUEST['action'] === 'update_settings' && $USER->CanDoOperation("controller_member_settings_update"))
		|| ($_REQUEST['action'] === 'site_update' && $USER->CanDoOperation("controller_member_updates_run"))
		|| ($_REQUEST['action'] === 'update_counters' && $USER->CanDoOperation("controller_member_counters_update"))
	)
)
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerMember::GetList(array(), $arFilter, ["ID"]);
		while ($arRes = $rsData->Fetch())
		{
			$arID[] = $arRes['ID'];
		}
	}

	foreach ($arID as $ID)
	{
		if ($ID == '')
		{
			continue;
		}

		$ID = intval($ID);
		switch ($_REQUEST['action'])
		{
		case "delete":
			$DB->StartTransaction();
			if (!CControllerMember::Delete($ID))
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_DEL_ERR"), $ID);
			}
			$DB->Commit();
			break;

		case "activate":
		case "deactivate":
			$arFields = array(
				"ACTIVE" => ($_REQUEST['action'] == "activate"? "Y": "N"),
			);
			if (!CControllerMember::Update($ID, $arFields))
			{
				if ($e = $APPLICATION->GetException())
				{
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_SAVE_ERR")." ".$ID.": ".$e->GetString(), $ID);
				}
			}
			break;

		case "disconnect":
			if (!CControllerMember::UnRegister($ID))
			{
				if ($e = $APPLICATION->GetException())
				{
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_DISC_ERR")." ".$ID.": ".$e->GetString(), $ID);
				}
			}
			break;

		case "update_settings":
			if (!CControllerMember::SetGroupSettings($ID))
			{
				if ($e = $APPLICATION->GetException())
				{
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPDSET_ERR").$ID.": ".$e->GetString(), $ID);
				}
			}
			break;

		case "site_update":
			if (!CControllerMember::SiteUpdate($ID))
			{
				if ($e = $APPLICATION->GetException())
				{
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPD_ERR").$ID.": ".$e->GetString(), $ID);
				}
			}
			break;

		case "update_counters":
			if (!CControllerMember::UpdateCounters($ID))
			{
				if ($e = $APPLICATION->GetException())
				{
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPDCNT_ERR").$ID.": ".$e->GetString(), $ID);
				}
			}
			break;
		}
	}

	if ($lAdmin->hasGroupErrors())
	{
		$adminSidePanelHelper->sendJsonErrorResponse($lAdmin->getGroupErrors());
	}
	else
	{
		$adminSidePanelHelper->sendSuccessResponse();
	}
}

$arHeaders = array(
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_MODIFIED"),
		"default" => true,
		"sort" => "timestamp_x",
	),
	array(
		"id" => "MODIFIED_BY",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_MODIFIEDBY"),
		"default" => true,
		"sort" => "modified_by",
	),
	array(
		"id" => "NAME",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_NAME"),
		"default" => true,
		"sort" => "name",
	),
	array(
		"id" => "URL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_URL"),
		"default" => true,
		"sort" => "URL",
	),
	array(
		"id" => "CONTACT_PERSON",
		"content" => GetMessage("CTRL_MEMB_ADMIN_CONTACT_PERSON"),
		"sort" => "CONTACT_PERSON",
	),
	array(
		"id" => "EMAIL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_EMAIL"),
		"sort" => "URL",
	),
	array(
		"id" => "CONTROLLER_GROUP_ID",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_GROUP"),
		"default" => true,
		"sort" => "CONTROLLER_GROUP_ID",
	),
	array(
		"id" => "DISCONNECTED",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_DISCONN"),
		"default" => true,
		"sort" => "active",
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_ACTIVE"),
		"default" => true,
		"sort" => "active",
		"align" => "center",
	),
	array(
		"id" => "DATE_ACTIVE_FROM",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_FROM"),
		"sort" => "DATE_ACTIVE_FROM",
	),
	array(
		"id" => "DATE_ACTIVE_TO",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_TO"),
		"sort" => "DATE_ACTIVE_TO",
	),
	array(
		"id" => "DATE_CREATE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_CREATED"),
		"sort" => "DATE_CREATE",
	),
	array(
		"id" => "CREATED_BY",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_CREATEDBY"),
		"sort" => "CREATED_BY",
	),
	array(
		"id" => "MEMBER_ID",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_UNIQID"),
		"sort" => "MEMBER_ID",
	),
	array(
		"id" => "COUNTERS_UPDATED",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_UPD"),
		"sort" => "COUNTERS_UPDATED",
	),
	array(
		"id" => "COUNTER_FREE_SPACE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_FREE"),
		"sort" => "COUNTER_FREE_SPACE",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_SITES",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_SITES"),
		"sort" => "COUNTER_SITES",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_USERS",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_USERS"),
		"sort" => "COUNTER_USERS",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_LAST_AUTH",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_LAST_AU"),
		"sort" => "COUNTER_LAST_AUTH",
	),
	array(
		"id" => "NOTES",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_NOTES"),
	),
	array(
		"id" => "ID",
		"content" => "ID",
		"default" => true,
		"sort" => "id",
		"align" => "right",
	),
);
if (ControllerIsSharedMode())
{
	$arHeaders[] = array(
		"id" => "SHARED_KERNEL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_SHARED_KERN"),
		"sort" => "SHARED_KERNEL",
		"align" => "center",
	);
}
if (COption::GetOptionString("controller", "show_hostname") == "Y")
{
	$arHeaders[] = array(
		"id" => "HOSTNAME",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_HOSTNAME"),
		"sort" => "HOSTNAME",
	);
}

$arCounters = array();
$rsCounters = CControllerCounter::GetList();
while ($arCounter = $rsCounters->Fetch())
{
	$key = "COUNTER_".$arCounter["ID"];
	$arCounters[$key] = $arCounter;
	$arHeaders[] = array(
		"id" => $key,
		"content" => htmlspecialcharsEx($arCounter["NAME"]),
		"sort" => $key,
		"align" => ($arCounter["COUNTER_FORMAT"] == "F"? "right": "left"),
	);
}

$USER_FIELD_MANAGER->AdminListAddHeaders($entity_id, $arHeaders);

$lAdmin->AddHeaders($arHeaders);

$nav = $lAdmin->getPageNavigation("pages-controller-member-admin");

if ($lAdmin->isTotalCountRequest())
{
	$count = CControllerMember::GetList(
		array(),
		$arFilter,
		array("ID"),
		array(),
		array("bOnlyCount" => true)
	);
	$lAdmin->sendTotalCountResponse($count);
}
elseif ($_REQUEST["mode"] == "excel")
{
	$arNavParams = false;
}
else
{
	$arNavParams = array(
		"nTopCount" => $nav->getLimit() + 1,
		"nOffset" => $nav->getOffset(),
	);
}

$arSelect = $lAdmin->GetVisibleHeaderColumns();
$arSelect[] = "ID";
$arSelect[] = "DISCONNECTED";
$arSelect[] = "SHARED_KERNEL";
if (in_array("MODIFIED_BY", $arSelect))
{
	$arSelect[] = "MODIFIED_BY_USER";
}
if (in_array("CREATED_BY", $arSelect))
{
	$arSelect[] = "CREATED_BY_USER";
}

$rsData = CControllerMember::GetList(
	array($by => $order),
	$arFilter,
	$arSelect,
	array(),
	$arNavParams
);
$rsData = new CAdminResult($rsData, $sTableID);

$n = 0;
$pageSize = $lAdmin->getNavSize();
while ($arRes = $rsData->Fetch())
{
	$n++;
	if ($n > $pageSize && !($_REQUEST["mode"] == "excel"))
	{
		break;
	}

	$row = &$lAdmin->AddRow($arRes['ID'], $arRes, 'controller_member_edit.php?lang='.LANGUAGE_ID.'&ID='.intval($arRes['ID']));
	$USER_FIELD_MANAGER->AddUserFields($entity_id, $arRes, $row);

	adminListAddUserLink($row, "MODIFIED_BY", $arRes['MODIFIED_BY'], $arRes['MODIFIED_BY_USER']);
	adminListAddUserLink($row, "CREATED_BY", $arRes['CREATED_BY'], $arRes['CREATED_BY_USER']);

	$row->AddCheckField("ACTIVE");
	if (ControllerIsSharedMode())
	{
		$row->AddCheckField("SHARED_KERNEL");
	}

	$row->AddInputField("NAME", array("size" => "35"));
	$row->AddInputField("URL", array("size" => "35"));

	if ($arRes['DISCONNECTED'] == 'Y')
	{
		$str = '<span class="adm-lamp adm-lamp-in-list adm-lamp-red"></span>'.GetMessage("admin_lib_list_yes");
	}
	elseif ($arRes['DISCONNECTED'] == 'I')
	{
		$str = GetMessage("CTRL_MEMB_ADMIN_DISCON");
	}
	else
	{
		$str = GetMessage("admin_lib_list_no");
	}

	$row->AddViewField("DISCONNECTED", $str);

	$row->AddViewField("URL", '<a href="'.htmlspecialcharsbx($arRes['URL']).'">'.htmlspecialcharsEx($arRes['URL']).'</a>');
	$row->AddInputField("EMAIL", array("size" => "35"));
	$row->AddInputField("CONTACT_PERSON", array("size" => "35"));

	if ($arRes['EMAIL'] != '')
	{
		$row->AddViewField("EMAIL", '<a href="'.htmlspecialcharsbx('mailto:'.$arRes['EMAIL']).'">'.htmlspecialcharsEx($arRes['EMAIL']).'</a>');
	}

	$row->AddSelectField("CONTROLLER_GROUP_ID", $arGroups);

	foreach ($arCounters as $key => $arCounter)
	{
		if (isset($arRes[$key]))
		{
			$html = CControllerCounter::FormatValue($arRes[$key], $arCounter["COUNTER_FORMAT"]);
			if ($arCounter["COUNTER_FORMAT"] == "F")
			{
				$html = str_replace(" ", "&nbsp;", $html);
			}
			$row->AddViewField($key, $html);
		}
	}

	$row->AddViewField("ID", '<a href="'.htmlspecialcharsbx('controller_member_edit.php?ID='.intval($arRes['ID'])."&lang=".LANGUAGE_ID).'">'.htmlspecialcharsEx($arRes['ID']).'</a>');

	$arActions = array();
	if ($USER->CanDoOperation("controller_member_edit"))
	{
		$arActions[] = array(
			"ICON" => "edit",
			"DEFAULT" => "Y",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_EDIT"),
			"ACTION" => $lAdmin->ActionRedirect("controller_member_edit.php?ID=".intval($arRes['ID'])."&lang=".LANGUAGE_ID),
		);
		$arActions[] = array(
			"SEPARATOR" => true,
		);
	}
	$c = 0;
	if ($arRes['DISCONNECTED'] == 'N')
	{
		if ($USER->CanDoOperation("controller_member_auth"))
		{
			$c++;
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_GOADMIN"),
				"ACTION" => $lAdmin->ActionRedirect("controller_goto.php?member=".$arRes['ID']."&lang=".LANGUAGE_ID),
			);
		}
		if ($arRes['SHARED_KERNEL'] != 'Y' && $USER->CanDoOperation("controller_member_updates_run"))
		{
			$c++;
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPD"),
				"ACTION" => $lAdmin->ActionDoGroup($arRes['ID'], "site_update"),
			);
		}
		if ($USER->CanDoOperation("controller_member_settings_update"))
		{
			$c++;
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPDSETT"),
				"ACTION" => $lAdmin->ActionDoGroup($arRes['ID'], "update_settings"),
			);
		}
		if ($USER->CanDoOperation("controller_member_counters_update"))
		{
			$c++;
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPDCNT"),
				"ACTION" => $lAdmin->ActionDoGroup($arRes['ID'], "update_counters"),
			);
		}
		if ($USER->CanDoOperation("controller_run_command"))
		{
			$c++;
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_RUNPHP"),
				"ACTION" => $lAdmin->ActionRedirect("controller_run_command.php?controller_member_id=".intval($arRes['ID'])."&lang=".LANGUAGE_ID),
			);
		}
	}

	if ($USER->CanDoOperation("controller_log_view"))
	{
		$c++;
		$arActions[] = array(
			"ICON" => "list",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_LOG"),
			"ACTION" => $lAdmin->ActionRedirect("controller_log_admin.php?CONTROLLER_MEMBER_ID=".intval($arRes['ID'])."&apply_filter=Y&lang=".LANGUAGE_ID),
		);
	}

	if ($c > 0)
	{
		$arActions[] = array(
			"SEPARATOR" => true,
		);
	}

	if ($arRes['DISCONNECTED'] == 'N')
	{
		if ($USER->CanDoOperation("controller_member_disconnect"))
		{
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_DISC"),
				"ACTION" => "if(confirm('".GetMessage("CTRL_MEMB_ADMIN_MENU_DISC_CONFIRM")."')) ".$lAdmin->ActionDoGroup($arRes['ID'], "disconnect"),
			);
		}
	}
	else
	{
		if ($USER->CanDoOperation("controller_member_edit"))
		{
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_CONN"),
				"ACTION" => $lAdmin->ActionRedirect("controller_member_edit.php?reconnect_id=".intval($arRes['ID'])."&lang=".LANGUAGE_ID),
			);
		}
	}

	if ($USER->CanDoOperation("controller_member_delete"))
	{
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_DEL"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_MEMB_ADMIN_MENU_DEL_ALERT")."')) ".$lAdmin->ActionDoGroup($arRes['ID'], "delete"),
		);
	}

	if ($arActions)
	{
		$row->AddActions($arActions);
	}
}

$nav->setRecordCount($nav->getOffset() + $n);
$lAdmin->setNavigation($nav, GetMessage("CTRL_MEMB_ADMIN_NAVSTRING"), false);

$groupActions = array();
if ($USER->CanDoOperation("controller_member_edit"))
	$groupActions["activate"] = GetMessage("MAIN_ADMIN_LIST_ACTIVATE");
if ($USER->CanDoOperation("controller_member_edit"))
	$groupActions["deactivate"] = GetMessage("MAIN_ADMIN_LIST_DEACTIVATE");
if ($USER->CanDoOperation("controller_member_settings_update"))
	$groupActions["update_settings"] = GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPDSETT");
if ($USER->CanDoOperation("controller_member_counters_update"))
	$groupActions["update_counters"] = GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPDCNT");
if ($USER->CanDoOperation("controller_member_disconnect"))
	$groupActions["disconnect"] = GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_DISC");
if ($USER->CanDoOperation("controller_member_updates_run"))
	$groupActions["site_update"] = GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPD");
if ($USER->CanDoOperation("controller_member_delete"))
	$groupActions["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");

if ($groupActions)
{
	$lAdmin->AddGroupActionTable($groupActions);
}

if ($USER->CanDoOperation("controller_member_add"))
{
	$aContext = array(
		array(
			"ICON" => "btn_new",
			"TEXT" => GetMessage("MAIN_ADD"),
			"LINK" => "controller_member_edit.php?lang=".LANGUAGE_ID,
			"TITLE" => GetMessage("MAIN_ADD"),
		),
	);
}
else
{
	$aContext = array();
}

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_MEMB_ADMIN_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(["SHOW_COUNT_HTML" => true]);

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
