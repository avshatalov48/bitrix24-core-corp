<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	$APPLICATION->RestartBuffer();
	?>
	<script type="text/javascript">
			top.location.href = '<?=CUtil::JSEscape($arResult['BACK_URL'])?>';
	</script><?
	die();
endif;

?>
<form action="/bitrix/components/bitrix/crm.config.tax.settings/box.php" target="tax_settings" name="settings_form" method="post" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="BACK_URL" value="<?=$arResult['BACK_URL']?>" id="BACK_URL"/>
<table cellspacing="0" cellpadding="0" border="0" width="100%"  class="bx-edit-table">
<tr class="bx-after-heading">
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAX_SETTINGS_CHOOSE')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input type="radio" name="TAX_TYPE" value="tax"<?=!$arResult['IS_VAT_MODE'] ? ' checked' : ''?>><?=GetMessage('CRM_TAX_TAX1')?><br>
		<div  style="margin: 0 0 0 22px;"><small><?=GetMessage('CRM_TAX_TAX_HINT1')?></small></div><br>
		<input type="radio" name="TAX_TYPE" value="vat"<?=$arResult['IS_VAT_MODE'] ? ' checked' : ''?>><?=GetMessage('CRM_TAX_VAT')?><br>
		<div  style="margin: 0 0 0 22px;"><small><?=GetMessage('CRM_TAX_VAT_HINT1')?></small></div>
	</td>
</tr>
</table>
</form>

<script type="text/javascript">
	BX('BACK_URL').value = window.location.href;

	BX.WindowManager.Get().SetTitle("<?=GetMessage('CRM_TAX_SETTINGS_TITLE');?>");

	var bSend = false;
	var _BTN = [
		{
			'title': "<?=GetMessage('CRM_TAX_SETTINGS_SAVE_BUTTON');?>",
			'id': 'crm_tax_settings',
			'action': function () {
				if (!bSend)
				{
					bSend = true;
					document.forms.settings_form.submit();
					BX.showWait();
				}
			}
		},
		BX.CDialog.btnCancel
	];

	BX.WindowManager.Get().ClearButtons();
	BX.WindowManager.Get().SetButtons(_BTN);
	BX.WindowManager.Get().adjustSizeEx();
</script>

<iframe name="tax_settings" style="display: none">
</iframe>
