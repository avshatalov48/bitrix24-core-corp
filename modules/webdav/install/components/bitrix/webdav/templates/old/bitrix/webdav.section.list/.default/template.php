<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;

$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/admin_tools.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/popup_menu.js"></script>', true);

$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script_dropdown.js"></script>', true);


$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");

global $by, $order;
?>
<script type="text/javascript">
//<![CDATA[
	if (phpVars == null || typeof(phpVars) != "object")
	{
		var phpVars = {
			'ADMIN_THEME_ID': '.default',
			'titlePrefix': '<?=CUtil::addslashes(COption::GetOptionString("main", "site_name", SITE_SERVER_NAME))?> - '};
	}

	if (typeof oObjectWD != "object")
		var oObjectWD = {};

	function ShowHideThisMenu(id, oObj)
	{
		if (oObjectWD['object'] == null || !oObjectWD['object'])
			oObjectWD['object'] = new PopupMenu('webdav');
		
		oObjectWD['object'].ShowMenu(oObj, window.oObjectWD['wd_' + id]);
	}
//]]>
</script>
<?

/*if (empty($arResult["DATA"]) && $arParams["SECTION_ID"] <= 0):
	?><span class="wd-text"><?=GetMessage("WD_EMPTY_DATA")?></span><?
	return 0;
endif;
*/

$_REQUEST["ELEMENTS"] = is_array($_REQUEST["ELEMENTS"]) ? $_REQUEST["ELEMENTS"] : array();
$arResult["SHOW_GROUP_ACTIONS"] = "none";
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arParams["BASE_URL"] = trim(str_replace(":443", "", $arParams["BASE_URL"]));
$arParams["SHOW_NAVIGATION"] = (is_array($arParams["SHOW_NAVIGATION"]) ? $arParams["SHOW_NAVIGATION"] : array("bottom"));
if ($arParams["PERMISSION"] <= "R")
{
	$arParams["COLUMNS_TITLE"] = array(
//Common
	"NAME" => array("title"=>GetMessage("WD_TITLE_NAME"),	"sort"=>"name"),
	"TIMESTAMP_X" => array("title" => GetMessage("WD_TITLE_TIMESTAMP"),"sort"=>"timestamp_x"),
//Section specific
	"ELEMENT_CNT" => array("title" => GetMessage("WD_TITLE_ELS"),			"sort"=>"element_cnt"),
	"SECTION_CNT" => array("title" => GetMessage("WD_TITLE_SECS")),
//Element specific
	"USER_NAME" => array("title" => GetMessage("WD_TITLE_MODIFIED_BY"), "sort"=>"modified_by"),
	"DATE_CREATE" => array("title" => GetMessage("WD_TITLE_ADMIN_DCREATE"), "sort"=>"created"),
	"CREATED_USER_NAME" => array("title" => GetMessage("WD_TITLE_ADMIN_WCREATE2"), "sort"=>"created_by"),
	"SHOW_COUNTER" => array("title" => GetMessage("WD_TITLE_EXTERNAL_SHOWS"), "sort"=>"show_counter"),
	"SHOW_COUNTER_START" => array("title" => GetMessage("WD_TITLE_EXTERNAL_SHOW_F"), "sort"=>"show_counter_start"),
	"PREVIEW_PICTURE" => array("title" => GetMessage("WD_TITLE_EXTERNAL_PREV_PIC")),
	"PREVIEW_TEXT" => array("title" => GetMessage("WD_TITLE_EXTERNAL_PREV_TEXT")),
	"DETAIL_PICTURE" => array("title" => GetMessage("WD_TITLE_EXTERNAL_DET_PIC")),
	"DETAIL_TEXT" => array("title" => GetMessage("WD_TITLE_EXTERNAL_DET_TEXT")),
	"TAGS" => array("title" => GetMessage("WD_TITLE_TAGS"), "sort"=>"tags"),
	"FILE_SIZE" => array("title" => GetMessage("WD_TITLE_FILE_SIZE")), 
	"PROPERTY_FORUM_MESSAGE_CNT" => array("title" => GetMessage("WD_PROPERTY_FORUM_MESSAGE_CNT"), 
		"description" => GetMessage("WD_PROPERTY_FORUM_MESSAGE_CNT_TITLE"), "sort" => "property_forum_message_cnt")
	);
	foreach ($arParams["COLUMNS"] as $key => $res)
	{
		if (empty($arParams["COLUMNS_TITLE"][$res]))
			unset($arParams["COLUMNS"][$key]);
	}
}
else 
{
	$arParams["COLUMNS_TITLE"] = array(
	//Common
		"NAME" => array("title"=>GetMessage("WD_TITLE_NAME"),	"sort"=>"name"),
		"ACTIVE" => array("title"=>GetMessage("WD_TITLE_ACTIVE"),	"sort"=>"active"),
		"SORT" => array("title" => GetMessage("WD_TITLE_SORT"),	"sort"=>"sort"),
		"CODE" => array("title" => GetMessage("WD_TITLE_CODE"),			"sort"=>"code"),
		"EXTERNAL_ID" => array("title" => GetMessage("WD_TITLE_EXTCODE"),		"sort"=>"external_id"),
		"TIMESTAMP_X" => array("title" => GetMessage("WD_TITLE_TIMESTAMP"),"sort"=>"timestamp_x"),
	//Section specific
		"ELEMENTS_CNT" => array("title" => GetMessage("WD_TITLE_ELS"),			"sort"=>"element_cnt"),
		"SECTIONS_CNT" => array("title" => GetMessage("WD_TITLE_SECS")),
	//Element specific
		"DATE_ACTIVE_FROM" => array("title" => GetMessage("WD_TITLE_ACTFROM"), "sort"=>"date_active_from"),
		"DATE_ACTIVE_TO" => array("title" => GetMessage("WD_TITLE_ACTTO"), "sort"=>"date_active_to"),
		"USER_NAME" => array("title" => GetMessage("WD_TITLE_MODIFIED_BY"), "sort"=>"modified_by"),
		"DATE_CREATE" => array("title" => GetMessage("WD_TITLE_ADMIN_DCREATE"), "sort"=>"created"),
		"CREATED_USER_NAME" => array("title" => GetMessage("WD_TITLE_ADMIN_WCREATE2"), "sort"=>"created_by"),
		"SHOW_COUNTER" => array("title" => GetMessage("WD_TITLE_EXTERNAL_SHOWS"), "sort"=>"show_counter"),
		"SHOW_COUNTER_START" => array("title" => GetMessage("WD_TITLE_EXTERNAL_SHOW_F"), "sort"=>"show_counter_start"),
		"PREVIEW_PICTURE" => array("title" => GetMessage("WD_TITLE_EXTERNAL_PREV_PIC")),
		"PREVIEW_TEXT" => array("title" => GetMessage("WD_TITLE_EXTERNAL_PREV_TEXT")),
		"DETAIL_PICTURE" => array("title" => GetMessage("WD_TITLE_EXTERNAL_DET_PIC")),
		"DETAIL_TEXT" => array("title" => GetMessage("WD_TITLE_EXTERNAL_DET_TEXT")),
		"TAGS" => array("title" => GetMessage("WD_TITLE_TAGS"), "sort"=>"tags"),
	
		"ID" => array("title"=>"ID", "sort"=>"id"),
		
		"WF_STATUS_ID" => array("title" => GetMessage("WD_TITLE_STATUS")),
		"WF_NEW" => array("title" => GetMessage("WD_TITLE_EXTERNAL_WFNEW")),
		"LOCK_STATUS" => array("title" => GetMessage("WD_TITLE_EXTERNAL_LOCK")),
		"LOCKED_USER_NAME" => array("title" => GetMessage("WD_TITLE_EXTERNAL_LOCK_BY")),
		"WF_DATE_LOCK" => array("title" => GetMessage("WD_TITLE_EXTERNAL_LOCK_WHEN")),
		"WF_COMMENTS" => array("title" => GetMessage("WD_TITLE_EXTERNAL_COM")),
		"FILE_SIZE" => array("title" => GetMessage("WD_TITLE_FILE_SIZE")), 
		"PROPERTY_FORUM_MESSAGE_CNT" => array("title" => GetMessage("WD_PROPERTY_FORUM_MESSAGE_CNT"), 
			"description" => GetMessage("WD_PROPERTY_FORUM_MESSAGE_CNT_TITLE"), "sort" => "property_forum_message_cnt"), 
		"BIZPROC" => array("title" => GetMessage("IBLIST_A_BP_H")), 
		"BP_PUBLISHED" => array("title" => GetMessage("IBLOCK_FIELD_BP_PUBLISHED"))
	);
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
}
if (!($arParams["RESOURCE_TYPE"] == "FOLDER" && $arParams["PERMISSION"] < "W"))
	array_unshift($arParams["COLUMNS"], "ACTIONS");
$iSelected = 0;
$iCountCheckbox = 0;
if ($arParams["PERMISSION"] >= "W"):
	foreach ($arResult["DATA"] as $key => $res):
		if ($res["TYPE"] == "E"):
			$val = array(
				"LOCK" => $res["SHOW"]["LOCK"], 
				"UNLOCK" => $res["SHOW"]["UNLOCK"], 
				"EDIT" => $res["SHOW"]["EDIT"], 
				"DELETE" => $res["SHOW"]["DELETE"]); 
			if (in_array("Y", $val)):
				$arResult["SHOW_GROUP_ACTIONS"] = "all";
				$arResult["DATA"][$key]["SHOW_CHECKBOX"] = "Y";
				$iCountCheckbox++;
			endif;
		elseif ($arParams["CHECK_CREATOR"] != "Y"):
			$arResult["SHOW_GROUP_ACTIONS"] = ($arResult["SHOW_GROUP_ACTIONS"] == "all" ? "all" : "sections");
			$arResult["DATA"][$key]["SHOW_CHECKBOX"] = "Y";
			$iCountCheckbox++;
		endif;
		if (is_array($_REQUEST["ELEMENTS"][$res["TYPE"]]) && in_array($res["ID"], $_REQUEST["ELEMENTS"][$res["TYPE"]])):
			$iSelected++;
		endif;
	endforeach;

	if ($arResult["SHOW_GROUP_ACTIONS"] != "none"):
		array_unshift($arParams["COLUMNS"], "CHECKBOX");
	endif;
endif;

$arTemplates = array();
if ($arParams["BIZPROC_START"] == true)
{
	$db_res = CBPWorkflowTemplateLoader::GetList(
		array(),
		array("DOCUMENT_TYPE" => $arParams["DOCUMENT_TYPE"]),
		false,
		false,
		array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS", "TEMPLATE")
	);
	while ($arWorkflowTemplate = $db_res->GetNext())
	{
		$arTemplates[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
	}
}

/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;

if (!empty($arResult["NAV_STRING"]) && in_array("top", $arParams["SHOW_NAVIGATION"])):
	?><div class="navigation navigation-top"><?=$arResult["NAV_STRING"]?></div><?
endif;

?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" class="wd-form">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="edit" value="Y" />

<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main data-table">
	<thead><tr class="wd-row">
<?
foreach ($arParams["COLUMNS"] as $key):
	$column = (empty($arParams["COLUMNS_TITLE"][$key]) ? array("title" => $key) : $arParams["COLUMNS_TITLE"][$key]);
	?><th class="wd-cell <?=(strtoupper($column["sort"]) == strtoupper($by) ? " selected" : "")?>" <?
	if (!empty($column["description"])):
		?> title="<?=$column["description"]?>"<?
	endif;
	
	?>><?
	if ($key == "CHECKBOX"):
		?><input type="checkbox" name="ELEMENTS_ALL[TOP]" onclick="wdChangeSelectPosition(this)" <?
			if ($iSelected == $iCountCheckbox):
			?> checked="checked" <?
			endif;
		?> /><?
	elseif ($key == "ACTIONS"):
		?><div class="wd-action-block"></div><?
	elseif (!empty($column["sort"])):
		$sClassSort = "wd-sort";
		$sOrder = "asc";
		if (strtoupper($column["sort"]) == strtoupper($by))
		{
			if(strtoupper($order)=="DESC")
			{
				$sClassSort .= " wd-sort-desc";
				$sOrder = "asc";
			}
			else
			{
				$sClassSort .= " wd-sort-asc";
				$sOrder = "desc";
			}
		}
?>
			<a href="<?=WDAddPageParams($arResult["URL"]["~THIS"], array("by" => $column["sort"], "order" => $sOrder))?>"<?
				?> title="<?
				if (strtoupper($column["sort"]) == strtoupper($by))
				{
					?><?=($sOrder=="desc" ? GetMessage("WD_SORTED_ASC") : GetMessage("WD_SORTED_DESC"))?><?
				}
				else
				{
					?><?=($sOrder=="desc" ? GetMessage("WD_SORT_DESC") : GetMessage("WD_SORT_ASC"))?><?
				}
				
				?>" class="<?=$sClassSort?>"><?=$column["title"]?>
			</a>
<?
	elseif ($key == "LOCK_STATUS"):
	else:
?>
			<?=$column["title"]?>
<?
	endif;
?>
		</th>
<?
endforeach;
?>
	</tr></thead>
	<tbody>
<?

if ($arParams["SECTION_ID"] > 0 && $arParams["SECTION_ID"] != $arParams["ROOT_SECTION_ID"]):
?>
	<tr class="wd-row-up">
<?
	foreach ($arParams["COLUMNS"] as $key):
	?><td class="wd-cell"><?
		if ($key == "NAME"):
		?><div class="controls controls-view up">
			<a href="<?=$arResult["URL"]["UP"]?>" title="<?=GetMessage("WD_UP_ALT")?>"><?
				?><?=GetMessage("WD_UP")
				?></a>
		</div><?
		else:
		?><div class="empty-clear"></div><?
		endif;
	?></td><?
	endforeach;
?>
</tr>
<?
endif;

if (empty($arResult["DATA"])):
?>
	<tr class="wd-row-up">
		<td class="wd-cell" colspan="<?=count($arParams["COLUMNS"])?>">
			<span class="wd-text"><?=GetMessage("WD_EMPTY_DATA")?></span>
		</td>
	</tr>
<?
else:
$iCount = 0;
foreach ($arResult["DATA"] as $res):
	$arActions = array();
	if ($res["TYPE"] == "S" && $arParams["PERMISSION"] >= "W")
	{
		if ($arParams["CHECK_CREATOR"] != "Y"):
			$arActions[] = array(
				"ICONCLASS" => "section_edit",
				"TITLE" => GetMessage("WD_CHANGE_SECTION"),
				"TEXT" => GetMessage("WD_CHANGE"),
				"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"])."');");
			$arActions[] = array(
				"ICONCLASS" => "section_drop",
				"TITLE" => GetMessage("WD_DELETE_SECTION"),
				"TEXT" => GetMessage("WD_DELETE"),
				"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_SECTION_CONFIRM"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
		endif;
	}
	elseif ($res["TYPE"] == "E")
	{
		$arActions[] = array(
			"ICONCLASS" => "element_download",
			"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
			"TEXT" => GetMessage("WD_DOWNLOAD"),
			"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DOWNLOAD"])."');", 
			"DEFAULT" => true); 
		if ($res["~TYPE"] !== "FILE")
		{
		$arActions[] = array(
			"ICONCLASS" => "element_view",
			"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
			"TEXT" => GetMessage("WD_VIEW"),
			"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"])."');"); 
		}

		if ($res["SHOW"]["SUBSCRIBE"] == "Y")
		{
			if ($res["SUBSCRIBE"] == "Y")
				$arActions[] = array(
					"ICONCLASS" => "element_subscribe",
					"TITLE" => GetMessage("WD_SUBSCRIBE_ELEMENT"),
					"TEXT" => GetMessage("WD_SUBSCRIBE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~SUBSCRIBE"])."');"); 
			else
				$arActions[] = array(
					"ICONCLASS" => "element_unsubscribe",
					"TITLE" => GetMessage("WD_UNSUBSCRIBE_ELEMENT"),
					"TEXT" => GetMessage("WD_UNSUBSCRIBE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNSUBSCRIBE"])."');"); 
		}
		
		
		if ($arParams["PERMISSION"] >= "U")
		{
			if ($res["SHOW"]["UNLOCK"] == "Y" || $res["SHOW"]["BP"] == "Y" || $res["SHOW"]["HISTORY"] == "Y")
				$arActions[] = array("SEPARATOR"=>true);
			
			if ($res["SHOW"]["UNLOCK"] == "Y")
			{
				$arActions[] = array(
					"ICONCLASS" => "element_unlock",
					"TITLE" => GetMessage("WD_UNLOCK_ELEMENT"),
					"TEXT" => GetMessage("WD_UNLOCK"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNLOCK"])."');");
			}
			
			if ($res["SHOW"]["BP"] == "Y")
			{
				if ($res["SHOW"]["BP_VIEW"] == "Y")
				{
					$arActions[] = array(
						"ICONCLASS" => "bizproc_document",
						"TITLE" => GetMessage("IBLIST_A_BP_H"),
						"TEXT" => GetMessage("IBLIST_A_BP_H"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~BP"])."');");
				}
				
				if ($res["SHOW"]["BP_START"] == "Y")
				{
					$arr = array();
					foreach ($arTemplates as $key => $arWorkflowTemplate)
					{
						if (!CBPDocument::CanUserOperateDocument(
							CBPCanUserOperateOperation::StartWorkflow,
							$GLOBALS["USER"]->GetID(),
							$res["DOCUMENT_ID"],
							array(
								"UserGroups" => $res["USER_GROUPS"], 
								"DocumentStates" => $res["~arDocumentStates"], 
								"WorkflowTemplateList" => $arTemplates, 
								"WorkflowTemplateId" => $arWorkflowTemplate["ID"]))):
							continue;
						endif;
						$url = $res["URL"]["~BP_START"];
						$url .= (strpos($url, "?") === false ? "?" : "&")."workflow_template_id=".$arWorkflowTemplate["ID"].'&'.bitrix_sessid_get();
						$arr[] = array(
							"ICONCLASS" => "",
							"TITLE" => $arWorkflowTemplate["DESCRIPTION"],
							"TEXT" => $arWorkflowTemplate["NAME"],
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($url)."');");
					}
					if (!empty($arr))
					{
						$arActions[] = array(
							"ICONCLASS" => "bizproc_start",
							"TITLE" => GetMessage("WD_START_BP_TITLE"),
							"TEXT" => GetMessage("WD_START_BP"),
							//"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~BP_START"])."');", 
							"MENU" => $arr);
					}
				}
			}
			if ($res["SHOW"]["HISTORY"] == "Y")
			{
				$arActions[] = array(
					"ICONCLASS" => "element_history".($res["SHOW"]["BP"] == "Y" ? " bizproc_history" : ""),
					"TITLE" => GetMessage("WD_HIST_ELEMENT_ALT"),
					"TEXT" => GetMessage("WD_HIST_ELEMENT"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~HIST"])."');");
			}
			
			if ($res["SHOW"]["EDIT"] == "Y")
			{
				if ($res["~TYPE"] !== "FILE")
				{
					$arActions[] = array("SEPARATOR"=>true);
				}
				$arActions[] = array(
					"ICONCLASS" => "element_edit",
					"TITLE" => GetMessage("WD_CHANGE_ELEMENT"),
					"TEXT" => GetMessage("WD_CHANGE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"])."');");
				if ($res["SHOW"]["DELETE"] == "Y")
				{
					$arActions[] = array(
						"ICONCLASS" => "element_delete",
						"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
						"TEXT" => GetMessage("WD_DELETE"),
						"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
				}
			}
		}
	}
	$iCount++;
	
?>	
	<tr class="wd-row<?=($iCount%2 == 0 ? " selected" : "")?> <?=($res["BP_PUBLISHED"] != "Y" ? "wd-row-unpublished" : "")?>">
<?
	foreach ($arParams["COLUMNS"] as $key):
		$column = (empty($arParams["COLUMNS_TITLE"][$key]) ? array("title" => $key) : $arParams["COLUMNS_TITLE"][$key]);
		?><td class="wd-cell wd-cell-<?=strtolower($key)?>"><?
		if ($key == "CHECKBOX"):
			if ($res["SHOW_CHECKBOX"] == "Y"):
				?><input type="checkbox" name="ELEMENTS[<?=$res["TYPE"]?>][]" value="<?=$res["ID"]?>" onclick="wdChangeSelectPosition(this)" <?
				if (is_array($_REQUEST["ELEMENTS"][$res["TYPE"]]) && in_array($res["ID"], $_REQUEST["ELEMENTS"][$res["TYPE"]])):
					?> checked="checked" <?
				endif;
				?> /><?
			endif;
		elseif ($key == "ACTIONS"):
if (!empty($arActions)):
/*?>
<script>
function HideThisMenu<?=$res["ID"]?>()
{
	if(window.WDDropdownMenu != null)
	{
		window.WDDropdownMenu.ShowMenu(this, oObjectWD['wd_<?=$res["ID"]?>'], document.getElementById('wd_<?=$res["ID"]?>'))
		window.WDDropdownMenu.PopupHide();
	}
}

oObjectWD['wd_<?=$res["ID"]?>'] = <?=CUtil::PhpToJSObject($arActions)?>;
</script>
<table cellpadding="0" cellspacing="0" border="0" class="wd-dropdown-pointer" <?
	?>onmouseover="this.className+=' wd-dropdown-pointer-over';" <?
	?>onmouseout="this.className=this.className.replace(' wd-dropdown-pointer-over', '');" <?
	?>onclick="if(window.WDDropdownMenu != null){window.WDDropdownMenu.ShowMenu(this, oObjectWD['wd_<?=$res["ID"]?>'], document.getElementById('wd_<?=$res["ID"]?>'))}" <?
	?>title="<?=GetMessage("WD_ACTIONS")?>" id="wd_table_<?=$res["ID"]?>"><tr>
	<td>
		<div class="controls controls-view show-action">
			<a href="javascript:void(0);" class="action">
				<div id="wd_<?=$res["ID"]?>" class="empty"></div>
			</a>
		</div></td>
</tr></table>
<?
*/
?>
<table cellpadding="0" cellspacing="0" border="0" class="wd-dropdown-pointer" <?
	?>onmouseover="this.className+=' wd-dropdown-pointer-over';" <?
	?>onmouseout="this.className=this.className.replace(' wd-dropdown-pointer-over', '');" <?
	?>onclick="ShowHideThisMenu('<?=CUtil::JSEscape($res["ID"])?>', document.getElementById('wd_<?=CUtil::JSEscape($res["ID"])?>'));" <?
	?>title="<?=GetMessage("WD_ACTIONS")?>" id="wd_table_<?=$res["ID"]?>"><tr>
	<td>
		<div class="controls controls-view show-action">
			<a href="javascript:void(0);" class="action">
				<div id="wd_<?=CUtil::JSEscape($res["ID"])?>" class="empty"></div>
			</a>
		</div></td>
</tr></table>
<script>
oObjectWD['wd_<?=CUtil::JSEscape($res["ID"])?>'] = <?=CUtil::PhpToJSObject($arActions)?>;
</script><?

endif;
		elseif ($key == "NAME"):
			if ($res["TYPE"] == "S"):
			?><div class="section-name"><?
				?><div class="section-icon"></div><?
				?><a href="<?=$res["URL"]["THIS"]?>"><?=$res["NAME"]?></a><?
			?></div><?
			else:
			?><div class="element-name"><?
				?><div class="element-icon ic<?=substr($res["FILE_EXTENTION"], 1)?>"></div><?
				if ($arParams["PERMISSION"] >= "U")
				{
					$lock_status = ($arParams["WORKFLOW"] == "workflow" ? $res['ORIGINAL']['LOCK_STATUS'] : $res['LOCK_STATUS']);
					if (in_array($lock_status, array("red", "yellow")))
					{
						$lamp_alt = ($lock_status=="yellow" ? GetMessage("IBLOCK_YELLOW_ALT") : GetMessage("IBLOCK_RED_ALT"));
						$locked_by = ($arParams["WORKFLOW"] == "workflow" ? $res['ORIGINAL']['LOCKED_USER_NAME'] : $res['LOCKED_USER_NAME']);
						?><div class="element-icon element-lamp-<?=$lock_status?>" title='<?=$lamp_alt?> <?
						if ($lock_status=='red' && $locked_by!='')
						{
							?> <?=$locked_by?> <?
						}
						?>'></div><?
					}
				}
				?><a href="<?=$res["URL"]["THIS"]?>" <?
				if (!empty($res["PREVIEW_TEXT"]))
				{
						?>title="<?=$res["PREVIEW_TEXT"]?>"<?
				}
				if ($res["SHOW"]["EDIT"] == "Y" && in_array($res["FILE_EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx")))
				{
					?> onclick="return EditDocWithProgID('<?=CUtil::JSEscape($res["URL"]["THIS"])?>')"<?
				}
				?> target="_blank"><?=$res["NAME"]?></a><?
			?></div><?
			if ($arParams["USE_COMMENTS"] == "Y" && intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]) > 0):
				$iComments = intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]);
				?><a href="<?=$res["URL"]["VIEW"]?>" class="element-properties element-comments" title="<?=GetMessage("WD_COMMENTS_FOR_DOCUMENT")." ".$iComments?>"><?=$iComments?></a><?
			elseif ($arParams["RESOURCE_TYPE"] != "FOLDER"):
				?><a href="<?=$res["URL"]["VIEW"]?>" class="element-properties element-view" title="<?=GetMessage("WD_VIEW_ELEMENT")?>"></a><?
			endif;
			endif;
		elseif (in_array($key, array("MODIFIED_BY", "CREATED_BY", "WF_LOCKED_BY",
			"LOCKED_USER_NAME", "USER_NAME", "CREATED_USER_NAME"))):
			if ($key == "CREATED_USER_NAME")
				$key = "CREATED_BY";
			elseif ($key == "USER_NAME")
				$key = "MODIFIED_BY";
			else
				$key = "WF_LOCKED_BY";
			
			$arUser = $arResult["USERS"][$res[$key]];
			if (empty($arUser))
			{
				if ($key == "CREATED_BY")
					$key = "CREATED_USER_NAME";
				elseif ($key == "MODIFIED_BY")
					$key = "USER_NAME";
				else
					$key = "LOCKED_USER_NAME";
				?><?=$res[$key]?><?
			}
			else
			{
				?><div class="wd-user">
					<a href="<?=$arUser["URL"]?>"><?=$arUser["NAME"]." ".$arUser["LAST_NAME"]?></a>
				</div><?
			}
		elseif (strPos($key, "PICTURE") !== false):
			$picture = $res["PREVIEW_PICTURE"];
			if ($res["TYPE"] == "S")
				$picture = $res["PICTURE"];
			?><?=CFile::ShowFile($picture, 100000, 50, 50, true);
		elseif ($res["TYPE"] == "E" && ($key == "WF_STATUS_ID" || $key == "LOCK_STATUS")): 
			if ($key == "WF_STATUS_ID"):
				?><?=$arResult["STATUSES"][$res['WF_STATUS_ID']]?><?
			elseif ($arParams["WORKFLOW"] == "workflow"):
				if ($res['ORIGINAL']['LOCK_STATUS']=="green")
				{
					$lamp_alt = GetMessage("IBLOCK_GREEN_ALT");
				}
				elseif ($res['ORIGINAL']['LOCK_STATUS']=="yellow")
				{
					$lamp_alt = GetMessage("IBLOCK_YELLOW_ALT");
				}
				else
				{
					$lamp_alt = GetMessage("IBLOCK_RED_ALT");
				}
				?><div class="element-lamp-<?=$res['ORIGINAL']['LOCK_STATUS']?>" title='<?=$lamp_alt?>'></div><?
	
				if ($res["ORIGINAL"]['LOCK_STATUS']=='red' && $res['LOCKED_USER_NAME']!='')
				{
					?><div class="wd-user element-locked-user"><?=$res['LOCKED_USER_NAME']?></div><?
				}
			endif;
			?><?
		elseif ($res["TYPE"] == "E" && $key == "PROPERTY_FORUM_MESSAGE_CNT"):
			?><a href="<?=$res["URL"]["VIEW"]?>"><?=intVal($res[$key])?></a><?
		elseif ($res["TYPE"] == "E" && $key == "BIZPROC"):
			$arDocumentStates = $res["arDocumentStates"];
			
			if (empty($arDocumentStates))
			{
				?>&nbsp;<?
			}
			elseif (count($arDocumentStates) == 1)
			{
				$arDocumentState = reset($arDocumentStates);
				$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
				
				?><div class="bizproc-item-title" style=""><?
					?><div class="bizproc-statuses <?
						if (!(strlen($arDocumentState["ID"]) <= 0 || strlen($arDocumentState["WORKFLOW_STATUS"]) <= 0)):
							?>bizproc-status-<?=(empty($arTasksWorkflow) ? "inprogress" : "attention")?><?
						endif;
						?>"></div><?
					?><?=(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP"))?>: <?
					?><span class="bizproc-item-title bizproc-state-title" style="margin-left:1em;"><?
						?><a href="<?=$res["URL"]["BP"]?>"><?
							?><?=(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"])?><?
						?></a><?
					?></span><?
				?></div><?
				
				if (!empty($arTasksWorkflow))
				{
					?><div class="bizproc-tasks"><?
					$first = true;
					foreach ($arTasksWorkflow as $key => $val)
					{
						$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], 
							array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"])); 
						$url = WDAddPageParams($url, array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
						?><?=($first ? "" : ", ")?><a href="<?=$url?>"><?=$val["NAME"]?></a><?
						$first = false;
					}
					?></div><?
				}
			}
			else 
			{
				
				$arTasks = array(); $bFirst = true; $bInprogress = false;
				ob_start();
				?><ol class="bizproc-items" style="margin: 0; padding: 0; list-style-type: none;"><?
				foreach ($arDocumentStates as $key => $arDocumentState)
				{
					$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
					?><li class="bizproc-item" style="<?=($bFirst ? "" : "margin-top:1em;")?>"><?
						?><div class="bizproc-item-title"><?
							?><div class="bizproc-statuses <?
							if (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0):
								$bInprogress = true;
								?>bizproc-status-<?=(empty($arTasksWorkflow) ? "inprogress" : "attention")?><?
							endif;
							?>"></div><?
							?><?=(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP"))?><?
						?></div><?
						?><div class="bizproc-item-title bizproc-state-title" style="margin-left:1em;"><?
							?><?=(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"])?><?
						?></div><?
					
					if (!empty($arTasksWorkflow))
					{
						?><div class="bizproc-tasks" style="margin-left:1em;"><?
						$first = true;
						foreach ($arTasksWorkflow as $key => $val)
						{
							$val["URL"] = array("BP_TASK" => CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], 
									array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"])));
							$val["URL"]["BP_TASK"] = WDAddPageParams($val["URL"]["BP_TASK"], 
								array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
							$arTasks[] = $val;
							?><?=($first ? "" : ", ")?><a href="<?=$val["URL"]["BP_TASK"]?>"><?=$val["NAME"]?></a><?
							$first = false;
						}
						?></div><?
					}
					?></li><?
					$bFirst = false;
				}
				?></ol><?
				$sHint = ob_get_clean();
				?><span class="bizproc-item-title"><?
					?><div class="bizproc-statuses<?
					if ($bInprogress):
						?> bizproc-status-<?=(empty($arTasks) ? "inprogress" : "attention")?><?
					endif;
					?>"></div><?
					?><?=GetMessage("WD_BP_R_P")?>: <a href="<?=$res["URL"]["BP"]?>" title="<?=GetMessage("WD_BP_R_P_TITLE")?>"><?=count($arDocumentStates)?></a><?
				?></span><?
				if (!empty($arTasks)):
					?><br /><span class="bizproc-item-title"><?=GetMessage("WD_TASKS")?>: <a href="<?=$res["URL"]["BP_TASK"]?>" title="<?=GetMessage("WD_TASKS_TITLE")?>"><?=count($arTasks)?></a></span><?
				endif;
				if (!empty($sHint)):
					ShowJSHint($sHint, array());
				endif;
			}
		elseif ($res["TYPE"] == "E" && $key == "BP_PUBLISHED"):
			?><?=($res["BP_PUBLISHED"] != "Y" ? GetMessage("WD_N") : GetMessage("WD_Y"))?><?
		else:
			?><?=$res[$key]?><?
		endif;
		
		?></td><?
	endforeach;
?>	
	</tr>
<?
endforeach;
endif;

?>
	</tbody>
<?
if ($arResult["SHOW_GROUP_ACTIONS"] != "none" && $arParams["PERMISSION"] >= "W"):
	$_REQUEST["ACTION"] = strtolower($_REQUEST["ACTION"]);
	$_REQUEST["ACTION"] = (!in_array($_REQUEST["ACTION"], array("move", "lock", "unlock", "delete")) ? "none" : $_REQUEST["ACTION"]);
?>
	<tfoot>
		<tr class="wd-row-up">
			<td><input type="checkbox" name="ELEMENTS_ALL[BOTTOM]" onclick="wdChangeSelectPosition(this)" <?
			if ($iSelected == $iCountCheckbox):
			?> checked="checked" <?
			endif;
			?> /></td>
			<td class="wd-cell" colspan="<?=(count($arParams["COLUMNS"]) - 1)?>">
				<select name="ACTION" onchange="wdChangeAction(this)">
					<option value="none" <?=($_REQUEST["ACTION"] == "none" ? " selected " : "")?>><?=GetMessage("WD_MANAGE")?></option>
<?/*?>					<option value="edit" <?=($_REQUEST["ACTION"] == "edit" ? " selected " : "")?>><?=GetMessage("WD_CHANGE")?></option><?*/?>
<?
	if ($arParams["WORKFLOW"] == "workflow" && $arResult["SHOW_GROUP_ACTIONS"] == "all"):
?>
					<option value="unlock" <?=($_REQUEST["ACTION"] == "unlock" ? " selected " : "")?>><?=GetMessage("WD_UNLOCK")?></option>
					<option value="lock" <?=($_REQUEST["ACTION"] == "lock" ? " selected " : "")?>><?=GetMessage("WD_LOCK")?></option>
<?
	endif;
?>
					<option value="move" <?=($_REQUEST["ACTION"] == "move" ? " selected " : "")?>><?=GetMessage("WD_MOVE")?></option><??>
					<option value="delete" <?=($_REQUEST["ACTION"] == "delete" ? " selected " : "")?>><?=GetMessage("WD_DELETE")?></option>
				</select><?
				?><span style="display:<?=($_REQUEST["ACTION"] == "move" ? "auto" : "none")?>;">
					<select name="IBLOCK_SECTION_ID" class="select">
						<option value="0" <?=($arParams["SECTION_ID"] == 0 ? "selected" : "")?>><?=GetMessage("WD_CONTENT")?></option>
			<?
			foreach ($arResult["SECTION_LIST"] as $res)
			{
			?>
						<option value="<?=$res["ID"]?>" <?=($arParams["SECTION_ID"] == $res["ID"] ? "selected=\"selected\" class=\"selected\" " : "")?>>
							<?=str_repeat(".", $res["DEPTH_LEVEL"])?><?=($res["NAME"])?></option>
			<?
			}
			?>
					</select>
				</span><?
				?><input type="submit" value="OK" <?=($_REQUEST["ACTION"] == "none" ? " disabled='disabled' " : "")?>/>
			</td>
		</tr>
	</tfoot>
<?
endif;
?>
</table>
</form>
<script>
window.wdEval = function(function_name, function_data)
{
	try
	{
		eval(function_name + '(' + function_data + ')');
	}
	catch(e)
	{
		alert('Error');
	}
}
window.wdChangeAction = function(oObj)
{
	if (!oObj || typeof(oObj) != "object")
		return false;
	else if (oObj.name != 'ACTION')
		oObj = oObj.form.ACTION
	oObj.nextSibling.style.display = (oObj.value == 'move' ? 'inline' : 'none');
	var iCheckedCount = wdChangeSelectPosition(oObj, true);
	oObj.nextSibling.nextSibling.disabled = (oObj.value == 'none' || iCheckedCount <= 0 ? true : false);
}
// for global ajax
if (typeof(window["wdChangeSelectPosition"]) == "object" || typeof(window["wdChangeSelectPosition"]) == "function")
{
	window.wdChangeSelectPosition.prototype.selector = 'undefined';
}
</script>
<?
if (!empty($arResult["DATA"]) && !empty($arResult["NAV_STRING"]) && in_array("bottom", $arParams["SHOW_NAVIGATION"])):
?>
	<div class="navigation navigation-bottom"><?=$arResult["NAV_STRING"]?></div>
<?
endif;

if (!empty($arParams["SHOW_NOTE"])):
?>
<br />
<div class="wd-help-list selected" id="wd_list_note"><?=$arParams["~SHOW_NOTE"]?></div>
<?
endif;

if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U" && $arParams["SHOW_WORKFLOW"] != "N"):?>
<br />
<div class="wd-help-list selected">
<?
if ($arParams["PERMISSION"] >= "W" && CWorkflow::IsAdmin()):
?><?=GetMessage("WD_WF_COMMENT1")?><br /><?
elseif (!in_array(2, $arResult["WF_STATUSES_PERMISSION"])):
?><?=GetMessage("WD_WF_COMMENT2")?><br /><?
else:
	foreach ($arResult["WF_STATUSES_PERMISSION"] as $key => $val):
		if ($val == 2):
			$arr[] = htmlspecialcharsEx($arResult["WF_STATUSES"][$key]);
		endif;
	endforeach;
	
	if (count($arr) == 1):
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION2"))?><br /><?
	else:
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION3"))?><br /><?
	endif;
endif;

if ($arParams["PERMISSION"] >= "W"):
?><?=GetMessage("WD_WF_ATTENTION1")?><br /><?
endif;
?>
</div>
<?endif;?>