<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
	if ($_SERVER['REQUEST_METHOD'] == 'POST'):
		$APPLICATION->RestartBuffer();
		?>
		<script type="text/javascript" src="/bitrix/js/main/core/core.js"></script>
		<script type="text/javascript">
		top.location.href = '<?=CUtil::JSEscape($arResult['EVENT_PAGE'])?>';
		top.BX.WindowManager.Get().Close();
		</script>
		<?
		die();
	endif;

?>

<form action="/bitrix/components/bitrix/crm.event.add/box.php" target="add_event" name="load_form" method="post" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="FORM_TYPE" value="<?=$arResult['FORM_TYPE']?>"/>
<input type="hidden" name="ENTITY_TYPE" value="<?=$arResult['ENTITY_TYPE']?>"/>
<input type="hidden" name="ENTITY_ID" value="<?=$arResult['ENTITY_ID']?>"/>
<input type="hidden" name="EVENT_PAGE" value="<?=$GLOBALS['APPLICATION']->GetCurPage();?>" id="EVENT_PAGE" />
<input type="hidden" name="add_event" value="Y"/>
<script>
var str = '';
</script>
<table cellspacing="0" cellpadding="0" border="0" width="100%"  class="bx-edit-table">
<tr class="bx-after-heading">
	<td class="bx-field-value bx-padding" style="width: 96px">
		<?=GetMessage('CRM_EVENT_TITLE_'.$arResult['ENTITY_TYPE'])?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<i><?=htmlspecialcharsbx($arResult['ENTITY_TITLE'])?></i>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_PHONE_LIST')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<input type="hidden" name="EVENT_ID" value="PHONE"/>
		<? $descrTemplate = GetMessage('CRM_CALL_DESCR'); ?>
		<?foreach($arResult['PHONE_GROUPS'] as $arPhoneGroup):?>
			<div class="crm-phone-group">
				<?$groupTitle = isset($arPhoneGroup['TITLE']) ? $arPhoneGroup['TITLE'] : ''; ?>
				<span class="crm-phone-name"><?= strlen($groupTitle) > 0 ? htmlspecialcharsbx($groupTitle).': ' : '' ?></span>
				<span class="crm-phone-number">
					<?if(!isset($arPhoneGroup['PHONES']) || count($arPhoneGroup['PHONES']) === 0): ?>
						<span class="crm-phone-text"><?= htmlspecialcharsbx(GetMessage('CRM_NO_PHONES'))?></span>
						<?continue;?>
					<?endif;?>
					<? $phoneCount = 0; ?>
					<?foreach($arPhoneGroup['PHONES'] as $arPhone):?>
						<?$phone =  isset($arPhone['NUMBER']) ? trim($arPhone['NUMBER']) : '';
						if(strlen($phone) === 0) continue;?>
						<span class="crm-phone-text"><?=($phoneCount > 0 ? ', ' : '').htmlspecialcharsbx(isset($arPhone['TITLE']) ? $arPhone['TITLE'] : '')?> </span>
						<a class="crm-phone-number-link" onclick="addToDescription('<?= htmlspecialcharsbx(str_replace(array('#NAME#', '#PHONE#'), array($groupTitle, $phone), $descrTemplate));?>');" href="<?=CCrmCallToUrl::Format(urlencode($arPhone['NUMBER']))?>"><?=htmlspecialcharsbx($arPhone['NUMBER'])?></a>
						<?$phoneCount++;?>
					<?endforeach;?>
				</span>
			</div>
		<?endforeach;?>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding event-desc" colspan="2">
		<textarea id="EVENT_DESC" name="EVENT_DESC" rows="7" style="width:100%"  onblur="if (value == '') {value = '<?=GetMessage('CRM_EVENT_DESC_TITLE');?>'; style.color = '#767676'}" onfocus="if (value == '<?=GetMessage('CRM_EVENT_DESC_TITLE')?>') {value = ''; style.color = '#000000'}" onkeyup="eventAddCheckText(this.value)" onkeydown="eventAddCheckText(this.value)"><?=GetMessage('CRM_EVENT_DESC_TITLE')?></textarea>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding event_date_text">
		<?=GetMessage('CRM_EVENT_DATE')?>:
	</td>
	<td class="bx-field-value bx-padding event_date_text2" style="height: 35px">
		<div class="event_date" id="crm_event_date" style="display: inline-block;text-decoration: none; border-bottom: 1px dashed #000;outline:none; cursor: pointer; color: #000" onclick="eventShowDateBox()"><?=ToLower(FormatDate("j F Y", time()));?></div>
		<div class="event_date_box" id="crm_event_date_box" style="display:none">
			<?$APPLICATION->IncludeComponent(
				'bitrix:main.calendar',
				'',
				array(
					'SHOW_INPUT' => 'Y',
					'FORM_NAME' => 'load_form',
					'INPUT_NAME' => 'EVENT_DATE',
					'INPUT_VALUE' => '',
					'SHOW_TIME' => 'Y'
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);?>
		</div>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding event-file" id="eventFileBox" colspan="2">
		<div class="event-file-title"><?=GetMessage('CRM_EVENT_ADD_FILE')?>:</div>
		<div><input type="file" name="ATTACH[]" onchange="eventAddFileInput(this)" /></div>
	</td>
</tr>
<?if(!empty($arResult['STATUS_LIST']) && $arResult['ENTITY_TYPE'] == 'LEAD'):?>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_EVENT_STATUS_ID')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?if ($arResult['ENTITY_CONVERTED'] == 'Y'):?>
			<?=$arResult['STATUS_LIST_EX'][$arResult['STATUS_ID']]?>
		<?else:?>
			<?=SelectBoxFromArray('STATUS_ID', $arResult['STATUS_LIST'], $arResult['STATUS_ID'])?>
		<?endif;?>
	</td>
</tr>
<?endif;?>
<?if(!empty($arResult['STAGE_LIST']) && $arResult['ENTITY_TYPE'] == 'DEAL'):?>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_EVENT_STAGE_ID')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=SelectBoxFromArray('STAGE_ID', $arResult['STAGE_LIST'], $arResult['STAGE_ID'])?>
	</td>
</tr>
<?endif;?>
<?if(!empty($arResult['STATUS_LIST']) && $arResult['ENTITY_TYPE'] == 'QUOTE'):?>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_EVENT_QUOTE_STATUS_ID')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=SelectBoxFromArray('STATUS_ID', $arResult['STATUS_LIST'], $arResult['STATUS_ID'])?>
	</td>
</tr>
<?endif;?>
</form>
<script type="text/javascript">
	function addToDescription(text)
	{
		if(!BX.type.isNotEmptyString(text))
		{
			return;
		}

		var el = BX('EVENT_DESC');

		if (el.value === '<?=GetMessage('CRM_EVENT_DESC_TITLE')?>')
		{
			el.value = '';
			el.style.color = '#000000'
		}

		if(BX.type.isNotEmptyString(el.value))
		{
			el.value += '\n';
		}

		el.value += text;
		eventAddCheckText(el.value);
	}

	var eventAddFile = false;
	function eventAddFileInput(el)
	{
		tableObject = BX('eventFileBox');
		divObject = document.createElement("div");
		divObject.innerHTML = el.parentNode.innerHTML;
		tableObject.appendChild(divObject);
		BX.WindowManager.Get().adjustSizeEx();
		BX('crm_event_add').disabled = false;
		eventAddFile = true;
	}
	function eventAddCheckText(text)
	{
		if (BX('EVENT_DESC').value != '')
			BX('crm_event_add').disabled = false;
		else if (!eventAddFile)
			BX('crm_event_add').disabled = true;
	}
	function eventShowDateBox()
	{
		BX('crm_event_date').style.display = 'none';
		BX('crm_event_date_box').style.display = 'inline-block';
		BX('EVENT_DATE').value = '<?=ConvertTimeStamp(time()+CTimeZone::GetOffset(), 'FULL')?>';
	}

	BX('EVENT_PAGE').value = window.location.href;

	BX.WindowManager.Get().SetTitle('<?=GetMessage('CRM_EVENT_ADD_TITLE')?>');

	var bSend = false;
	var _BTN = [
		{
			'title': "<?=GetMessage('CRM_EVENT_ADD_BUTTON');?>",
			'id': 'crm_event_add',
			'action': function () {
				if (!bSend)
				{
					if (BX('EVENT_DESC').value == '<?=GetMessage('CRM_EVENT_DESC_TITLE');?>')
						BX('EVENT_DESC').value = '';
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

	BX('crm_event_add').disabled = true;
	BX.WindowManager.Get().adjustSizeEx();
</script>
<iframe name="add_event" style="display: none"></iframe>