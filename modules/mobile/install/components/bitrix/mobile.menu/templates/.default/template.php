<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?

if (!CModule::IncludeModule("mobileapp"))
	die();
$APPLICATION->SetPageProperty("BodyClass", "menu-page");
$APPLICATION->AddHeadString('<link href="' . $templateFolder . "/style.css" . '" type="text / css" rel="stylesheet" />');


$initData = array(
	"lang" => array(
		"pulltext" => GetMessage("PULL_TEXT"),
		"downtext" => GetMessage("DOWN_TEXT"),
		"loadtext" => GetMessage("LOAD_TEXT")
	),
	"userId" => $GLOBALS['USER']->GetID(),
	"canInvite" => (IsModuleInstalled("bitrix24") && $USER->CanDoOperation('bitrix24_invite')),
	"calendarFirstVisit" => (CUserOptions::GetOption("mobile", "calendar_first_visit", "Y") == "Y")
);

?>

<script type="text/javascript">
	MenuSettings.setSettings(<?=CUtil::PhpToJsObject($initData)?>);
	BX.ready(function ()
	{
		Menu.init(null);
	});

</script>

<div class="menu-items" id="menu-items">

	<div class="menu-section">
		<div id="mb_user_profile_link" class="menu-item  menu-item-user"
			data-url="<?=SITE_DIR?>mobile/users/?ID=<?= $arResult["USER"]["ID"] ?>">
			<div class="menu-item-alignment"></div>
			<div class="menu-item-avatar"
				<? if ($arResult["USER"]["AVATAR"]): ?>style="background: url('<?= $arResult["USER"]["AVATAR"]["src"] ?>') no-repeat; background-size: cover;"<? endif ?>></div>
			<span class="menu-item-user-text"><?=
				$arResult["USER_FULL_NAME"];
				?></span>
		</div>
		<a href="#" id="account_change_id" onclick="app.exec('showAuthForm'); return false;"
			onmouseup="BX.removeClass(this, 'menu-item-selected');" class="menu-item menu-item-reload"><i></i></a>
	</div>

	<div class="menu-separator"><?= GetMessage("MB_SEC_FAVORITE"); ?></div>

	<div class="menu-section">
		<div class="menu-item menu-item-wrap menu-icon-lenta" id="main_feed" data-url="<?=SITE_DIR?>mobile/"
			data-pageid="main_feed"><?= GetMessage("MB_LIVE_FEED"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-tasks" id="tasks_list"
			data-url="<?=SITE_DIR?>mobile/tasks/snmrouter/?routePage=list"
			data-pageid="tasks_list"><?= GetMessage("MB_TASKS_MAIN_MENU_ITEM"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-calendar" id="calendar_list"
			onclick="calendarList(<?= $GLOBALS['USER']->GetID(); ?>);"><?= GetMessage("MB_CALENDAR_LIST"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-files" id="doc_user"
			onclick="webdavList('user/<? echo $GLOBALS['USER']->GetID(); ?>/');"><?= GetMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-msg" onclick="app.openRight();"><?= GetMessage("MB_MESSAGES"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-employees"
			onclick="userList();"><?= GetMessage("MB_COMPANY"); ?></div>
		<div class="menu-item menu-item-wrap menu-icon-files" id="doc_shared"
			onclick="webdavList('shared/');"><?= GetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"); ?></div>
	</div>
	<?if (IsModuleInstalled('crm') && CModule::IncludeModule('crm') && CCrmPerms::IsAccessEnabled()):
		$userPerms = CCrmPerms::GetCurrentUserPermissions();?>
		<div class="menu-separator">CRM</div>
		<div class="menu-section">
			<div class="menu-item menu-item-wrap menu-icon-mybusiness" id="crm_activity_list"
				data-url="/mobile/crm/activity/list.php"
				data-pageid="crm_activity_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_ACTIVITY')) ?></div>
			<? if (!$userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-contacts" id="crm_contact_list"
					data-url="/mobile/crm/contact/list.php"
					data-pageid="crm_contact_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_CONTACT')) ?></div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-company" id="crm_company_list"
					data-url="/mobile/crm/company/list.php"
					data-pageid="crm_company_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_COMPANY')) ?></div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-dealings" id="crm_deal_list"
					data-url="/mobile/crm/deal/list.php"
					data-pageid="crm_deal_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_DEAL')) ?></div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-invoice" id="crm_invoice_list"
					data-url="/mobile/crm/invoice/list.php"
					data-pageid="crm_invoice_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_INVOICE')) ?></div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-leads" id="crm_lead_list"
					data-url="/mobile/crm/lead/list.php"
					data-pageid="crm_lead_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_LEAD')) ?></div>
			<? endif; ?>
			<? if ($userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')): ?>
				<div class="menu-item menu-item-wrap menu-icon-products" id="crm_product_list"
					data-url="/mobile/crm/product/list.php"
					data-pageid="crm_product_list"><?= htmlspecialcharsbx(GetMessage('MB_CRM_PRODUCT')) ?></div>
			<? endif; ?>
		</div>
	<? endif; ?>
	<?
	if (is_array($arResult["GROUP_MENU"]) && count($arResult["GROUP_MENU"]) > 0):
		?>
		<div class="menu-separator"><?= GetMessage("MB_SEC_GROUPS"); ?></div>
		<div class="menu-section menu-section-groups"><?
		foreach ($arResult["GROUP_MENU"] as $key => $value):
			?>
			<div class="menu-item menu-item-wrap" data-url="<?= $value[1] ?>"><?= $value[0] ?></div><?
		endforeach;
		?></div><?
	endif;

	if (is_array($arResult["EXTRANET_MENU"]) && count($arResult["EXTRANET_MENU"]) > 0):
		?>
		<div class="menu-separator"><?= GetMessage("MB_SEC_EXTRANET"); ?></div>
		<div class="menu-section menu-section-groups"><?
		foreach ($arResult["EXTRANET_MENU"] as $key => $value):
			$id = rand(1, 10000);
			?>
			<div class="menu-item menu-item-wrap" data-url="<?= $value[1] ?>"><?= $value[0] ?></div><?
		endforeach;
		?></div><?
	endif;?>

	<div class="menu-separator"></div>

	<div class="menu-section">
		<? if (CMobile::$platform == "ios"): ?>
			<div class="menu-item menu-item-wrap menu-icon-help" id="help"
				data-url="<?=SITE_DIR?>mobile/help/"><?= GetMessage("MB_HELP"); ?></div>
		<? endif ?>
		<div id="mb_logout" class="menu-item menu-icon-logout"
			onclick="app.logOut();"><?= GetMessage("MB_EXIT"); ?></div>
	</div>

</div>
