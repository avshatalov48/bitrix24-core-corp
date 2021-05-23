<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$requisitesMap = $map['MyCompanyId']['FullMap'];
$template = $map['TemplateId'];
$selectFieldNames = ['MyCompanyId', 'MyCompanyRequisiteId', 'MyCompanyBankDetailId'];
$selected = $dialog->getCurrentValue($template['FieldName']);

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmgenerateentitydocumentactivity/script.js'));

\Bitrix\Main\Localization\Loc::loadMessages(__DIR__.'/properties_dialog.php');

?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($template['Name'])?>: </span>
	<?if(empty($template['Options']))
	{
		?><b style="color: #ff5752;"><?=GetMessage('CRM_GEDA_NO_TEMPLATES');?></b><?
	}
	else
	{?>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($template['FieldName'])?>" id="id_template_id">
		<?foreach ($template['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
	<?}
	?>
</div>
<div class="bizproc-automation-popup-settings">
	<label class="bizproc-automation-popup-chk-label">
		<input type="checkbox" name="<?=$map['UseSubscription']['FieldName'];?>" value="Y" class="bizproc-automation-popup-chk"<?echo ($dialog->getCurrentValue($map['UseSubscription']['FieldName']) != 'Y' ? '' : ' checked');?>>
		<?=htmlspecialcharsbx($map['UseSubscription']['Name'])?>
	</label>
</div>
<div class="bizproc-automation-popup-settings">
    <label class="bizproc-automation-popup-chk-label">
        <input type="checkbox" name="<?=$map['WithStamps']['FieldName'];?>" value="Y" class="bizproc-automation-popup-chk"<?echo ($dialog->getCurrentValue($map['WithStamps']['FieldName']) != 'Y' ? '' : ' checked');?>>
		<?=htmlspecialcharsbx($map['WithStamps']['Name'])?>
    </label>
</div>
<div class="bizproc-automation-popup-settings">
    <label class="bizproc-automation-popup-chk-label">
        <input type="checkbox" name="<?=$map['EnablePublicUrl']['FieldName'];?>" value="Y" class="bizproc-automation-popup-chk"<?echo ($dialog->getCurrentValue($map['EnablePublicUrl']['FieldName']) != 'Y' ? '' : ' checked');?>>
		<?=htmlspecialcharsbx($map['EnablePublicUrl']['Name'])?>
    </label>
</div>
<?php
foreach($selectFieldNames as $fieldName): ?>
    <div class="bizproc-automation-popup-settings">
        <span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($map[$fieldName]['Name'])?>: </span>
        <select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($map[$fieldName]['FieldName'])?>" id="id_<?=htmlspecialcharsbx($map[$fieldName]['FieldName'])?>">
            <option value=""></option>
            <?php
			$map[$fieldName]['Options'] = $map[$fieldName]['Options'] ?? [];
            foreach ($map[$fieldName]['Options'] as $value => $optionLabel):?>
                <option value="<?=htmlspecialcharsbx($value)?>"
                        <?=($value == $dialog->getCurrentValue($map[$fieldName]['FieldName'])) ? ' selected' : ''?>
                ><?=htmlspecialcharsbx($optionLabel)?></option>
            <?endforeach;?>
        </select>
    </div>
    <?php
endforeach;
?>
<div class="bizproc-automation-popup-settings" id="add_new_field_tr">
	<h3><?=GetMessage('BPGEDA_PROP_DIALOG_TEMPLATE_FIELDS');?></h3>
	<span class="bizproc-automation-popup-settings-title"><?=GetMessage('BPGEDA_PROP_DIALOG_ADD_FIELD');?>: </span>
	<select class="bizproc-automation-popup-settings-dropdown" id="add_new_field_select" style="max-width: 400px;">
		<option value=""></option>
		<?
		if(isset($map['Values']) && isset($map['Values']['TemplateFields']) && is_array($map['Values']['TemplateFields']))
		{
			foreach($map['Values']['TemplateFields'] as $placeholder => $field)
			{
				?>
				<option value="<?=$placeholder;?>"><?echo $placeholder;
					if($field['title'])
					{
						echo ' ('.$field['title'].')';
					}?></option>
				<?
			}
		}
		?>
	</select>
	<input style="display: none;" class="bizproc-automation-popup-input" id="add_new_field_text" autocomplete="off" />
	<a class="bizproc-automation-popup-settings-link" id="add_new_field_button"><?=GetMessage('BPGEDA_PROP_DIALOG_ADD');?></a>
</div>
	<?
	$providerClassName = \CBPCrmGenerateEntityDocumentActivity::getDataProviderByEntityTypeId(\CCrmOwnerType::ResolveID($dialog->getDocumentType()[2]));
	$values = $dialog->getCurrentValue('values');
	if(is_array($values))
	{
		foreach($values as $name => $value)
		{?>
			<div class="bizproc-automation-popup-settings bp-geda-fields-tr">
				<?=\CBPCrmGenerateEntityDocumentActivity::renderValuePropertyDialog(true, $providerClassName, $name, $map['Values']['TemplateFields'][$name], $value);?>
			</div>
		<?}
	}?>
	<script>
		BX.ready(function()
		{
			BX.Crm.Activity.CrmGenerateEntityDocumentActivity.init({
				documentType: <?=Cutil::PhpToJSObject($dialog->getDocumentType())?>,
				entityType: '<?=$dialog->getDocumentType()[2];?>',
				entityTypeId: '<?=intval(\CCrmOwnerType::ResolveID($dialog->getDocumentType()[2]));?>',
                requisitesMap: <?=CUtil::PhpToJSObject($requisitesMap);?>,
                selectMyCompanyNodeId: 'id_my_company_id',
                selectMyCompanyRequisiteNodeId: 'id_my_company_requisite_id',
                selectMyCompanyBankDetailNodeId: 'id_my_company_bank_detail_id',
				selectTemplateNodeId: 'id_template_id',
				selectFieldNodeId: 'add_new_field_select',
				textFieldNodeId: 'add_new_field_text',
				deleteRowClassName: 'bizproc-automation-popup-settings-delete',
				openFieldInfoUrlClassName: 'bp-geda-fields-link',
				addNewFieldButtonNodeId: 'add_new_field_button',
				fieldTableRowClassName: 'bizproc-automation-popup-settings',
				fieldTableRowTagName: 'div',
				isRobot: true,
			});
		});
	</script>