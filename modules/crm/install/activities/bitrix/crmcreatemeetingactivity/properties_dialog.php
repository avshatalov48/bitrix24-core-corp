<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$documentType = $dialog->getDocumentType();
$useAutoComplete = $documentType[1] === 'CCrmDocumentLead' || $documentType[1] === 'CCrmDocumentDeal';
$map = $dialog->getMap();
if (!$useAutoComplete)
{
	unset($map['AutoComplete']);
}

foreach ($map as $fieldId => $field):
?>
<tr>
	<td align="right" width="40%">
		<?if (!empty($field['Required'])):?><span class="adm-required-field"><?endif;?>
		<?=htmlspecialcharsbx($field['Name'])?>:
		<?if (!empty($field['Required'])):?></span><?endif;?>
	</td>
	<td width="60%">
		<? $filedType = $dialog->getFieldTypeObject($field);

		echo $filedType->renderControl(array(
			'Form' => $dialog->getFormName(),
			'Field' => $field['FieldName']
		), $dialog->getCurrentValue($field['FieldName']), true, 0);
		?>
	</td>
</tr>
<?endforeach;?>