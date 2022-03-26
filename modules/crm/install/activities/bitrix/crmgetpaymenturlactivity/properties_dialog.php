<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):?>
	<tr id="field_<?=htmlspecialcharsbx($field['FieldName'])?>_container">
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?php
			$fieldType = $dialog->getFieldTypeObject($field);

			echo $fieldType->renderControl(array(
				'Form' => $dialog->getFormName(),
				'Field' => $field['FieldName']
			), $dialog->getCurrentValue($field['FieldName']), true, 0);
			?>
		</td>
	</tr>
<?php endforeach;?>

<script>
	BX.ready(function() {
		var form = document.forms['<?= CUtil::JSEscape($dialog->getFormName()) ?>'];
		var orderIdContainer = document.getElementById('field_order_id_container');

		orderIdContainer.hidden = form.autocreate.value === 'Y';
		form.autocreate.onchange = function(event)
		{
			orderIdContainer.hidden = event.srcElement.value === 'Y';
		};
	});
</script>
