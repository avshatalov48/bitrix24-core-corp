<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$canUseAbsence = $dialog->getRuntimeData()['CanUseAbsence'];
$canUseTimeMan = $dialog->getRuntimeData()['CanUseTimeMan'];

foreach ($dialog->getMap() as $key => $property):
	$locked = !$canUseAbsence && $key === 'SkipAbsent' || !$canUseTimeMan && $key === 'SkipTimeMan';
?>
	<div class="bizproc-automation-popup-settings <?= $locked ? 'bizproc-automation-robot-btn-set-locked' : '' ?>">
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

		<?php
		if (!$canUseTimeMan):?>
			var selectTm = document.getElementById('id_skip_timeman');
			selectTm.setAttribute('disabled', 'disabled');
			selectTm.parentElement.addEventListener(
				'click',
				() =>
				{
					if (top.BX.UI && top.BX.UI.InfoHelper)
					{
						top.BX.UI.InfoHelper.show('limit_office_worktime_responsible');
					}
				}
			);
		<?php endif ?>
	});
</script>
