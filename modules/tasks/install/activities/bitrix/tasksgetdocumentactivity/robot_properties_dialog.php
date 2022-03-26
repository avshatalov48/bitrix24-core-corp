<?php

use Bitrix\Bizproc\Activity\PropertiesDialog;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var PropertiesDialog $dialog*/
$isAdmin = $dialog->getRuntimeData()['isAdmin'];

if ($isAdmin):
	foreach ($dialog->getMap() as $fieldId => $field): ?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title">
				<?=htmlspecialcharsbx($field['Name']) ?>:
			</span>
			<?= $dialog->renderFieldControl($field) ?>
		</div>
<?php endforeach ?>

<?php else: ?>
<div class="bizproc-automation-popup-settings-alert">
	<?=GetMessage('TASKS_GLDA_ACCESS_DENIED_1')?>
</div>
<?php endif ?>
