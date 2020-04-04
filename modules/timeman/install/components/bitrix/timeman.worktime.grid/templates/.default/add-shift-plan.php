<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>
<div class="timeman-grid-worktime timeman-grid-worktime-add" data-role="add-shiftplan-btn">
	<div class="timeman-grid-add-btn">
		<input type="hidden"
				data-role="shiftId"
				name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[shiftId]'); ?>"
				value="<?= htmlspecialcharsbx($recordShiftplanData['WORK_SHIFT']['ID']); ?>">
		<input type="hidden"
				data-role="userId"
				name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[userId]'); ?>"
				value="<?= htmlspecialcharsbx($recordShiftplanData['USER_ID']); ?>">
		<input type="hidden"
				data-role="dateAssigned"
				name="<?= htmlspecialcharsbx($arResult['SHIFT_PLAN_FORM_NAME'] . '[dateAssignedFormatted]'); ?>"
				value="<?= htmlspecialcharsbx($recordShiftplanData['DRAWING_DATE']->format('Y-m-d')); ?>">
	</div>
</div>