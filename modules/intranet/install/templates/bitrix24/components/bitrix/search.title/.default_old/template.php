<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

CJSCore::Init(array('finder'));

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

<div class="header-search<?=$className?>" >
	<div class="header-search-inner">
		<form class="header-search-form" method="get" name="search-form" action="<?=$arResult["FORM_ACTION"]?>" id="<?=$containerId?>">
			<input
				class="header-search-input" name="q" id="<?=$inputId?>" type="text" autocomplete="off"
				placeholder = "<?=GetMessage("CT_BST_SEARCH_HINT")?>"
				onclick="BX.addClass(this.parentNode.parentNode.parentNode,'header-search-active')"
				onblur="BX.removeClass(this.parentNode.parentNode.parentNode, 'header-search-active')"
			/>
			<span class="header-search-icon" onclick="document.forms['search-form'].submit();"></span>
		</form>
	</div>
</div>

<? $frame = $this->createFrame()->begin(""); ?>
<script type="text/javascript">
new BX.B24SearchTitle({
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
	'SEARCH_PAGE': '<?=str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])?>'
});
</script>
<? $frame->end(); ?>