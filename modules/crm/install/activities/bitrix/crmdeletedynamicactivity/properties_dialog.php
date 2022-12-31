<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();

$entityTypeIdField = $map['EntityTypeId'];
$entityIdField = $map['EntityId'];
?>

<tr>
	<td align="right" width="40%"><?= htmlspecialcharsbx($entityTypeIdField['Name']) . ':'?></td>
	<td width="60%">
		<?= $dialog->renderFieldControl(
			$entityTypeIdField,
			$dialog->getCurrentValue($entityTypeIdField),
			true,
			1
		) ?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= htmlspecialcharsbx($entityIdField['Name']) . ':'?></td>
	<td width="60%">
		<?= $dialog->renderFieldControl(
			$entityIdField,
			$dialog->getCurrentValue($entityIdField),
			true,
			1
		) ?>
	</td>
</tr>
<tr hidden>
	<td width="60%">
		<?= $dialog->renderFieldControl(
			$dialog->getMap()['OnlyDynamicEntities'],
			$dialog->getCurrentValue('only_dynamic_entities'),
			false,
			1
		) ?>
	</td>
</tr>