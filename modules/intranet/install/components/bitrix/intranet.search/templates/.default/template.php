<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['INTRANET_TOOLBAR']->Show();

$current_filter = $_REQUEST['current_filter'] == 'adv' ? 'adv' : 'simple';

$arParams['CURRENT_FILTER'] = $current_filter;

//$arParams['DETAIL_URL'] = $arParams['LIST_URL'] = $APPLICATION->GetCurPageParam('current_view='.$arParams['CURRENT_VIEW'].'&current_filter='.$current_filter, array('current_view', 'current_filter'));

$outlook_link = 'javascript:'.CIntranetUtils::GetStsSyncURL(
	array(
		'LINK_URL' => $APPLICATION->GetCurPage()
	), 'contacts', ($arParams["EXTRANET_TYPE"] == "employees" ? true : false)
);
?>
<div class="bx-intranet-outlook-banner">
<?
$APPLICATION->IncludeComponent(
	'bitrix:intranet.banner',
	'',
	array(
		'ID' => 'user_search_outlook',
		'CONTENT' => GetMessage('INTR_COMP_IS_TPL_BANNER_OUTLOOK').'<br /><br /><a href="'.htmlspecialcharsbx($outlook_link).'" class="bx-bnr-button bx-users-outlook">'.GetMessage('INTR_COMP_IS_TPL_BANNER_OUTLOOK_BUTTON').'</a>',
		'ICON' => 'bx-outlook-contacts',
		'ICON_HREF' => $outlook_link,
		'ALLOW_CLOSE' => 'Y',

	),
	$this,
	array('HIDE_ICONS' => 'Y')
)
?>
</div>
<script>
var current_filter = '<?echo CUtil::JSEscape($current_filter)?>';
var arFilters = ['simple', 'adv'];
function BXSetFilter(new_current_filter)
{
	if (current_filter == new_current_filter)
		return;

	for (var i = 0; i < arFilters.length; i++)
	{
		var obTabContent = document.getElementById('bx_users_filter_' + arFilters[i]);
		var obTab = document.getElementById('bx_users_selector_tab_' + arFilters[i]);

		if (null != obTabContent)
		{
			obTabContent.style.display = new_current_filter == arFilters[i] ? 'block' : 'none';
			current_filter = new_current_filter;
		}

		if (null != obTab)
		{
			obTab.className = new_current_filter == arFilters[i] ? 'bx-selected' : '';
		}
	}

}
</script>
<div class="bx-users-filter">
	<ul class="bx-users-selector">
		<li id="bx_users_selector_tab_simple"<?echo $current_filter == 'adv' ? '' : ' class="bx-selected"'?> onclick="BXSetFilter('simple')"><?echo GetMessage('INTR_COMP_IS_TPL_FILTER_SIMPLE')?></li>
		<li id="bx_users_selector_tab_adv"<?echo $current_filter == 'adv' ? ' class="bx-selected"' : ''?> onclick="BXSetFilter('adv')"><?echo GetMessage('INTR_COMP_IS_TPL_FILTER_ADV')?></li>
	</ul>
	<div class="bx-users-selector-filter" id="bx_users_filter_simple"<?echo $current_filter == 'adv' ? ' style="display: none;"' : ''?>>
	<?
	$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "simple", $arParams, $component, array('HIDE_ICONS' => 'Y'));
	?>
	</div>
	<div class="bx-users-selector-filter" id="bx_users_filter_adv"<?echo $current_filter == 'adv' ? '' : ' style="display: none;"'?>>
	<?
	$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "advanced", $arParams, $component, array('HIDE_ICONS' => 'Y'));
	?>
	</div>
</div>
<br />
<table class="bx-users-toolbar"><tr>
	<td><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW')?>:</td>
	<td><a class="bx-users-icon bx-users-view-list<?echo $arParams['CURRENT_VIEW'] == 'list' ? '-active bx-users-selected ' : ''?>" href="<?echo $APPLICATION->GetCurPageParam('current_view=list', array('current_view'));?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW_LIST')?>"></a></td>
	<td><a class="bx-users-icon bx-users-view-table<?echo $arParams['CURRENT_VIEW'] == 'table' ? '-active bx-users-selected ' : ''?>" href="<?echo $APPLICATION->GetCurPageParam('current_view=table', array('current_view'));?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW_TABLE')?>"></a></td>
	<td class="bx-users-toolbar-delimiter"></td>
	<td><?$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", 'online', $arParams, $component, array('HIDE_ICONS' => 'Y'));?></td>
	<td class="bx-users-toolbar-last"><a class="bx-users-icon1 bx-users-excel" href="<?=$APPLICATION->GetCurPageParam('current_view=table&excel=yes&ncc=1', array('excel', 'current_view'))?>" onclick="javascript:void(0)" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_EXCEL_TITLE')?>"><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_EXCEL')?></a><a class="bx-users-icon1 bx-users-outlook" href="<?echo $outlook_link;?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_OUTLOOK_TITLE')?>"><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_OUTLOOK')?></a><a class="bx-users-icon1 bx-users-outlook" href="/bitrix/groupdav.php?help=Y#carddav" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_CARDDAV_TITLE')?>"><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_CARDDAV')?></a></td>
</tr></table>
<div class="bx-users-alphabet" id="bx_alph" style="visibility: visible;">
<?
$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", 'alphabet', $arParams, $component, array('HIDE_ICONS' => 'Y'));
?>
</div>
<div style="clear: right;"></div>
<?
if (($arParams['CURRENT_VIEW'] == 'list' && $arParams['LIST_VIEW'] == 'group') || ($arParams['CURRENT_VIEW'] == 'table' && $arParams['TABLE_VIEW'] == 'group_table'))
{
	$arParams['SHOW_NAV_TOP'] = 'N';
	$arParams['SHOW_NAV_BOTTOM'] = 'N';
	$arParams['USERS_PER_PAGE'] = 0;
}

$arParams['USER_PROPERTY'] = $arParams['CURRENT_VIEW'] == 'list' ?
	($arParams['LIST_VIEW'] == 'group' ? $arParams['USER_PROPERTY_GROUP'] : $arParams['USER_PROPERTY_LIST']):
	$arParams['USER_PROPERTY_TABLE'];

//echo '<pre>'; print_r($arParams['USER_PROPERTY']); echo '</pre>';

$APPLICATION->IncludeComponent("bitrix:intranet.structure.list", ($arParams['CURRENT_VIEW'] == 'list' ? $arParams['LIST_VIEW'] : ($arParams['TABLE_VIEW']!=''?$arParams['TABLE_VIEW']:'')), $arParams, $component, array('HIDE_ICONS' => 'Y'));
?>