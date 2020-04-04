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
		array(
			"TEXT"=>GetMessage("BPWC_WNCT_2EDIT"),
			"TITLE"=>GetMessage("BPWC_WNCT_2EDIT"),
			"LINK"=>$arResult["PATH_TO_EDIT"],
			"ICON"=>"btn-edit",
		),
	);
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS" => $arButtons
		),
		$component
	);
	?>

	<div id="id_div_error_message" style="color:red; display: none;"><?= GetMessage("BPWC_WNCT_SYNC_ERROR_SYNC"); ?></div>
	<div id="id_div_success_message" style="color:green; display: none;"><?= GetMessage("BPWC_WNCT_SYNC_SUCCESS_SYNC"); ?></div>

	<?
	$arResult["FORM_ID"] = "form_crm_config_ext_sale_sync";

	$arFieldsTmp1 = array();
	$arFieldsTmp1[] = array("id" => "ID", "name" => "ID", "type" => "label");
	$arFieldsTmp1[] = array("id" => "NAME", "name" => GetMessage("BPWC_WNCT_NAME"), "type" => "label");

	$urlValue = $arResult["BP"]["SCHEME"].'://'.$arResult["BP"]["SERVER"].':'.$arResult["BP"]["PORT"];
	$arFieldsTmp1[] = array("id" => "URL", "name" => GetMessage("BPWC_WNCT_URL"), "type" => "custom", "value" => $urlValue);

	ob_start();
	?>
	<table>
		<tr>
			<td><?= GetMessage("BPWC_WNCT_SYNC_DEALS"); ?>:</td>
			<td id="id_stat_load_deal">0</td>
		</tr>
		<tr>
			<td><?= GetMessage("BPWC_WNCT_SYNC_CONTACTS"); ?>:</td>
			<td id="id_stat_load_contact">0</td>
		</tr>
		<tr>
			<td><?= GetMessage("BPWC_WNCT_SYNC_COMPANIES"); ?>:</td>
			<td id="id_stat_load_company">0</td>
		</tr>
		<tr id="id_es_progress_stop" style="display: none;">
			<td colspan="2">
				<br />
				<img src="/bitrix/components/bitrix/crm.config.external_sale/images/pb1.gif" alt="Loading..."/><br />
				<a href="javascript:ExtSaleDoStop()" id="id_link_stop_load"><?= GetMessage("BPWC_WNCT_SYNC_STOP_LOAD"); ?></a>
			</td>
		</tr>
	</table>
	<?
	$statusValue = ob_get_contents();
	ob_end_clean();
	$arFieldsTmp1[] = array("id" => "STATUS", "name" => GetMessage("BPWC_WNCT_STATUS_CUR"), "type" => "custom", "value" => $statusValue);

	$arTabs = array(
		array(
			"id" => "tab1", "name" => GetMessage("BPWC_WNCTS_TAB1"), "title" => GetMessage("BPWC_WNCTS_TAB1T"), "icon" => "",
			"fields" => $arFieldsTmp1
		),
	);

	$formCustomHtml = "<input type='button' onclick='ExtSaleDoSyncStart();' id='id_do_import_btn' value='".GetMessage("BPWC_WNCTS_BTN_SYNC")."'/> ".
		"<input type='button' value='".GetMessage("BPWC_WNCTS_BTN_CANCEL")."' id='id_do_cancel_btn' onclick=\"window.location='".htmlspecialcharsbx(CUtil::addslashes($arResult['PATH_TO_INDEX']))."'\"/>";
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => $arResult["FORM_ID"],
			"TABS" => $arTabs,
			"BUTTONS" => array(
				"custom_html" => $formCustomHtml,
				"standard_buttons" => false,
				'back_url' => $arResult['PATH_TO_INDEX'],
			),
			"DATA" => $arResult["BP"],
			"SHOW_SETTINGS" => "N",
		),
		$component
	);
}
?>
<script type="text/javascript">
	var extSaleSyncStep = 0;
	var statLoadDeal = 0;
	var statLoadContact = 0;
	var statLoadCompany = 0;
	var extSaleStop = false;

	function ExtSaleDoSyncStart()
	{
		if (extSaleSyncStep != 0)
			return;

		statLoadDeal = 0;
		statLoadContact = 0;
		statLoadCompany = 0;
		ExtSaleDoStat();

		ExtSaleDoBtns(true);

		extSaleStop = false;

		document.getElementById("id_link_stop_load").innerHTML = "<?= GetMessage("BPWC_WNCT_SYNC_STOP_LOAD"); ?>";

		document.getElementById("id_div_error_message").style.display = "none";
		document.getElementById("id_div_success_message").style.display = "none";
		document.getElementById("id_es_progress_stop").style.display = "";

		document.getElementById("id_div_success_message").innerHTML = "<?= GetMessage("BPWC_WNCT_SYNC_SUCCESS_SYNC"); ?>";
		document.getElementById("id_div_error_message").innerHTML = "<?= GetMessage("BPWC_WNCT_SYNC_ERROR_SYNC"); ?>";

		ExtSaleDoSync();
	}

	function ExtSaleDoStat()
	{
		document.getElementById("id_stat_load_deal").innerHTML = statLoadDeal;
		document.getElementById("id_stat_load_contact").innerHTML = statLoadContact;
		document.getElementById("id_stat_load_company").innerHTML = statLoadCompany;
	}

	function ExtSaleDoBtns(val)
	{
		document.getElementById("id_do_import_btn").disabled = val;
		document.getElementById("id_do_cancel_btn").disabled = val;
	}

	function ExtSaleDoStop()
	{
		document.getElementById("id_link_stop_load").innerHTML = "<?= GetMessage("BPWC_WNCT_SYNC_STOPPING"); ?>...";
		extSaleStop = true;
	}

	function ExtSaleDoSync()
	{
		BX.showWait();

		BX.ajax.get(
			"/bitrix/components/bitrix/crm.config.external_sale/ajax.php",
			{
				id:<?=$arResult["BP"]["ID"]?>,
				skip_bp:"<?=(intval($arResult["BP"]["MODIFICATION_LABEL"]) == 0) ? "Y" : "N"?>",
				skip_notify:"<?=(intval($arResult["BP"]["MODIFICATION_LABEL"]) == 0) ? "Y" : "N"?>",
				timestamp:(new Date()).getTime() //for preventing request caching
			},
			function(v)
			{
				ExtSaleDoSyncResult(ExtSaleDoPrepareResponse(v));
			}
		);
	}

	function ExtSaleDoPrepareResponse(v)
	{
		v = v.replace(/^\s+|\s+$/g, '');
		while (v.length > 0 && v.charCodeAt(0) == 65279)
			v = v.substring(1);

		if (v.length <= 0)
			return undefined;

		try
		{
			eval("v1 = " + v);
		}
		catch (e)
		{
			alert(v);
			return undefined;
		}

		return v1;
	}

	function ExtSaleDoSyncResult(result)
	{
		BX.closeWait();
		if ((result != undefined) && (result["result"] != undefined))
		{
			if (result["result"] == 0 || result["result"] == 1)
			{
				statLoadDeal += parseInt(result["details"]["CreatedDeals"]) + parseInt(result["details"]["UpdatedDeals"]);
				statLoadContact += parseInt(result["details"]["CreatedContacts"]) + parseInt(result["details"]["UpdatedContacts"]);
				statLoadCompany += parseInt(result["details"]["CreatedCompanies"]) + parseInt(result["details"]["UpdatedCompanies"]);

				ExtSaleDoStat();

				if (result["result"] == 0 || extSaleStop)
				{
					ExtSaleDoBtns(false);
					document.getElementById("id_es_progress_stop").style.display = "none";
					document.getElementById("id_div_success_message").style.display = "";
					if (extSaleStop)
						document.getElementById("id_div_success_message").innerHTML = "<?= GetMessage("BPWC_WNCT_SYNC_TERMINATE"); ?>";
					extSaleSyncStep = 0;
				}
				else
				{
					extSaleSyncStep++;
					ExtSaleDoSync();
				}
			}
			else
			{
				ExtSaleDoBtns(false);
				document.getElementById("id_es_progress_stop").style.display = "none";
				document.getElementById("id_div_error_message").style.display = "";
				document.getElementById("id_div_error_message").innerHTML += "<br />" + result["errors"];
				extSaleSyncStep = 0;
			}
		}
		else
		{
			ExtSaleDoBtns(false);
			document.getElementById("id_es_progress_stop").style.display = "none";
			document.getElementById("id_div_error_message").style.display = "";
			extSaleSyncStep = 0;
		}
	}
</script>
