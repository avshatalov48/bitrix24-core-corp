<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$entityTypeIdField = $dialog->getMap()['EntityTypeId'];
$entityIdField = $dialog->getMap()['EntityId'];

?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?= htmlspecialcharsbx($entityTypeIdField['Name']) ?>
	</span>
	<?= $dialog->renderFieldControl($entityTypeIdField, $dialog->getCurrentValue($entityTypeIdField)) ?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?= htmlspecialcharsbx($entityIdField['Name']) ?>
	</span>
	<?= $dialog->renderFieldControl($entityIdField, $dialog->getCurrentValue($entityIdField)) ?>
</div>
<div hidden>
	<?= $dialog->renderFieldControl($dialog->getMap()['OnlyDynamicEntities']) ?>
</div>
