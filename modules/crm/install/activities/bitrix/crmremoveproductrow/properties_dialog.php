<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field): ?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?= $dialog->renderFieldControl($field, null, !empty($field['AllowSelection']), 0) ?>
		</td>
	</tr>
<?php endforeach; ?>
<tr>
	<td colspan="2">
		<?= GetMessage('CRM_RMPR_DESCRIPTION') ?>
	</td>
</tr>
