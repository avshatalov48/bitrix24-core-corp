<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$status = $map['TargetStatus'];
?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($status['Name']) ?>: </span>
	<?= $dialog->renderFieldControl($status) ?>
</div>
<?if (isset($map['ModifiedBy'])):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($map['ModifiedBy']['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($map['ModifiedBy']) ?>
	</div>
<?endif;?>