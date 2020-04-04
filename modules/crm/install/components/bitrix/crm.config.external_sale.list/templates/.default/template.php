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
	if (strlen($arResult["SuccessMessage"]) > 0)
		ShowMessage(array("MESSAGE" => $arResult["SuccessMessage"], "TYPE" => "OK"));

	$arButtons = array(
		array(
			"TEXT"=>GetMessage("BPWC_WLCT_NEW"),
			"TITLE"=>GetMessage("BPWC_WLCT_NEW"),
			"LINK"=>$arResult["PATH_TO_EDIT"],
			"ICON"=>"btn-new",
		),
		array(
			"TEXT"=>GetMessage("BPWC_WLCT_NEWW"),
			"TITLE"=>GetMessage("BPWC_WLCT_NEWW"),
			"LINK"=>"javascript:bxExtSaleWizard.Start('".SITE_ID."');",
			"ICON"=>"btn-new",
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

	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.grid",
		"",
		array(
			"GRID_ID"=>$arResult["GRID_ID"],
			"HEADERS"=>$arResult["HEADERS"],
			"SORT"=>$arResult["SORT"],
			"ROWS"=>$arResult["RECORDS"],
			"FOOTER"=>array(array("title"=>GetMessage("BPWC_WLCT_TOTAL"), "value"=>$arResult["ROWS_COUNT"])),
			"ACTIONS"=>array("delete"=>true, "list"=>array()),
			"ACTION_ALL_ROWS"=>false,
			"EDITABLE"=>false,
			"NAV_OBJECT"=>$arResult["NAV_RESULT"],
			"AJAX_MODE"=>"Y",
			"AJAX_OPTION_JUMP"=>"N",
		),
		$component
	);
}

if ($_REQUEST["do_show_wizard"] == "Y")
{
	?>
	<script type="text/javascript">
		BX.ready(function(){bxExtSaleWizard.Start('<?= SITE_ID ?>', 2, <?= intval($_REQUEST["do_show_wizard_id"]) ?>);});
	</script>
	<?
}
?>