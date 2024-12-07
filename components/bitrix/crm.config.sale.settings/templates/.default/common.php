<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Bitrix\Main\UI\Extension::load("ui.buttons");
Bitrix\Main\UI\Extension::load("ui.notification");

$settings = $arResult["SETTINGS"];
$pageSettings = $arResult["PAGE_SETTINGS"];

?>

<div class="crm-sale-settings-container">
<form method="POST" id="common_sale_settings_form">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="common_sale_settings" value="Y">

	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => "config_sale_settings",
			"TABS" => $settings["TABS"],
			"SHOW_SETTINGS" => false,
			"USE_THEMES" => false
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);
	?>

	<div class="crm-sale-settings-button-container">
		<span id="common_sale_settings_apply_button" class="ui-btn ui-btn-success">
			<?=Loc::getMessage("CRM_SALE_SETTINGS_BUTTON_APPLY")?>
		</span>

		<? if ($_REQUEST["IFRAME"] == "Y"): ?>
			<span id="common_sale_settings_close_button" class="ui-btn ui-btn-link">
				<?=Loc::getMessage('CRM_SALE_SETTINGS_BUTTON_CLOSE')?>
			</span>
		<? endif; ?>
	</div>

</form>
</div>

<script>
	BX.ready(function() {
		new BX.Crm.CommonSaleSettings({
			ajaxUrl: "<?= $pageSettings["AJAX_URL"] ?>",
			isFramePopup: "<?=($_REQUEST["IFRAME"] == "Y" ? "Y" : "N")?>",
			optionPrefix: "<?=$pageSettings["OPTION_PREFIX"]?>",
			listSiteId: <?=Json::encode($pageSettings["LIST_SITE_ID"])?>,
			languageId: "<?=$pageSettings["LANGUAGE_ID"]?>"
		});

		BX.message({
			CRM_SALE_SETTINGS_SAVE_SUCCESS: "<?= GetMessageJS("CRM_SALE_SETTINGS_SAVE_SUCCESS") ?>",
			CRM_SALE_SETTINGS_BUTTON_CLOSE: "<?= GetMessageJS("CRM_SALE_SETTINGS_BUTTON_CLOSE") ?>",
			CRM_SALE_PRODUCT_SETTINGS_TITLE: "<?= GetMessageJS("CRM_SALE_PRODUCT_SETTINGS_TITLE") ?>"
		});
	});
</script>