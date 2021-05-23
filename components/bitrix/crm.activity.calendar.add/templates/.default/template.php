<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['ERROR_MESSAGE']))
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		$APPLICATION->RestartBuffer();
		?>
		<script type="text/javascript">
		top.BX.closeWait();
		<?
		if (!empty($arResult['EVENT_PAGE']))
		{
			?>top.location.href = '<?=CUtil::JSEscape($arResult['EVENT_PAGE'])?>';<?
		}
		?>
		top.BX.WindowManager.Get().Close();
		</script>
		<?
		die();
	}
}

?>
<form action="/bitrix/components/bitrix/crm.activity.calendar.add/component.ajax.php" target="add_calendarfr" name="load_form" method="post" enctype="multipart/form-data" style="padding-top: 10px;">
<?
if (!empty($arResult['ERROR_MESSAGE']))
{
	?><div class="crm-errors-calendar-err"><div>
		<?
			echo implode('<br />',$arResult['ERROR_MESSAGE']);
		?></div>
	</div><?
}
?>
<?=bitrix_sessid_post()?>
<input type="hidden" name="FORM_TYPE" value="<?=$arResult['FORM_TYPE']?>"/>
<input type="hidden" name="ENTITY_TYPE" value="<?=$arResult['ENTITY_TYPE']?>"/>
<?
if (is_array($arResult['ENTITY_ID'])):
	foreach($arResult['ENTITY_ID'] as $iEntitiID):
	?><input type="hidden" name="ENTITY_ID[]" value="<?=$iEntitiID?>"/><?
	endforeach;
else:
	?><input type="hidden" name="ENTITY_ID" value="<?=$arResult['ENTITY_ID']?>"/><?
endif;
?>
<input type="hidden" name="EVENT_PAGE" value="" id="EVENT_PAGE" />
<input type="hidden" name="add_calendar" value="Y"/>
<table cellspacing="0" cellpadding="0" border="0" width="100%"  class="bx-edit-table">
<tr class="bx-after-heading">
	<td class="bx-field-value bx-padding crm-one-row-text">
		<span class="required">*</span><?=GetMessage('CRM_CALENDAR_TOPIC')?>:
	</td>
	<td class="bx-field-value bx-padding">
		<input type="text" value="<? echo htmlspecialcharsbx($arResult['VALUES']['CALENDAR_TOPIC']); ?>" name="CALENDAR_TOPIC" id="CALENDAR_TOPIC" style="width: 100%;"/>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding crm-one-row-text">
		<span class="required">*</span><?=GetMessage('CRM_CALENDAR_DATE')?>:
	</td>
	<td class="bx-field-value bx-padding">
		<div class="event_date" id="crm_event_date" style="display: inline-block;text-decoration: none; border-bottom: 1px dashed #000;outline:none; cursor: pointer; color: #000" onclick="eventShowDateBox()"><? echo htmlspecialcharsex($arResult['VALUES']['CALENDAR_FROM']);?> - <? echo htmlspecialcharsex($arResult['VALUES']['CALENDAR_TO']);?></div>
		<div class="event_date_box" id="crm_event_date_box" style="display:none">
			<?$APPLICATION->IncludeComponent(
				'bitrix:main.calendar',
				'',
				array(
					'SHOW_INPUT' => 'Y',
					'FORM_NAME' => 'load_form',
					'INPUT_NAME' => 'CALENDAR_FROM',
					'INPUT_NAME_FINISH' => 'CALENDAR_TO',
					'INPUT_VALUE' => $arResult['VALUES']['CALENDAR_FROM'],
					'INPUT_VALUE_FINISH' => $arResult['VALUES']['CALENDAR_TO'],
					'SHOW_TIME' => 'Y'
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);?>
		</div>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" colspan="2">
		<input type="hidden" name="REMIND_FLAG" value="N">
		<input type="checkbox" name="REMIND_FLAG" value="Y" <? echo ('Y' == $arResult['VALUES']['REMIND_FLAG'] ? 'checked' : ''); ?> onclick="eventShowReminBox(this);">&nbsp;
		<? echo GetMessage('CRM_CALENDAR_REMIND')?>&nbsp;
		<span id="crm_event_remind_box" style="display: <? echo ('Y' == $arResult['VALUES']['REMIND_FLAG'] ? 'inline' : 'none'); ?>"><? echo GetMessage('CRM_CALENDAR_REMIND_FROM'); ?>&nbsp;
		<input type="text" value="<? echo intval($arResult['VALUES']['REMIND_LEN']); ?>" name="REMIND_LEN">&nbsp;<select name="REMIND_TYPE">
		<option value="min" <? echo ('min' == $arResult['VALUES']['REMIND_TYPE'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_REM_MIN') ?></option>
		<option value="hour" <? echo ('hour' == $arResult['VALUES']['REMIND_TYPE'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_REM_HOUR') ?></option>
		<option value="day" <? echo ('day' == $arResult['VALUES']['REMIND_TYPE'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_REM_DAY') ?></option>
		</select>
		</span>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding crm-one-row-text"><? echo GetMessage('BX_CRM_CACA_PRIORITY') ?>:</td>
	<td class="bx-field-value bx-padding"><select name="PRIORITY">
	<option value="high" <? echo ('high' == $arResult['VALUES']['PRIORITY'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_PRIORITY_HIGH') ?></option>
	<option value="normal" <? echo ('normal' == $arResult['VALUES']['PRIORITY'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_PRIORITY_NORMAL') ?></option>
	<option value="low" <? echo ('low' == $arResult['VALUES']['PRIORITY'] ? 'selected' : ''); ?>><? echo GetMessage('BX_CRM_CACA_PRIORITY_LOW') ?></option>
	</select></td>
</tr>
<tr>
	<td class="bx-field-value bx-padding" style="background-image: none;" colspan="2">
		<? echo GetMessage('CRM_CALENDAR_DESC')?>:
	</td>
</tr><tr>
	<td class="bx-field-value bx-padding event-desc" colspan="2">
		<textarea id="CALENDAR_DESC" name="CALENDAR_DESC" rows="7" style="width:100%"><? echo htmlspecialcharsbx($arResult['VALUES']['CALENDAR_DESC']); ?></textarea>
	</td>
</tr>
</table>
</form>
<script type="text/javascript">
	function eventShowReminBox(obj)
	{
		BX('crm_event_remind_box').style.display = (obj.checked ? 'inline-block' : 'none');
		BX.WindowManager.Get().adjustSizeEx();
	}
	function eventShowDateBox()
	{
		BX('crm_event_date').style.display = 'none';
		BX('crm_event_date_box').style.display = 'inline-block';
		BX.WindowManager.Get().adjustSizeEx();
	}

	BX('EVENT_PAGE').value = window.location.href;

	BX.WindowManager.Get().SetTitle('<?=GetMessage('CRM_CALENDAR_ADD_TITLE')?>');

	var _BTN = [
		{
			'title': "<?=GetMessage('CRM_CALENDAR_ADD_BUTTON');?>",
			'id': 'crm_calendar_add',
			'action': function () {
				var bSubmit = true;
				if (document.forms.load_form['CALENDAR_TOPIC'].value == '')
				{
					document.forms.load_form['CALENDAR_TOPIC'].className = 'border_red';
					bSubmit = false;
				}
				else
				{
					document.forms.load_form['CALENDAR_TOPIC'].className = '';
				}

				if (document.forms.load_form['CALENDAR_FROM'].value == '')
				{
					document.forms.load_form['CALENDAR_FROM'].className = 'border_red';
					bSubmit = false;
				}
				else
				{
					document.forms.load_form['CALENDAR_FROM'].className = '';
				}

				if (bSubmit)
				{
					BX('crm_calendar_add').disabled = false;
					this.parentWindow.PostParameters();
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
<iframe name="add_calendarfr" style="display: none; margin: 0; padding: 0; border: 0 none transparent; height: 0; font-size: 0.1em; line-height: 0.1em;"></iframe>