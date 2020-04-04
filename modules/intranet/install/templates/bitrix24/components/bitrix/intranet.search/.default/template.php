<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/employee.css");

$show_user = "active";
	
if (isset($_REQUEST['show_user']))
{
	if ($_REQUEST['show_user'] == 'inactive')
		$show_user = 'inactive';
	elseif ($_REQUEST['show_user'] == 'fired' && COption::GetOptionString("bitrix24", "show_fired_employees", "Y") == "Y")
		$show_user = 'fired';
	elseif ($_REQUEST['show_user'] == 'extranet')
		$show_user = 'extranet';
	elseif ($_REQUEST['show_user'] == 'all')
		$show_user = 'all';	
	else
		$show_user = 'active';
}
$arParams["show_user"] = $show_user;

$outlook_link = 'javascript:'.CIntranetUtils::GetStsSyncURL(
	array(
		'LINK_URL' => $APPLICATION->GetCurPage()
	), 'contacts', ($arParams["EXTRANET_TYPE"] == "employees" ? true : false)
);

global $USER;
?>

<?$this->SetViewTarget("pagetitle", 100);?>

<div class="bx24-top-bar-search-wrap employee-search-wrap">
	<?$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "advanced", $arParams, $component, array('HIDE_ICONS' => 'Y'));?>
</div>

<?
if ((CModule::IncludeModule('bitrix24') &&
	 CBitrix24::isInvitingUsersAllowed() ||
	 !IsModuleInstalled("bitrix24") &&
	 $USER->CanDoOperation('edit_all_users')) &&
	CModule::IncludeModule('intranet') &&
	$arParams['TABLE_VIEW'] != 'group_table'
):?>
	<span
		class="webform-small-button webform-small-button-blue bx24-top-toolbar-add"
		onclick="<?=CIntranetInviteDialog::ShowInviteDialogLink()?>"
	>
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage("INTR_COMP_IS_TPL_TOOLBAR_USER_INVITE")?></span>
	</span>
<?endif;?>

<?$this->EndViewTarget()?>

<?if($arParams["USE_VIEW_SELECTOR"]!="N"):

	$abFilter = array_key_exists($arParams['FILTER_NAME'].'_LAST_NAME', $arFilterValues) ? $arFilterValues[$arParams['FILTER_NAME'].'_LAST_NAME'] : "";
?>
<div class="employee-filter-block">
	<span id="employee-filter" class="employee-filter">
		<span class="filter-but-wrap <?if ($show_user == 'active') echo " filter-but-act";?>"><span class="filter-but-left"></span><a href="<?echo $APPLICATION->GetCurPageParam('show_user=active', array('show_user'));?>"><span class="filter-but-text-block"><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_ACTIVE')?></span></a><span class="filter-but-right"></span></span>
			<?if (!(CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())):?> 
				<?if (COption::GetOptionString("bitrix24", "show_fired_employees", "Y") == "Y"):?>
				<span class="filter-but-wrap <?if ($show_user == 'fired') echo " filter-but-act";?>"><span class="filter-but-left"></span><a href="<?echo $APPLICATION->GetCurPageParam('show_user=fired', array('show_user'));?>"><span class="filter-but-text-block"><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_FIRED')?></span></a><span class="filter-but-right"></span></span>
				<?endif?>
				<?if ($USER->CanDoOperation('edit_all_users')):?>
					<?if (CModule::IncludeModule("extranet") && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0):?>
					<span class="filter-but-wrap <?if ($show_user == 'extranet') echo " filter-but-act";?>"><span class="filter-but-left"></span><a href="<?echo $APPLICATION->GetCurPageParam('show_user=extranet', array('show_user'));?>"><span class="filter-but-text-block"><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_EXTRANET')?></span></a><span class="filter-but-right"></span></span>
					<?endif?>
					<span class="filter-but-wrap <?if ($show_user == 'inactive') echo " filter-but-act";?>"><span class="filter-but-left"></span><a href="<?echo $APPLICATION->GetCurPageParam('show_user=inactive', array('show_user'));?>"><span class="filter-but-text-block"><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_INACTIVE')?></span></a><span class="filter-but-right"></span></span>
					<span class="filter-but-wrap <?if ($show_user == 'all') echo " filter-but-act";?>"><span class="filter-but-left"></span><a href="<?echo $APPLICATION->GetCurPageParam('show_user=all', array('show_user'));?>"><span class="filter-but-text-block"><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_ALL')?></span></a><span class="filter-but-right"></span></span>
				<?endif;?>
			<?endif;?>
	</span><span id="filter-but-ABC" class="filter-but-wrap filter-but-ABC<?if (strlen($abFilter) > 0):?> filter-but-act<?endif?>"><span class="filter-but-left"></span><span class="filter-but-text-block"><?if ($abFilter == ""):?><span class="filter-but-Ab"><span
	class="filter-but-blue"><?=GetMessage("INTR_COMP_IS_TPL_FILTER_ALPH_LETTER1")?></span><?=GetMessage("INTR_COMP_IS_TPL_FILTER_ALPH_LETTER2")?></span><?endif?><span class="filter-but-text"><?=GetMessage('INTR_COMP_IS_TPL_FILTER_ALPH')?><?=(strlen($abFilter) > 0 ? ": ".htmlspecialcharsbx(substr($abFilter, 0, 1)) : "")?></span></span><span class="filter-but-right"></span></span>
	<span id="filter-but-more" class="filter-but-wrap filter-but-more"><span class="filter-but-left"></span><span class="filter-but-text-block"><span class="filter-but-icon"></span><?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_USER_MORE')?></span><span class="filter-but-right"></span></span>
	<div class="employee-filter-left"></div>
	<div class="employee-filter-right"></div>
</div>
<?endif?>

<?

$arParams['USER_PROPERTY'] = $arParams['USER_PROPERTY_LIST'];
$arParams['SHOW_USER'] = $show_user;

$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", 'alphabet', $arParams, $component, array('HIDE_ICONS' => 'Y'));

?>

<script type="text/javascript">
	BX.bind(BX('filter-but-ABC'), 'click', ABC_popup);
	BX.bind(BX('filter-but-more'), 'click', more_action_popup);
	
	var user_list = BX.findChildren(BX('employee-table'), {tagName:'span', className:'employee-user-action'}, true);

	function ABC_popup()
	{
		var width_but = BX('filter-but-ABC').offsetWidth;
		var ABCPopup = BX.PopupWindowManager.create('employee-ABC-block', BX('filter-but-ABC'), {
				angle: { offset: 75 },
				offsetTop: 8,
				events: {
					onPopupClose: function() {
						BX.removeClass(this.bindElement, 'filter-but-act')
					}
				},
				autoHide:true
			});
		var ABCPopupContent = BX('employee-ABC', false);
		if (ABCPopupContent)
		{
			ABCPopup.setContent(ABCPopupContent);
			ABCPopupContent.removeAttribute('id');
		}
		ABCPopup.setBindElement(BX('filter-but-ABC'));
		ABCPopup.show();	
		BX.addClass(BX('filter-but-ABC'), 'filter-but-act');
	}

	function more_action_popup()
	{
		BX.addClass(BX('filter-but-more'),"filter-but-act");
		
		BX.PopupMenu.show('more-action-menu', BX('filter-but-more'), [
			{ text : "<?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_EXCEL')?>", className: "menu-popup-no-icon", href : "<?=$APPLICATION->GetCurPageParam('current_view=table&excel=yes&ncc=1', array('excel', 'current_view'))?>"},
			{ text : "<?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_OUTLOOK')?>", className: "menu-popup-no-icon", href : "<?echo $outlook_link;?>"},
			{ text : "<?=GetMessage('INTR_COMP_IS_TPL_TOOLBAR_CARDDAV')?>", className: "menu-popup-no-icon", href : "javascript:<?= $APPLICATION->GetPopupLink(
					Array(
						"URL"=> "/bitrix/groupdav.php?lang=".LANG."&help=Y&dialog=Y",
						//"PARAMS" => Array("width" => 450, "height" => 200)
					)
				);?>" 
			}
		],
		{
			offsetTop: 7,
			offsetLeft: -40,
			events: {
				onPopupClose : function() {
					BX.removeClass(this.bindElement, 'filter-but-act');
				}
			}
		});
	}

	var moreActionMenu = BX.PopupMenu.getMenuById('more-action-menu');
	if (moreActionMenu)
		moreActionMenu.popupWindow.setBindElement(BX('filter-but-more'));

</script>
<?
if (IsModuleInstalled("bitrix24"))
{
	$APPLICATION->IncludeComponent("bitrix:intranet.structure.list", 'list', $arParams, $component, array('HIDE_ICONS' => 'Y'));	
}
else
{
	if (($arParams['DEFAULT_VIEW'] == 'list' && $arParams['LIST_VIEW'] == 'group') || ($arParams['DEFAULT_VIEW'] == 'table' && $arParams['TABLE_VIEW'] == 'group_table'))
	{
		$arParams['SHOW_NAV_TOP'] = 'N';
		$arParams['SHOW_NAV_BOTTOM'] = 'N';
		$arParams['USERS_PER_PAGE'] = 0;
	}
	$arParams['USER_PROPERTY'] = ($arParams['DEFAULT_VIEW'] == 'list')
			? ($arParams['LIST_VIEW'] == 'group' ? $arParams['USER_PROPERTY_GROUP'] : $arParams['USER_PROPERTY_LIST'])
			: $arParams['USER_PROPERTY_TABLE'];

	$APPLICATION->IncludeComponent(
		"bitrix:intranet.structure.list",
		($arParams['DEFAULT_VIEW'] == 'list') ? $arParams['LIST_VIEW'] : $arParams['TABLE_VIEW'],
		$arParams,
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
?>