<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Timeman\Component\WorktimeGrid\TemplateParams $templateParams */

$extraCssClasses = '';
$drawDeleteBtn = false;
if ($templateParams->isShiftedSchedule())
{
	$utcShiftEndTime = -1;
	if ($templateParams->shiftPlan)
	{
		$utcShiftEndTime = $templateParams->shift->buildUtcEndByShiftplan($templateParams->shiftPlan)->getTimestamp();
	}
	// css classes
	$extraCssClasses = 'timeman-grid-worktime-shift ';
	if ($arResult['canUpdateShiftplan'])
	{
		$extraCssClasses .= ' timeman-grid-worktime-inner-clickable ';
	}
	$isFutureShiftPlan = $arResult['nowTime'] < $utcShiftEndTime && empty($templateParams->record);
	$theme = 'day';
	if ($isFutureShiftPlan)
	{
		$extraCssClasses .= 'timeman-grid-worktime-shift-future timeman-grid-worktime-shift-future-' . $theme;
	}
	else
	{
		if ($templateParams->record && !$templateParams->shiftPlan)
		{
			$theme = 'no-shiftplan';
		}
		if (!$templateParams->record && $templateParams->shiftPlan && $arResult['nowTime'] > $utcShiftEndTime)
		{
			$theme = 'missed-shift';
		}
		$extraCssClasses .= 'timeman-grid-worktime-shift-past timeman-grid-worktime-shift-past-' . $theme;
	}
	// css classes

	$drawDeleteBtn = $arResult['SHOW_DELETE_SHIFT_PLAN_BTN'];
}


if ($templateParams->absence)
{
	?><div class="timeman-grid-worktime timeman-grid-worktime-absence-block timeman-grid-cell-absence-<?=
htmlspecialcharsbx($templateParams->absence['ABSENCE_PART']) ?>"
	data-role="absence"
	data-title="<?php echo $templateParams->getAbsenceDrawTitle(); ?>"><?
}
?>
<? if ($templateParams->recordLink): ?>
	<div class="timeman-grid-worktime-record-cell" data-href="<?php echo $templateParams->recordLink; ?>"
			data-id="<?php echo $templateParams->record->getId(); ?>"
			data-role="worktime-record-cell">
<? endif; ?>
	<div class="" <?= !empty($templateParams->hintDataset) ? $templateParams->hintDataset : ''; ?>>
		<div class="<? if (!$templateParams->absence): ?>timeman-grid-worktime <? endif; ?> <?php echo $extraCssClasses; ?>">
			<div class="timeman-grid-worktime-inner">
				<div class="timeman-grid-worktime-container">
					<? if ($templateParams->isShiftedSchedule() && $templateParams->shift && $templateParams->shift->isActive()
						   && $arResult['IS_SHIFTPLAN']
						   && $arResult['canUpdateShiftplan']): ?>
						<div class="timeman-grid-shift-plan-menu"
								data-role="shiftplan-menu-toggle"
							<?
							if ($drawDeleteBtn && $templateParams->shiftPlan): ?>
								data-item-delete="1"
							<?
							endif; ?>
							<?
							if (!$templateParams->shiftPlan): ?>
								data-item-add="1"
							<?
							endif; ?>
						>
							<? if ($templateParams->record): ?>
								<input type="hidden"
										data-role="recordId"
										name="recordId"
										value="<?php echo $templateParams->record->getId(); ?>">
							<? endif; ?>
							<? if ($templateParams->shiftPlan): ?>
								<input type="hidden"
										name="ShiftPlanForm[id]"
										value="<?php echo $templateParams->shiftPlan->getId(); ?>">
							<? endif; ?>
							<input type="hidden"
									data-role="shiftId"
									name="ShiftPlanForm[shiftId]"
									value="<?php echo $templateParams->shift->getId(); ?>">
							<input type="hidden"
									data-role="userId"
									name="ShiftPlanForm[userId]"
									value="<?php echo $templateParams->userId; ?>">
							<input type="hidden"
									data-role="dateAssigned"
									name="ShiftPlanForm[dateAssignedFormatted]"
									value="<?php echo $templateParams->buildUtcShiftStartFormatted(); ?>">
						</div>
					<? endif; ?>
					<? if ($templateParams->isShiftedSchedule() && $templateParams->shift): ?>
						<span class="timeman-grid-worktime-name"
								data-role="name"><?php
							echo htmlspecialcharsbx($templateParams->shift->getName()); ?></span>
					<? endif; ?>
					<? if ($templateParams->formattedDuration): ?>
						<div class="timeman-grid-worktime-duration">
							<span class="timeman-grid-worktime-duration-value">
								<?php echo $templateParams->formattedDuration; ?>
							</span>
						</div>
					<? endif; ?>
					<? if (!$arResult['IS_SHIFTPLAN'] || (
							$arResult['IS_SHIFTPLAN'] && $templateParams->shiftPlan && !$templateParams->record
						)): ?>
						<div class="timeman-grid-worktime-interval <?= $arResult['GRID_OPTIONS']['SHOW_START_END'] ? '' : 'timeman-hide' ?>"
								data-role="start-end">
							<span data-role="start"><?php echo $templateParams->formattedStart; ?></span>
							<span>-</span>
							<span data-role="end"><?php echo $templateParams->formattedEnd; ?></span>
						</div>
					<? endif; ?>
				</div>
				<? if ($templateParams->record): ?>
					<span class="timeman-grid-worktime-icon-container">
						<?php $extraIndividualClass = $arResult['GRID_OPTIONS']['SHOW_VIOLATIONS_INDIVIDUAL'] ? ' ' : ' timeman-hide '; ?>
						<?php $extraCommonClass = $arResult['GRID_OPTIONS']['SHOW_VIOLATIONS_INDIVIDUAL'] ? ' timeman-hide ' : ' '; ?>
						<? if ($templateParams->getViolationIndividualHint()): ?>
							<span class="timeman-record-violation-icon <?php echo
								$templateParams->getViolationIndividualCss() . $extraIndividualClass; ?>"
									data-hint-no-icon
									data-type="individual"
									data-role="violation-icon" data-hint-html
									data-hint="<?php echo htmlspecialcharsbx($templateParams->getViolationIndividualHint()); ?>">
							</span>
						<? endif; ?>
						<? if ($templateParams->getViolationCommonHint()): ?>
							<span class="timeman-record-violation-icon <?php echo
								$templateParams->getViolationCommonCss() . $extraCommonClass; ?>"
									data-hint-no-icon
									data-type="common"
									data-role="violation-icon"
									data-hint-html
									data-hint="<?php echo htmlspecialcharsbx($templateParams->getViolationCommonHint()); ?>">
							</span>
						<? endif; ?>
					</span>
				<? endif; ?>
			</div>
		</div>
	</div>

<? if ($templateParams->recordLink): ?>
	</div>
<? endif; ?>
<? if ($templateParams->absence): ?>
	</div>
<? endif; ?>