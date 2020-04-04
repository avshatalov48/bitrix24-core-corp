<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
	//TODO Get rid of it
	if ($_SERVER['REQUEST_METHOD'] == 'POST'):
		$APPLICATION->RestartBuffer();
		die();
	endif;

?>

<form action="" name="load_form" method="post" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<?if(isset($arResult['FREEZE_EVENT_ID']) && $arResult['FREEZE_EVENT_ID'] !== ''):?>
	<input type="hidden" name="EVENT_ID" value="<?=$arResult['FREEZE_EVENT_ID']?>"/>
<?endif;?>
<input type="hidden" name="FORM_ID" value="<?=$arResult['FORM_ID']?>"/>
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
<?if(!(isset($arResult['FREEZE_EVENT_ID']) && $arResult['FREEZE_EVENT_ID'] !== '')):?>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=GetMessage('CRM_EVENT_ADD_ID')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?=SelectBoxFromArray('EVENT_ID', $arResult['EVENT_TYPE'])?>
		<div id="EVENT_ID_NOTICE" class="crm-event-id-notice" style="display: none;">
		</div>
	</td>
</tr>
<?endif;?>
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
					//document.forms.load_form.submit();
					BX.ajax.submitAjax(
						document.forms.load_form,
						{
							method: "POST",
							url: "<?=SITE_DIR?>bitrix/components/bitrix/crm.event.add/box.php",
							processData: false,
							onsuccess: function()
							{
								BX.closeWait();

								var dialog = BX.WindowManager.Get();
								if(dialog)
								{
									dialog.Close(true);
								}

								var eventData =
								{
									url: "<?=CUtil::JSEscape($arResult['EVENT_PAGE'])?>",
									cancel: false
								};

								BX.onCustomEvent(window, "CrmBeforeEventPageReload", [ eventData ]);

								if(!eventData.cancel)
								{
									window.location.href = eventData.url;
								}
							}
						}
					);
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

	BX.ready(
			function()
			{
				BX.bind(
						BX('EVENT_ID'),
						'change',
						function(e)
						{
							var notice = BX('EVENT_ID_NOTICE');
							if(!notice)
							{
								return;
							}

							if(this.value === 'PHONE')
							{
								notice.innerHTML = '<?= CUtil::JSEscape(GetMessage('CRM_EVENT_PHONE_OBSOLETE'))?>';
								notice.style.display = '';
							}
							else if(this.value === 'MESSAGE')
							{
								notice.innerHTML = '<?= CUtil::JSEscape(GetMessage('CRM_EVENT_MESSAGE_OBSOLETE'))?>';
								notice.style.display = '';
							}
							else
							{
								notice.innerHTML = '';
								notice.style.display = 'none';
							}
						}
				);
			}
	);

</script>
<iframe name="add_event" style="display: none">
</iframe>
