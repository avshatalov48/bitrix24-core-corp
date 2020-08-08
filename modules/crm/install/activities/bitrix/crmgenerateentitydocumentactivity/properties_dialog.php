<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmgenerateentitydocumentactivity/script.js'));
$map = $dialog->getMap();
$requisitesMap = $map['MyCompanyId']['FullMap'];
foreach ($map as $fieldId => $field)
{
	if($fieldId == 'Values')
	{
		continue;
	}
	?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?
			$filedType = $dialog->getFieldTypeObject($field);
			if(!$filedType)
			{
				continue;
			}

			echo $filedType->renderControl(array(
				'Form' => $dialog->getFormName(),
				'Field' => $field['FieldName']
			), $dialog->getCurrentValue($field['FieldName']), true, 0);
			?>
		</td>
	</tr>
<?}?>
<tr>
	<td colspan="2" align="center" width="100%" style="text-align: center;"><h3><?=GetMessage('BPGEDA_PROP_DIALOG_TEMPLATE_FIELDS');?></h3></td>
</tr>
<tr id="add_new_field_tr" class="bp-geda-fields-tr">
	<td align="right" width="40%"><?=GetMessage('BPGEDA_PROP_DIALOG_ADD_FIELD');?>:</td>
	<td>
		<select id="add_new_field_select">
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
		<input id="add_new_field_text" autocomplete="off" />
		<button id="add_new_field_button"><?=GetMessage('BPGEDA_PROP_DIALOG_ADD');?></button>
	</td>
</tr>
<?
$providerClassName = \CBPCrmGenerateEntityDocumentActivity::getDataProviderByEntityTypeId(\CCrmOwnerType::ResolveID($dialog->getDocumentType()[2]));
$values = $dialog->getCurrentValue('values');
if(is_array($values))
{
	foreach($values as $name => $value)
	{?>
		<tr class="bp-geda-fields-tr">
			<?=\CBPCrmGenerateEntityDocumentActivity::renderValuePropertyDialog(false, $providerClassName, $name, $map['Values']['TemplateFields'][$name], $value);?>
		</tr>
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
			deleteRowClassName: 'bp-geda-delete-row',
			openFieldInfoUrlClassName: 'bp-geda-fields-link',
			addNewFieldButtonNodeId: 'add_new_field_button',
			fieldTableRowClassName: 'bp-geda-fields-tr',
		});
	});
</script>
