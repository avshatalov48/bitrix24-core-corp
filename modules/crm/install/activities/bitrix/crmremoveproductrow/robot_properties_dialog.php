<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $propertyKey => $property):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($property['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($property, null, !empty($property['AllowSelection'])) ?>
	</div>
<?php endforeach; ?>
<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
	<?= GetMessage('CRM_RMPR_DESCRIPTION') ?>
</div>
