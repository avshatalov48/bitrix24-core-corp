<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):
	?>
	<tr>
		<td align="right" width="40%">
			<?=htmlspecialcharsbx($field['Name'])?>:
			<? if (!empty($field['Description'])):?>
			<br><?=htmlspecialcharsbx($field['Description'])?>
			<?endif;?>
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