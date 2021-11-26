<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$canUseAbsence = $dialog->getRuntimeData()['CanUseAbsence'];

foreach ($dialog->getMap() as $key => $property): ?>
	<div class="bizproc-automation-popup-settings <?= ((!$canUseAbsence) && ($key === 'SkipAbsent')) ? 'bizproc-automation-robot-btn-set-locked' : '' ?>">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($property['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($property) ?>
	</div>
<?php
endforeach;
?>

<script>

	BX.ready(function ()
	{
		<?php
		if (!$canUseAbsence):?>
			var select = document.getElementById('id_skip_absent');
			select.setAttribute('disabled', 'disabled');
			var externalDiv = select.parentElement;
			externalDiv.addEventListener('click', callLimitInfoHelper)
		<?php endif ?>

		function callLimitInfoHelper()
		{
			if (top.BX.UI && top.BX.UI.InfoHelper)
			{
				top.BX.UI.InfoHelper.show('limit_crm_robot_change_responsible');
			}
		}
	});

</script>
