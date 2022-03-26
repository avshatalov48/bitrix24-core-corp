<?php

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var PropertiesDialog $dialog */
$isAdmin = $dialog->getRuntimeData()['isAdmin'];

if ($isAdmin):
	foreach ($dialog->getMap() as $fieldId => $field): ?>
		<tr>
			<td align="right" width="40%">
				<?php if ($field['Required']): ?>
					<span class="adm-required-field">
				<?php endif ?>
				<?= htmlspecialcharsbx($field['Name']) ?>:
				<?php if ($field['Required']):?>
					</span>
				<?php endif ?>
			</td>
			<td width="60%">
				<?php if ($field['Type'] === 'select'):
					$allowSelection = false;
				else:
					$allowSelection = true;
				endif;
				$fieldType = $dialog->getFieldTypeObject($field) ?>

				<?= $fieldType->renderControl(
					[
						'Form' => $dialog->getFormName(),
						'Field' => $field['FieldName'],
					],
					$dialog->getCurrentValue($field['FieldName']),
					$allowSelection,
					FieldType::RENDER_MODE_DESIGNER
				) ?>
			</td>
		</tr>
	<?php endforeach ?>
<?php else: ?>
	<tr>
		<td
			align="right"
			width="40%"
			valign="top"
			colspan="2"
			style="color: red"
		><?= GetMessage('TASKS_GLDA_ACCESS_DENIED_1') ?></td>
	</tr>
<?php endif ?>
