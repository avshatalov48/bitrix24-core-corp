<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Component\WorktimeGrid\TemplateParams;

/** @var TemplateParams $templateParams */
if ($templateParams === null)
{
	$templateParams = $arResult['templateParams'];
}

if (!$templateParams->record)
{
	if ($arResult['canUpdateShiftplan'] && $templateParams->showAddShiftPlanBtn && $templateParams->shift)
	{
		?>
		<div>
			<div class="timeman-grid-worktime timeman-grid-worktime-add"
					data-role="add-shiftplan-btn"
				<?php echo $templateParams->hintDataset; ?>>
				<div class="timeman-grid-add-btn">
					<input type="hidden" data-role="shiftId" name="ShiftPlanForm[shiftId]" value="<?php echo $templateParams->shift->getId(); ?>">
					<input type="hidden" data-role="userId" name="ShiftPlanForm[userId]" value="<?php echo (int)$templateParams->userId; ?>">
					<input type="hidden" data-role="dateAssigned" name="ShiftPlanForm[dateAssignedFormatted]" value="<?php
					echo htmlspecialcharsbx($templateParams->buildUtcShiftStartFormatted());
					?>">
				</div>
			</div>
		</div>
		<?
		return;
	}

	if (!empty($templateParams->absence))
	{
		?>
		<div class="timeman-grid-worktime timeman-grid-worktime-absence-block timeman-grid-cell-absence-<?= htmlspecialcharsbx($templateParams->absence['ABSENCE_PART']) ?>"
				data-role="absence"
				data-title="<?php echo $templateParams->getAbsenceDrawTitle(); ?>">
			<div class="timeman-grid-worktime-inner"
				<? if ($templateParams->absence['ABSENCE_HINT']): ?>
					data-hint-no-icon data-hint="<?php echo htmlspecialcharsbx($templateParams->absence['ABSENCE_HINT']) ?>"
				<? endif; ?>
			>
				<span class="timeman-grid-worktime-absence-desc"><?=
					htmlspecialcharsbx(isset($templateParams->absence['ABSENCE_TITLE']) ? $templateParams->absence['ABSENCE_TITLE'] : '')
					?></span>
			</div>
		</div>
		<?
		return;
	}
	if ($templateParams->shiftPlan)
	{
		require __DIR__ . '/_time-cell.php';
	}
	return;
}


if ($templateParams->isRecordExpired())
{
	if ($templateParams->absence)
	{
		?><div class="timeman-grid-worktime timeman-grid-worktime-absence-block timeman-grid-cell-absence-<?= htmlspecialcharsbx($templateParams->absence['ABSENCE_PART']) ?>"
		data-role="absence"
		data-title="<?php echo $templateParams->getAbsenceDrawTitle(); ?>"><?
	}
	?>
	<a class="timeman-grid-worktime-link"
			href="<?php echo $templateParams->recordLink; ?>"
			data-id="<?php echo $templateParams->record->getId(); ?>"
			data-role="worktime-record-cell">
		<div class="<? if (!$templateParams->absence): ?>timeman-grid-worktime<? endif; ?> timeman-grid-worktime-expired-text">
			<div class="timeman-grid-worktime-inner">
				<?= Loc::getMessage('TM_WORKTIME_GRID_RECORD_EXPIRED_TITLE'); ?>
			</div>
		</div>
	</a>
	<?
	if ($templateParams->absence)
	{
		?></div><?
	}
	return;
}


require __DIR__ . '/_time-cell.php';