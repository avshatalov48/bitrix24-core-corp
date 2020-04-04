<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (strlen($arResult["FatalErrorMessage"]) > 0)
{
	ShowError($arResult["FatalErrorMessage"]);
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
		ShowError($arResult["ErrorMessage"]);

	$arButtons = array(
		array(
			"TEXT"=>GetMessage("BPWC_WNCT_2LIST"),
			"TITLE"=>GetMessage("BPWC_WNCT_2LIST"),
			"LINK"=>$arResult["PATH_TO_INDEX"],
			"ICON"=>"btn-list",
		),
	);
	if ($arResult["PATH_TO_SYNC"] != "")
	{
		$arButtons[] = array(
			"TEXT"=>GetMessage("BPWC_WNCT_2SYNC"),
			"TITLE"=>GetMessage("BPWC_WNCT_2SYNC"),
			"LINK"=>$arResult["PATH_TO_SYNC"],
			"ICON"=>"btn-import",
		);
	}
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS" => $arButtons
		),
		$component
	);
	?>

	<?
	$arResult["FORM_ID"] = "form_crm_config_ext_sale_edit";

	$urlValue = '<select class="crm-config-ext-sale-scheme" name="SCHEME">
		<option value="http"'.(($arResult["BP"]["SCHEME"]=="http") ? " selected" : "").'>http</option>
		<option value="https"'.(($arResult["BP"]["SCHEME"]=="https") ? " selected" : "").'>https</option>
		</select><span class="crm-config-ext-sale-text">://</span><input type="text" name="SERVER" class="crm-config-ext-sale-server"
		value="'.$arResult["BP"]["SERVER"].'" size="40"><span class="crm-config-ext-sale-text">:</span><input type="text" name="PORT" class="crm-config-ext-sale-port"
		value="'.$arResult["BP"]["PORT"].'" size="5">';
	$passValue = '<input type="password" name="PASSWORD" size="30" autocomplete="off">';

	$lastStatusValue = "";
	if (intval($arResult["BP"]["MODIFICATION_LABEL"]) == 0)
		$lastStatusValue .= "<font class='errortext'>".GetMessage("BPWC_WLC_NEED_FIRST_SYNC1")."</font><br /><a href='".$arResult["PATH_TO_SYNC"]."'>".GetMessage("BPWC_WLC_NEED_FIRST_SYNC1_DO")."</a><br />";
	if ($arResult["BP"]["LAST_STATUS"] != "" && strtolower(substr($arResult["BP"]["LAST_STATUS"], 0, strlen("success"))) != "success")
		$lastStatusValue .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC3").$arResult["BP"]["LAST_STATUS"];
	if ($lastStatusValue == "")
		$lastStatusValue .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC2");

	$modificationLabelValue = ConvertTimeStamp($arResult["BP"]["MODIFICATION_LABEL"], "FULL");

	ob_start();
	$arUser = false;
	if (intval($arResult["BP"]["IMPORT_RESPONSIBLE"]) > 0)
	{
		$dbUser = CUser::GetByID($arResult["BP"]["IMPORT_RESPONSIBLE"]);
		$arUser = $dbUser->GetNext();
	}
	?>
	<script type="text/javascript">
		function __BXOnImportResponsibleChange()
		{
			var ddd = document.getElementById("id_IMPORT_RESPONSIBLE_TXT");
			ddd.innerHTML = BX.util.htmlspecialchars(arguments[0]["name"]);
			document.getElementById("id_IMPORT_RESPONSIBLE").value = arguments[0]["id"];
			window.BXMembersSelector.close();
		}

		function __BXOnImportResponsibleShow(el)
		{
			if (!window.BXMembersSelector)
			{
				window.BXMembersSelector = BX.PopupWindowManager.create("members-popup", el, {
					offsetTop : 1,
					autoHide : true,
					closeByEsc : true,
					content : BX("IMPORT_RESPONSIBLE_selector_content")
				});
			}

			if (window.BXMembersSelector.popupContainer.style.display != "block")
			{
				window.BXMembersSelector.show();
			}
		}
	</script>
	<a onclick="javascript:__BXOnImportResponsibleShow(this)" class="crm-field-action-link" id="id_IMPORT_RESPONSIBLE_TXT"><?= $arUser ? $arUser["NAME"]." ".$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")" : GetMessage("BPWC_WNCT_DO_SELECT") ?></a><?
	$GLOBALS["APPLICATION"]->IncludeComponent(
		'bitrix:intranet.user.selector.new',
		'',
		array(
			'NAME' => 'IMPORT_RESPONSIBLE',
			'VALUE' => $arResult["BP"]["IMPORT_RESPONSIBLE"],
			'MULTIPLE' => 'N',
			"POPUP" => "Y",
			"SITE_ID" => SITE_ID,
			"ON_SELECT" => "__BXOnImportResponsibleChange",
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	?>
	<input type="hidden" name="IMPORT_RESPONSIBLE" id="id_IMPORT_RESPONSIBLE" value="<?= $arResult["BP"]["IMPORT_RESPONSIBLE"] ?>">
	<?
	$responsibleValue = ob_get_contents();
	ob_end_clean();

	ob_start();
	$arGroup = false;
	if (intval($arResult["BP"]["IMPORT_GROUP_ID"]) > 0)
		$arGroup = CSocNetGroup::GetByID($arResult["BP"]["IMPORT_GROUP_ID"]);
	?>
	<script type="text/javascript">
	function __BXOnImportGroupChange()
	{
		var ddd = document.getElementById("id_GROUP_TXT");
		ddd.innerHTML = arguments[0][0]["title"];
		document.getElementById("id_IMPORT_GROUP_ID").value = arguments[0][0]["id"];
		
		if (BX("id_IMPORT_GROUP_CONT"))
		{
			BX.removeClass(BX("id_IMPORT_GROUP_CONT"), "crm-field-group-cont-empty");
		}
	}

	function __BXOnImportGroupClear()
	{
		if (BX("id_IMPORT_GROUP_ID"))
		{
			BX("id_IMPORT_GROUP_ID").value = "";
		}

		if (BX("id_GROUP_TXT"))
		{
			BX("id_GROUP_TXT").innerHTML = "<?=GetMessage("BPWC_WNCT_DO_SELECT")?>";
		}

		if (BX("id_IMPORT_GROUP_CONT"))
		{
			BX.addClass(BX("id_IMPORT_GROUP_CONT"), "crm-field-group-cont-empty");
		}
	}

	function __BXOnImportGroupShow()
	{
		groupsPopup.show();
	}
	</script>
	<div class="crm-field-group-cont<?=(intval($arResult["BP"]["IMPORT_GROUP_ID"]) <= 0 ? " crm-field-group-cont-empty" : "")?>" id="id_IMPORT_GROUP_CONT"><a onclick="javascript:__BXOnImportGroupShow()" class="crm-field-action-link" id="id_GROUP_TXT"><?= $arGroup ? $arGroup["NAME"] : GetMessage("BPWC_WNCT_DO_SELECT") ?></a><span class="crm-field-clear" onclick="__BXOnImportGroupClear();"></span></div>
	<?
	$name = $APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.group.selector", ".default", array(
				"BIND_ELEMENT" => "id_GROUP_TXT",
				"ON_SELECT" => "__BXOnImportGroupChange",
				"SELECTED" => $arResult["BP"]["IMPORT_GROUP_ID"]
			), null, array("HIDE_ICONS" => "Y")
		);
	?>
	<input type="hidden" name="IMPORT_GROUP_ID" id="id_IMPORT_GROUP_ID" value="<?= $arResult["BP"]["IMPORT_GROUP_ID"] ?>" />
	<?
	$groupValue = ob_get_contents();
	ob_end_clean();

	$arFieldsTmp1 = array();
	$arFieldsTmp2 = array();
	if ($arParams["ID"] > 0)
	{
		$arFieldsTmp1[] = array("id" => "LAST_STATUS", "name" => GetMessage("BPWC_WNCT_STATUS"), "type" => "custom", "value" => $lastStatusValue);
		$arFieldsTmp1[] = array("id" => "ID", "name" => "ID", "type" => "label");
		$arFieldsTmp1[] = array("id" => "DATE_CREATE", "name" => GetMessage("BPWC_WNCT_DATE_CREATE"), "type" => "label");
		$arFieldsTmp1[] = array("id" => "DATE_UPDATE", "name" => GetMessage("BPWC_WNCT_DATE_UPDATE"), "type" => "label");
		//$arFieldsTmp1[] = array("id" => "MODIFICATION_LABEL", "name" => GetMessage("BPWC_WNCT_LABEL"), "type" => "custom", "value" => $modificationLabelValue);
		$arFieldsTmp1[] = array("id" => "LAST_STATUS_DATE", "name" => GetMessage("BPWC_WNCT_LAST_STATUS_DATE"), "type" => "label");
	}
	$arFieldsTmp1[] = array("id" => "URL", "name" => GetMessage("BPWC_WNCT_URL"), "type" => "custom", 'required' => true, "value" => $urlValue);
	$arFieldsTmp1[] = array("id" => "LOGIN", "name" => GetMessage("BPWC_WNCT_LOGIN"), "type" => "text", 'required' => true);
	$arFieldsTmp1[] = array("id" => "PASSWORD", "name" => GetMessage("BPWC_WNCT_PASSWORD"), "type" => "custom", "value" => $passValue, 'required' => ($arParams["ID"] <= 0));
	$arFieldsTmp1[] = array("id" => "NAME", "name" => GetMessage("BPWC_WNCT_NAME"), "type" => "text");
	$arFieldsTmp1[] = array("id" => "ACTIVE", "name" => GetMessage("BPWC_WNCT_ACTIVE"), "type" => "checkbox");
	$arFieldsTmp2[] = array("id" => "IMPORT_SIZE", "name" => GetMessage("BPWC_WNCT_SIZE"), "type" => "text", "params" => array("disabled" => "disabled"));
	$arFieldsTmp2[] = array("id" => "IMPORT_PERIOD", "name" => GetMessage("BPWC_WNCT_IMPORT_PERIOD"), "type" => "text");
	$arFieldsTmp2[] = array("id" => "IMPORT_PROBABILITY", "name" => GetMessage("BPWC_WNCT_IMPORT_PROBABILITY"), "type" => "text");
	$arFieldsTmp2[] = array("id" => "IMPORT_RESPONSIBLE", "name" => GetMessage("BPWC_WNCT_IMPORT_RESPONSIBLE"), "type" => "custom", "value" => $responsibleValue);
	$arFieldsTmp2[] = array("id" => "IMPORT_GROUP_ID", "name" => GetMessage("BPWC_WNCT_IMPORT_GROUP_ID"), "type" => "custom", "value" => $groupValue);
	$arFieldsTmp2[] = array("id" => "IMPORT_PUBLIC", "name" => GetMessage("BPWC_WNCT_IMPORT_PUBLIC"), "type" => "checkbox");
	$arFieldsTmp2[] = array("id" => "IMPORT_PREFIX", "name" => GetMessage("BPWC_WNCT_IMPORT_PREFIX"), "type" => "text");
	$arFieldsTmp1[] = array("id" => "DATA_SYNC_PERIOD", "name" => GetMessage("BPWC_WNCT_DATA_SYNC_PERIOD"), "type" => "text");

	$arTabs = array(
		array(
			"id" => "tab1", "name" => GetMessage("BPWC_WNCT_TAB1"), "title" => GetMessage("BPWC_WNCT_TAB1T"), "icon" => "",
			"fields" => $arFieldsTmp1
		),
		array(
			"id" => "tab2", "name" => GetMessage("BPWC_WNCT_TAB2"), "title" => GetMessage("BPWC_WNCT_TAB2T"), "icon" => "",
			"fields" => $arFieldsTmp2
		),
	);
?>
<div class="crm-config-ext-sale-edit">
<?	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => $arResult["FORM_ID"],
			"TABS" => $arTabs,
			"BUTTONS" => array(
				"custom_html" => "",
				"standard_buttons" => !$arResult["DAS_IST_SHOP_LIMIT"],
				'back_url' => $arResult['PATH_TO_INDEX'],
			),
			"DATA" => $arResult["BP"],
			"SHOW_SETTINGS" => "Y",
		),
		$component
	);
}
?>
</div>
<?echo BeginNote();?>
<?= GetMessage("BPWC_WNCT_NOTE_HINT")?>
<?echo EndNote(); ?>
