<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
global $USER;
CJSCore::Init(array('finder'));
\Bitrix\Main\UI\Extension::load('intranet.search_title');

$this->setFrameMode(true);

$inputId = trim($arParams["~INPUT_ID"]);
if($inputId == '')
	$inputId = "title-search-input";
$inputId = CUtil::JSEscape($inputId);

$containerId = trim($arParams["~CONTAINER_ID"]);
if($containerId == '')
{
	$containerId = "title-search";
}
$containerId = CUtil::JSEscape($containerId);

$className =
	!isModuleInstalled("timeman") || (CModule::IncludeModule("bitrix24") && SITE_ID == "ex") ? " timeman-simple" : "";
?>

<div class="header-search<?=$className?> header-search-empty">
	<div class="header-search-inner">
		<form class="header-search-form" method="get" name="search-form" onsubmit="return false;" action="<?//=$arResult["FORM_ACTION"]?>" id="<?=$containerId?>">
			<input
				class="header-search-input" name="q" id="<?=$inputId?>" type="text" autocomplete="off"
				placeholder = "<?=GetMessage("CT_BST_SEARCH_HINT")?>"
				onclick="BX.addClass(this.parentNode.parentNode.parentNode,'header-search-active')"
				onblur="BX.removeClass(this.parentNode.parentNode.parentNode, 'header-search-active')"
			/>
			<span class="header-search-icon header-search-icon-title"></span>
			<span class="search-title-top-delete"></span>
		</form>
	</div>
</div>

<? $frame = $this->createFrame()->begin(""); ?>
<script type="text/javascript">
	BX.message({
		"GLOBAL_SEARCH" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH")?>",
		"SEARCH_MORE" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_MORE")?>",
		"SEARCH_NO_RESULT" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_NO_RESULT")?>",
		"SEARCH_CRM_LEAD" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_LEAD")?>",
		"SEARCH_CRM_DEAL" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_DEAL")?>",
		"SEARCH_CRM_INVOICE" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_INVOICE")?>",
		"SEARCH_CRM_CONTACT" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_CONTACT")?>",
		"SEARCH_CRM_COMPANY" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_COMPANY")?>",
		"SEARCH_CRM_QUOTE" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_QUOTE")?>",
		"SEARCH_TASKS" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_TASKS")?>",
		"SEARCH_DISK" : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_DISK")?>"
	});

	new BX.Intranet.SearchTitle ({
		'AJAX_PAGE' : '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>',
		'CONTAINER_ID': '<?=$containerId?>',
		'INPUT_ID': '<?=$inputId?>',
		'MIN_QUERY_LEN': 3,
		'FORMAT': 'json',
		'CATEGORIES_ALL': <?=CUtil::PhpToJSObject($arResult['CATEGORIES_ALL'])?>,
		'USER_URL':  '<?=CUtil::JSEscape(\Bitrix\Main\Config\Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/', SITE_ID).'user/#user_id#/')?>',
		'GROUP_URL':  '<?=CUtil::JSEscape(\Bitrix\Main\Config\Option::get('socialnetwork', 'workgroups_page', SITE_DIR.'workgroups/', SITE_ID).'group/#group_id#/')?>',
		'WAITER_TEXT':  '<?=GetMessageJS('CT_BST_WAITER_TEXT')?>',
		'CURRENT_TS':  <?=time()?>,
		'GLOBAL_SEARCH_CATEGORIES': <?=CUtil::PhpToJSObject($arResult["GLOBAL_SEARCH_CATEGORIES"])?>,
		'MORE_USERS_URL': '<?=SITE_DIR."company/?apply_filter=Y&with_preset=Y&FIND="?>',
		'MORE_GROUPS_URL': '<?=SITE_DIR."workgroups/?apply_filter=Y&with_preset=Y&FIND="?>',
		'IS_CRM_INSTALLED': '<?=IsModuleInstalled("crm") ? "Y" : "N"?>'
		//'SEARCH_PAGE': '<?=CUtil::JSEscape(str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"]))?>'
	});
</script>
<? $frame->end(); ?>