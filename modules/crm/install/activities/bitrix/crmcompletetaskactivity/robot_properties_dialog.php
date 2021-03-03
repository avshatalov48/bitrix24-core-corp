<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die;
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>

<?php foreach ($dialog->getMap() as $field): ?>
	<div class="crm-automation-popup-settings">
		<span class="crm-automation-popup-settings-title"><?= htmlspecialcharsbx($field['Name']) ?>: </span>
		<?= $dialog->renderFieldControl($field, $dialog->getCurrentValue($field)) ?>
	</div>
<?php endforeach; ?>