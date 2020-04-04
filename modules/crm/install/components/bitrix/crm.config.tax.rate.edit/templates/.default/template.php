<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	$APPLICATION->RestartBuffer();
	?>
	<script type="text/javascript">
		<?if(strlen($arResult['ERROR_MSG']) > 0 ):?>
			alert("<?=$arResult['ERROR_MSG']?>");
			BX.closeWait();
		<?else:?>
			top.location.href = '<?=CUtil::JSEscape($arResult['RATE_PAGE'])?>';
		<?endif;?>
	</script><?
	die();
endif;

?>
<form action="/bitrix/components/bitrix/crm.config.tax.rate.edit/box.php" target="add_taxrate" name="load_form" method="post" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="FORM_ID" value="<?=$arResult['FORM_ID']?>"/>
<input type="hidden" name="TAX_ID" value="<?=$arResult['TAX_ID']?>"/>
<input type="hidden" name="ID" value="<?=$arResult['ID']?>"/>
<input type="hidden" name="RATE_PAGE" value="<?=$GLOBALS['APPLICATION']->GetCurPage()?>" id="RATE_PAGE" />
<input type="hidden" name="add_taxrate" value="Y"/>
<script>
var str = '';
</script>
<table cellspacing="0" cellpadding="0" border="0" width="100%"  class="bx-edit-table">
<tr class="bx-after-heading">
	<?if($arResult['ID'] > 0):?>
			<td class="bx-field-value bx-padding" style="width: 96px">
				<?=ID?>:
			</td>
			<td class="bx-field-value bx-padding" style="">
				<?=$arResult['ID']?>
			</td>
		</tr>
		<tr>
	<?endif;?>

	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAXRATE_FIELDS_TAX')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<?=$arResult['TAX_NAME']?>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_TAXRATE_FIELDS_ACTIVE')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<input type="checkbox" name="ACTIVE" value="Y" <?=$arResult['ACTIVE'] ? 'checked' : ''?>>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAXRATE_FIELDS_PERSON_TYPE_ID')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<select name="PERSON_TYPE_ID">
			<?foreach ($arResult['PERSON_TYPES_LIST'] as $key => $value):?>
				<option value="<?=$key?>"<?=$arResult['PERSON_TYPE_ID'] == $key ? 'selected' : ''?>><?=$value?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr class="crm-required-field">
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAXRATE_FIELDS_VALUE')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input id ="VALUE" type="text" name="VALUE" value="<?=$arResult['VALUE']?>" size="10" onkeyup="onFieldFill();">%
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAXRATE_FIELDS_IS_IN_PRICE')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input type="checkbox" name="IS_IN_PRICE" value="Y" <?=$arResult['IS_IN_PRICE'] ? 'checked' : ''?>>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_TAXRATE_FIELDS_APPLY_ORDER')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input type="text" name="APPLY_ORDER" value="<?=$arResult['APPLY_ORDER']?>" size="10">
	</td>
</tr>
<?if(CSaleLocation::isLocationProEnabled()):?>

	<tr class="crm-required-field">
		<td colspan="2">

			<?$APPLICATION->IncludeComponent("bitrix:sale.location.selector.system", "", array(
					"ENTITY_PRIMARY" => $arResult['ID'],
					"LINK_ENTITY_NAME" => CSaleTaxRate::CONN_ENTITY_NAME,
					"INPUT_NAME" => 'LOCATION',
					"SELECTED_IN_REQUEST" => $arResult['LOCATION_QUERY'],
					"JS_CONTROL_GLOBAL_ID" => 'tax-location-selector',
					"PATH_TO_LOCATION_IMPORT" => "/crm/configs/locations/import/"
				),
				false
			);?>

		</td>
	</tr>

<?else:?>

	<tr class="crm-required-field">
		<td class="bx-field-value bx-padding" style="width: 96px">
			<?=GetMessage('CRM_TAXRATE_FIELDS_LOCATION1')?>:
		</td>
		<td class="bx-field-value bx-padding" style="">
			<select id="LOCATION1" name="LOCATION1[]" size="5" multiple onclick = "onFieldFill();">
				<?foreach ($arResult['LOCATION1_LIST'] as $locID => $arLocation):?>
					<option value="<?=$locID?>"<?=$arLocation['SELECTED'] ? ' selected' : ''?>><?=htmlspecialcharsbx($arLocation["STRING"])?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>

-<?endif?>
</table>
</form>
<script type="text/javascript">

	<?if(CSaleLocation::isLocationProEnabled()):?>
		BX.locationSelectors['tax-location-selector'].bindEvent('after-target-input-modified', function(){
			onFieldFill();
		});
	<?endif?>

	function onFieldFill()
	{
		disableButton(!checkFormFields());
	}
	function checkFormFields()
	{
		if(!checkTextField(BX('VALUE')))
			return false;

		<?if(CSaleLocation::isLocationProEnabled()):?>

			if(!BX.locationSelectors['tax-location-selector'].checkSmthSelected())
				return false;

		<?else:?>

			if(!checkSelectField(BX('LOCATION1')))
				return false;

		<?endif?>

		return true;
	}

	function disableButton(bDisable)
	{
		BX('crm_taxrate_add').disabled = bDisable
	}

	function checkTextField(textObj)
	{
		if(textObj.value == '')
			return false;

		if(/[^0-9.,\s]/.test(textObj.value) || BX.util.trim(textObj.value) == '')
		{
			alert("<?=GetMessage('CRM_TAXRATE_FIELDS_VALUE_CHECK')?>");
			return false;
		}

		return true;
	}

	function checkSelectField(selectObj)
	{
		for ( var i = 0, l = selectObj.options.length; i < l; i++ )
			if (selectObj.options[i].selected)
				return true;

		return false;
	}

	BX('RATE_PAGE').value = window.location.href;

	BX.WindowManager.Get().SetTitle("<?=$arResult['ID'] ? GetMessage('CRM_TAXRATE_TITLE_EDIT') : GetMessage('CRM_TAXRATE_TITLE')?>");

	var bSend = false;
	var _BTN = [
		{
			'title': "<?=GetMessage('CRM_TAXRATE_SAVE_BUTTON');?>",
			'id': 'crm_taxrate_add',
			'action': function () {
				if (!bSend)
				{
					bSend = true;
					document.forms.load_form.submit();
					BX.showWait();
				}
			}
		},
		BX.CDialog.btnCancel
	];

	BX.WindowManager.Get().ClearButtons();
	BX.WindowManager.Get().SetButtons(_BTN);

	<?if($arResult['ID'] <= 0):?>
		BX('crm_taxrate_add').disabled = true;
	<?endif;?>

	BX.WindowManager.Get().adjustSizeEx();

</script>
<iframe name="add_taxrate" style="display: none">
</iframe>
