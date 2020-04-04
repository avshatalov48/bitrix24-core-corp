<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$startInterval = \Bitrix\Crm\Automation\Helper::parseDateTimeInterval($dialog->getCurrentValue($map['StartTime']['FieldName']));
$day = $startInterval['d'];
$time = ($startInterval['h'] > 0 ? $startInterval['h'] : 12).':'.($startInterval['i'] > 0 ? $startInterval['i'] : 0);
?>
<div class="crm-automation-popup-settings">
	<input name="<?=htmlspecialcharsbx($map['Subject']['FieldName'])?>" type="text" class="crm-automation-popup-input"
		value="<?=htmlspecialcharsbx($dialog->getCurrentValue($map['Subject']['FieldName']))?>"
		placeholder="<?=htmlspecialcharsbx($map['Subject']['Name'])?>"
		data-role="inline-selector-target"
	>
</div>
<div class="crm-automation-popup-settings">
<textarea name="<?=htmlspecialcharsbx($map['Description']['FieldName'])?>"
		class="crm-automation-popup-textarea"
		placeholder="<?=htmlspecialcharsbx($map['Description']['Name'])?>"
		data-role="inline-selector-target"
><?=htmlspecialcharsbx($dialog->getCurrentValue($map['Description']['FieldName']))?></textarea>
</div>
<div class="crm-automation-popup-settings">
	<input name="<?=htmlspecialcharsbx($map['Location']['FieldName'])?>" type="text" class="crm-automation-popup-input"
		value="<?=htmlspecialcharsbx($dialog->getCurrentValue($map['Location']['FieldName']))?>"
		placeholder="<?=htmlspecialcharsbx($map['Location']['Name'])?>"
		data-role="inline-selector-target"
	>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($map['StartTime']['Name'])?>: </span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($map['StartTime']['FieldName'])?>_interval_d">
		<option value="0" <?=($day == 0) ? 'selected':''?>><?=GetMessage('CRM_CREATE_MEETING_RPD_DAY_0')?></option>
		<option value="1" <?=($day == 1) ? 'selected':''?>><?=GetMessage('CRM_CREATE_MEETING_RPD_DAY_1')?></option>
		<option value="2" <?=($day == 2) ? 'selected':''?>><?=GetMessage('CRM_CREATE_MEETING_RPD_DAY_2')?></option>
		<option value="3" <?=($day == 3) ? 'selected':''?>><?=GetMessage('CRM_CREATE_MEETING_RPD_DAY_3')?></option>
	</select>
	<input class="crm-automation-popup-settings-select" readonly="readonly" value="<?=$time?>" data-role="time-selector" name="<?=htmlspecialcharsbx($map['StartTime']['FieldName'])?>_interval_t">
</div>
<div class="crm-automation-popup-settings">
<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
	<?=htmlspecialcharsbx($map['Responsible']['Name'])?>:
</span>
	<?=$dialog->renderFieldControl($map['Responsible'])?>
</div>
<div class="crm-automation-popup-checkbox">
	<div class="crm-automation-popup-checkbox-item">
		<label class="crm-automation-popup-chk-label">
			<input type="checkbox" name="<?=htmlspecialcharsbx($map['IsImportant']['FieldName'])?>" value="Y" class="crm-automation-popup-chk" <?=$dialog->getCurrentValue($map['IsImportant']['FieldName']) === 'Y' ? 'checked' : ''?>>
			<?=htmlspecialcharsbx($map['IsImportant']['Name'])?>
		</label>
	</div>
	<div class="crm-automation-popup-checkbox-item">
		<label class="crm-automation-popup-chk-label">
			<input type="checkbox"
				name="<?=htmlspecialcharsbx($map['AutoComplete']['FieldName'])?>"
				value="Y"
				class="crm-automation-popup-chk"<?=$dialog->getCurrentValue($map['AutoComplete']['FieldName']) === 'Y' ? 'checked' : ''?>
				data-role="save-state-checkbox"
				data-save-state-key="activity_auto_complete"
			>
			<?=htmlspecialcharsbx($map['AutoComplete']['Name'])?>
		</label>
	</div>
</div>