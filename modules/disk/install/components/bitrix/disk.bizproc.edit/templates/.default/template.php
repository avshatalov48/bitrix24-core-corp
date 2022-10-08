<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;

//$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/adminstyles.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/calendar.css");
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/popup_menu.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');
CUtil::InitJSCore(array("window", "ajax", "bp_selector"));
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
\Bitrix\Main\UI\Extension::load(['bizproc.globals']);
//////////////////////////////////////////////////////////////////////////////

$ID = $arResult["ID"];

$menu = Array();
$menu[] = array(
	"TEXT"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_BACK"),
	"TITLE"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_BACK"),
	"LINK"=>$arResult['BACK_TO_STORAGE'],
	"ICON"=>"",
);

$menu[] = array(
	"TEXT"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_PARAMS"),
	"TITLE"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_PARAMS_TITLE"),
	"LINK"=>"javascript:BCPShowParams();",
	"ICON"=>"btn_settings",
);

$menu[] = [
	'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_WFEDIT_MENU_GLOBAL_VARIABLES'),
	'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_WFEDIT_MENU_GLOBAL_VARIABLES_TITLE'),
	'LINK' => 'javascript:globals.showGlobalVariables();',
];
$menu[] = [
	'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_WFEDIT_MENU_GLOBAL_CONSTANTS'),
	'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('BIZPROC_WFEDIT_MENU_GLOBAL_CONSTANTS_TITLE'),
	'LINK' => 'javascript:globals.showGlobalConstants();',
];

$menu[] = array("SEPARATOR"=>"Y");

$menu[] = array(
	"TEXT"=>(($arParams["BIZPROC_EDIT_MENU_LIST_MESSAGE"] <> '') ? htmlspecialcharsbx($arParams["BIZPROC_EDIT_MENU_LIST_MESSAGE"]) : Loc::getMessage("BIZPROC_WFEDIT_MENU_LIST")),
	"TITLE"=>(($arParams["BIZPROC_EDIT_MENU_LIST_TITLE_MESSAGE"] <> '') ? htmlspecialcharsbx($arParams["BIZPROC_EDIT_MENU_LIST_TITLE_MESSAGE"]) : GetMessage("BIZPROC_WFEDIT_MENU_LIST_TITLE")),
	"LINK"=>$arResult['LIST_PAGE_URL'],
	"ICON"=>"btn_list",
);

if (!array_key_exists("SKIP_BP_TYPE_SELECT", $arParams) || $arParams["SKIP_BP_TYPE_SELECT"] != "Y")
{
	$subMenu = Array();

	$subMenu[] = array(
		"TEXT"	=> Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_STATE"),
		"TITLE"	=> Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_STATE_TITLE"),
		"ONCLICK"=> "if(confirm('".Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='".str_replace("#ID#", "0", $arResult["EDIT_PAGE_TEMPLATE"]).(mb_strpos($arResult["EDIT_PAGE_TEMPLATE"], "?")? "&" : "?")."init=statemachine';"
	);

	$subMenu[] = array(
		"TEXT"	=> Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ"),
		"TITLE"	=> Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ_TITLE"),
		"ONCLICK" => "if(confirm('".Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='".str_replace("#ID#", "0", $arResult["EDIT_PAGE_TEMPLATE"]).(mb_strpos($arResult["EDIT_PAGE_TEMPLATE"], "?")? "&" : "?")."';"
	);

	$menu[] = array(
		"TEXT"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD"),
		"TITLE"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_ADD_TITLE"),
		"ICON"=>"btn_new",
		"MENU"=>$subMenu
	);
}

$menu[] = array("SEPARATOR"=>true);

$menu[] = array(
	"TEXT"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_EXPORT"),
	"TITLE"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_EXPORT_TITLE"),
	"LINK"=>"javascript:BCPProcessExport();",
	"ICON"=>"",
);
$menu[] = array(
	"TEXT"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_IMPORT"),
	"TITLE"=>Loc::getMessage("BIZPROC_WFEDIT_MENU_IMPORT_TITLE"),
	"LINK"=>"javascript:BCPProcessImport();",
	"ICON"=>"",
);


$releaseDate = IsModuleInstalled('bitrix24')? GetMessage('BIZPROC_IMPORT_FILE_OLD_TEMPLATE_B24') : GetMessage('BIZPROC_IMPORT_FILE_OLD_TEMPLATE_CP');
$releaseDate = CUtil::JSEscape(FormatDate('SHORT', strtotime($releaseDate)));

?>
<script>
var BCPEmptyWorkflow =  <?=$ID>0 ? 'false' : 'true'?>;
function BCPProcessExport()
{
	if (BCPEmptyWorkflow)
	{
		alert('<?= GetMessageJS("BIZPROC_EMPTY_EXPORT") ?>');
		return false;
	}
	<?$v = str_replace("&amp;", "&", str_replace("#ID#", $ID, $arResult["EDIT_PAGE_TEMPLATE"]));?>
	window.open('<?=CUtil::JSEscape($v)?><?if(mb_strpos($v, "?")):?>&<?else:?>?<?endif?>action=exportTemplate&<?=bitrix_sessid_get()?>');
}

function BCPProcessImport()
{
	if (!confirm("<?= GetMessageJS("BIZPROC_WFEDIT_MENU_IMPORT_PROMT") ?>"))
		return;

	var btnOK = new BX.CWindowButton({
		'title': '<?= GetMessageJS("BIZPROC_IMPORT_BUTTON") ?>',
		'action': function()
		{
			BX.showWait();

			var _form = document.getElementById('import_template_form');

			var _name = document.getElementById('id_import_template_name');
			var _descr = document.getElementById('id_import_template_description');
			var _auto = document.getElementById('id_import_template_autostart');

			if (_form)
			{
				_name.value = workflowTemplateName;
				_descr.value = workflowTemplateDescription;
				_auto.value = encodeURIComponent(workflowTemplateAutostart);
				_form.submit();
			}

			this.parentWindow.Close();
		}
	});

	new BX.CDialog({
		title: '<?= GetMessageJS("BIZPROC_IMPORT_TITLE") ?>',
		content: '<form action="<?= CUtil::JSEscape(POST_FORM_ACTION_URI.'?action=importTemplate') ?>" method="POST" id="import_template_form" enctype="multipart/form-data"><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="top"><td width="50%" align="right"><?= GetMessageJS("BIZPROC_IMPORT_FILE") ?>:</td><td width="50%" align="left"><input type="file" size="35" name="import_template_file" value=""></td></tr><tr valign="top"> <td style="padding-top:15px" width="50%" align="right"><?= GetMessageJS("BIZPROC_IMPORT_FILE_OLD_TEMPLATE") ?>:</td> <td style="padding-top:15px" width="50%" align="left"><input type="checkbox" size="35" name="old_template" value="1"></td> </tr></table><input type="hidden" name="import_template" value="Y"><input type="hidden" id="id_import_template_name" name="import_template_name" value=""><input type="hidden" name="import_template_description" id="id_import_template_description" value=""><input type="hidden" id="id_import_template_autostart" name="import_template_autostart" value=""><?= bitrix_sessid_post() ?><div style="float:left;background-color:rgb(214,241,251);border: 1px solid rgb(193, 234, 249);font-size: 0.95em;padding: 15px 15px;margin-top:19px;"><div><?= GetMessageJS("BIZPROC_IMPORT_FILE_OLD_TEMPLATE_P1") ?></div> <div style=" margin-top: 10px;"><?= GetMessageJS("BIZPROC_IMPORT_FILE_OLD_TEMPLATE_P2", array('#DATE#' => $releaseDate)) ?></div> </div></form>',
		buttons: [btnOK, BX.CDialog.btnCancel],
		width: 550,
		height: 230
	}).Show();
}

function BCPSaveTemplateComplete()
{
}

<?$v = str_replace("&amp;", "&", POST_FORM_ACTION_URI);?>

function BCPSaveUserParams()
{
	var data = JSToPHP(arUserParams, 'USER_PARAMS');

	jsExtLoader.onajaxfinish = BCPSaveTemplateComplete;
	jsExtLoader.startPost('<?= CUtil::JSEscape($v) ?><?if(mb_strpos($v, "?")):?>&<?else:?>?<?endif?><?=bitrix_sessid_get()?>&action=saveAjax&saveuserparams=Y', data);
}

function BCPSaveTemplate(save)
{
	arWorkflowTemplate = Array(rootActivity.Serialize());
	var data =
			'workflowTemplateName=' + encodeURIComponent(workflowTemplateName) + '&' +
			'workflowTemplateDescription=' + encodeURIComponent(workflowTemplateDescription) + '&' +
			'workflowTemplateAutostart=' + encodeURIComponent(workflowTemplateAutostart) + '&' +
			JSToPHP(arWorkflowParameters, 'arWorkflowParameters') + '&' +
			JSToPHP(arWorkflowVariables, 'arWorkflowVariables') + '&' +
			JSToPHP(arWorkflowConstants, 'arWorkflowConstants') + '&' +
			JSToPHP(arWorkflowTemplate, 'arWorkflowTemplate');

	jsExtLoader.onajaxfinish = BCPSaveTemplateComplete;
	// TODO: add sessid
	jsExtLoader.startPost('<?=CUtil::JSEscape($v)?><?if(mb_strpos($v, "?")):?>&<?else:?>?<?endif?><?=bitrix_sessid_get()?>&action=saveAjax'+
		(save ? '': '&apply=Y'),
		data);
}

function BCPShowParams()
{
	<?php
	$u = "/bitrix/admin/".$arResult['MODULE_ID']."_bizproc_wf_settings.php?mode=public&bxpublic=Y&lang="
		.LANGUAGE_ID."&entity=".$arResult['ENTITY'];

	if (isset($arResult['DOCUMENT_TYPE_SIGNED']))
	{
		$dts = $arResult['DOCUMENT_TYPE_SIGNED'];
		$u = "/bitrix/tools/bizproc_wf_settings.php?mode=public&bxpublic=Y&lang=".LANGUAGE_ID."&dts=".$dts;
	}
	?>

	(new BX.CAdminDialog({
	'content_url': '<?=CUtil::JSEscape($u)?>',
	'content_post': 'workflowTemplateName=' 		+ encodeURIComponent(workflowTemplateName) + '&' +
			'workflowTemplateDescription=' 	+ encodeURIComponent(workflowTemplateDescription) + '&' +
			'workflowTemplateAutostart=' 	+ encodeURIComponent(workflowTemplateAutostart) + '&' +
			'document_type=' 				+ encodeURIComponent(document_type) + '&' +
			'<?= bitrix_sessid_get() ?>' + '&' +
			JSToPHP(arWorkflowParameters, 'arWorkflowParameters')  + '&' +
			JSToPHP(arWorkflowVariables, 'arWorkflowVariables')  + '&' +
			JSToPHP(arWorkflowConstants, 'arWorkflowConstants')  + '&' +
			JSToPHP(Array(rootActivity.Serialize()), 'arWorkflowTemplate'),
	'height': 500,
	'width': 800,
	'resizable' : false
	})).Show();
	<? if($arResult['HIDE_TAB_PERMISSION']) { ?>
	BX.addCustomEvent(BX.WindowManager.Get(), 'onWindowRegister', BX.defer(function(){
		BX.remove(BX('tab_cont_edit4'));
	}));
	<? } ?>
}

var globals =  new BX.Disk.Component.BizprocEditComponent({
	signedDocumentType: '<?= CUtil::JSEscape($arResult['DOCUMENT_TYPE_SIGNED']) ?>'
});

</script>
<div style="background-color: #FFFFFF;">
<?
if($arParams['SHOW_TOOLBAR']=='Y'):
?>
<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS"=>$menu,
		),
		$component, array("HIDE_ICONS" => "Y")
	);
?>
<?endif?>

<style>
div#bx_admin_form table.edit-tab td div.edit-tab-inner {height: 310px;}
a.activitydel, a.activityset, a.activitymin {width:11px; height: 11px; float: right; cursor: pointer; margin: 4px;}
.activity a.activitydel {background: url(/bitrix/images/bizproc/act_button_del.gif) 50% center no-repeat;}
.activity a.activityset {background: url(/bitrix/images/bizproc/act_button_sett.gif) 50% center no-repeat;}
.activity a.activitymin {background: url(/bitrix/images/bizproc/act_button_min.gif) 50% center no-repeat;}

a.activitydel:hover {border: 1px #999999 solid; margin: 3px;}
a.activityset:hover {border: 1px #999999 solid; margin: 3px;}
a.activitymin:hover {border: 1px #999999 solid; margin: 3px;}

.parallelcontainer {position: relative; top: -12px;}

.btn_settings {background-image:url(/bitrix/images/bizproc/settings.gif);}
.btn_list {background-image:url(/bitrix/images/bizproc/list.gif);}
.btn_new {background-image:url(/bitrix/images/bizproc/new.gif);}

td.statdel, td.statset {width:20px; height: 10px; cursor: pointer; margin-top: 7px; margin-right: 7px;}
td.statdel {background: url(/bitrix/images/bizproc/stat_del.gif) 50% center no-repeat;}
td.statset {background: url(/bitrix/images/bizproc/stat_sett.gif) 50% center no-repeat;}

.activity {
	position: relative;
}
.activity-modern {
	border: 2px #bebabb solid;
	border-radius: 3px;
}
.activity .activity-comment {
	position: absolute;
	top: -5px;
	right: -24px;
}
.activity .activityhead {background: url(/bitrix/images/bizproc/act_h.gif) left top repeat-x; height: 17px; overflow-y: hidden; background-color: #fec260;}
.activity.activity-modern .activityhead {background-image: none; background-color: #f9cf82; padding-bottom: 1px}
.activity .activityheadr {background: url(/bitrix/images/bizproc/act_hr.gif) right top no-repeat;}
.activity .activityheadl {background: url(/bitrix/images/bizproc/act_hl.gif) left top no-repeat; height:17px; padding-left: 3px;}

.activityerr {}
.activityerr .activityhead {background: url(/bitrix/images/bizproc/err_act_h.gif) left top repeat-x; height: 17px; overflow-y: hidden; background-color: #ffb3b3;}
.activityerr.activity-modern .activityhead {background-image: none; background-color: #ffb3b3;}
.activityerr .activityheadr {background: url(/bitrix/images/bizproc/err_act_hr.gif) right top no-repeat;}
.activityerr .activityheadl {background: url(/bitrix/images/bizproc/err_act_hl.gif) left top no-repeat; height:17px; padding-left: 3px;}

.activityerr a.activitydel {background: url(/bitrix/images/bizproc/err_act_button_del.gif) 50% center no-repeat;}
.activityerr a.activityset {background: url(/bitrix/images/bizproc/err_act_button_sett.gif) 50% center no-repeat;}

</style>
<script src="/bitrix/js/main/public_tools.js"></script>
<script src="/bitrix/js/bizproc/bizproc.js"></script>

<?
global $JSMESS;
$JSMESS = Array();
function GetJSLangMess($f, $actId)
{
	$MESS = Array();
	if(file_exists($f."/lang/en/".$actId.".js.php"))
		include($f."/lang/en/".$actId.".js.php");
	if(file_exists($f."/lang/".LANGUAGE_ID."/".$actId.".js.php"))
		include($f."/lang/".LANGUAGE_ID."/".$actId.".js.php");

	global $JSMESS;
	foreach($MESS as $k=>$v)
		$JSMESS[$k] = $v;
}

foreach($arResult['ACTIVITIES'] as $actId => $actProps)
{
	$actPath = mb_substr($actProps["PATH_TO_ACTIVITY"], mb_strlen($_SERVER["DOCUMENT_ROOT"]));
	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/".$actId.".js"))
	{
		echo '<script src="'.$actPath.'/'.$actId.'.js"></script>';
		GetJSLangMess($actProps["PATH_TO_ACTIVITY"], $actId);
	}

	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/".$actId.".css"))
		echo '<link rel="stylesheet" type="text/css" href="'.$actPath.'/'.$actId.'.css">';

	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/icon.gif"))
		$arResult['ACTIVITIES'][$actId]['ICON'] = $actPath.'/icon.gif';

	unset($arResult['ACTIVITIES'][$actId]['PATH_TO_ACTIVITY']);
}
?>
<script>
var arAllActivities = <?=CUtil::PhpToJSObject($arResult['ACTIVITIES'])?>;
var arAllActGroups = <?=CUtil::PhpToJSObject($arResult['ACTIVITY_GROUPS'])?>;
var arWorkflowParameters = <?=CUtil::PhpToJSObject($arResult['PARAMETERS'])?>;
var arWorkflowVariables = <?=CUtil::PhpToJSObject($arResult['VARIABLES'])?>;
var arWorkflowConstants = <?=CUtil::PhpToJSObject($arResult['CONSTANTS'])?>;
var arWorkflowGlobalConstants = <?= CUtil::PhpToJSObject($arResult['GLOBAL_CONSTANTS']) ?>;
var arWorkflowGlobalVariables = <?= CUtil::PhpToJSObject($arResult['GLOBAL_VARIABLES']) ?>;
var wfGVarVisibilityNames = <?= CUtil::PhpToJSObject($arResult['GLOBAL_VARIABLES_VISIBILITY_NAMES']) ?>;
var wfGConstVisibilityNames = <?= CUtil::PhpToJSObject($arResult['GLOBAL_CONSTANTS_VISIBILITY_NAMES']) ?>;
var arWorkflowTemplate = <?=CUtil::PhpToJSObject($arResult['TEMPLATE'][0])?>;
var arDocumentFields = <?=CUtil::PhpToJSObject($arResult['DOCUMENT_FIELDS'])?>;

var workflowTemplateName = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_NAME'])?>;
var workflowTemplateDescription = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_DESC'])?>;
var workflowTemplateAutostart = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_AUTOSTART'])?>;

var document_type = <?=CUtil::PhpToJSObject($arResult['DOCUMENT_TYPE'])?>;
<?if (isset($arResult['DOCUMENT_TYPE_SIGNED'])):?>
var document_type_signed = '<?=CUtil::JSEscape($arResult['DOCUMENT_TYPE_SIGNED'])?>';
<?endif?>
var MODULE_ID = <?=CUtil::PhpToJSObject($arResult['MODULE_ID'])?>;
var ENTITY = <?=CUtil::PhpToJSObject($arResult['ENTITY'])?>;
var BPMESS = <?=CUtil::PhpToJSObject($JSMESS)?>;
var BPDesignerUseJson = true;

var CURRENT_SITE_ID = <?=CUtil::PhpToJSObject(SITE_ID)?>;

var arUserParams = <?=CUtil::PhpToJSObject($arResult['USER_PARAMS'])?>;


var arAllId = {};
var rootActivity;

function BizProcRender(oActivity, divParent, t)
{
	rootActivity = CreateActivity(oActivity);
	rootActivity.Draw(divParent);
}

function ReDraw()
{
	var p;
	if(rootActivity.Type == 'SequentialWorkflowActivity')
	{
		if(rootActivity.swfWorkspaceDiv)
			p = rootActivity.swfWorkspaceDiv.scrollTop;

		while(rootActivity.childActivities.length>0)
			rootActivity.RemoveChild(rootActivity.childActivities[0]);

		rootActivity.Init(arWorkflowTemplate);
		rootActivity.DrawActivities();

		rootActivity.swfWorkspaceDiv.scrollTop = p;
	}
	else
	{
		if(rootActivity._redrawObject)
		{
			if(rootActivity._redrawObject.swfWorkspaceDiv)
				p = rootActivity._redrawObject.swfWorkspaceDiv.scrollTop;

			while(rootActivity._redrawObject.childActivities.length>0)
				rootActivity._redrawObject.RemoveChild(rootActivity._redrawObject.childActivities[0]);

			var act = FindActivityById(arWorkflowTemplate, rootActivity._redrawObject.Name);

			rootActivity._redrawObject.Init(act);
			rootActivity._redrawObject.DrawActivities();

			rootActivity._redrawObject.swfWorkspaceDiv.scrollTop = p;
		}
		else
		{
			var d = rootActivity.Table.parentNode;

			while(rootActivity.childActivities.length>0)
				rootActivity.RemoveChild(rootActivity.childActivities[0]);

			rootActivity.Init(arWorkflowTemplate);
			rootActivity.RemoveResources();
			rootActivity.Draw(d);
		}
	}
}


function start()
{
	var t = document.getElementById('wf1');
	if (!t)
	{
		setTimeout(function () {start();}, 1000);
		return;
	}
	BizProcRender(arWorkflowTemplate, document.getElementById('wf1'));
	<?if($ID<=0):?>
	BCPShowParams();
	<?endif;?>
}

setTimeout("start()", 0);
</script>
<form>

<div id="wf1" style="width: 100%; border-bottom: 2px #efefef dotted; " ></div>

<div id="bizprocsavebuttons">
<br>
<input type="button" onclick="BCPSaveTemplate(true);" value="<?echo Loc::getMessage("BIZPROC_WFEDIT_SAVE_BUTTON")?>">
<input type="button" onclick="BCPSaveTemplate();" value="<?echo Loc::getMessage("BIZPROC_WFEDIT_APPLY_BUTTON")?>">
<input type="button" onclick="window.location='<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['LIST_PAGE_URL']))?>';" value="<?echo Loc::getMessage("BIZPROC_WFEDIT_CANCEL_BUTTON")?>">
</div>

</form>
</div>
