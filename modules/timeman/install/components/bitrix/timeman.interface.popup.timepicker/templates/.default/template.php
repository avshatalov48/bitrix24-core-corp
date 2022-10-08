<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/tools/clock.php");

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui', 'date']);
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/timeman.interface.popup.timepicker/templates/.default/timepicker.css');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.interface.popup.timepicker/templates/.default/timepicker.js');

?>
<div class="bx-tm-popup-edit-clock-wnd main-ui-hide"
		data-role="<?= htmlspecialcharsbx($arResult['TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE']); ?>">
	<? if ($arResult['SHOW_START_END_BLOCKS']): ?>
		<div class="bx-tm-popup-edit-clock-wnd-clock">
			<span class="bx-tm-clock-caption"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_POPUP_WORK_TIME_START_TITLE')); ?></span><?
			?><span class="bx-tm-clock-caption"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_POPUP_WORK_TIME_END_TITLE')); ?></span>
		</div>
		<table class="tm-double-clock-table">
			<tbody>
			<tr>
				<td class="tm-double-clock-table-row tm-double-clock-table-first">
					<? CClock::Show([
						'view' => 'inline',
						'showIcon' => false,
						'step' => $arResult['START_CLOCK_STEP'],
						'inputName' => $arResult['START_INPUT_NAME'],
						'inputId' => $arResult['START_INPUT_ID'],
						'initTime' => $arResult['START_INIT_TIME'],
					]); ?>
					<? if ($arResult['SHOW_START_DATE_PICKER']): ?>
						<div class="bx-tm-popup-clock-wnd-custom-date-block">
							<span class="bx-tm-popup-clock-wnd-custom-date-link bx-tm-popup-clock-wnd-custom-date-link-edit"
									data-role="date-picker"
									data-type="start"><?php echo Loc::getMessage('TIMEMAN_EDIT_CLOCK_SET_CUSTOM_DATE'); ?>
							</span>
						</div>
					<? endif; ?>
				</td>
				<td class="tm-double-clock-table-row tm-double-clock-table-second">
					<? CClock::Show([
						'view' => 'inline',
						'showIcon' => false,
						'step' => $arResult['END_CLOCK_STEP'],
						'inputName' => $arResult['END_INPUT_NAME'],
						'inputId' => $arResult['END_INPUT_ID'],
						'initTime' => $arResult['END_INIT_TIME'],
					]); ?>
					<? if ($arResult['SHOW_END_DATE_PICKER']): ?>
						<div class="bx-tm-popup-clock-wnd-custom-date-block">
							<span class="bx-tm-popup-clock-wnd-custom-date-link bx-tm-popup-clock-wnd-custom-date-link-edit"
									data-role="date-picker"
									data-type="end"><?php echo Loc::getMessage('TIMEMAN_EDIT_CLOCK_SET_CUSTOM_DATE'); ?>
							</span>
						</div>
					<? endif; ?>
				</td>
			</tr>
			</tbody>
		</table>
		<div class="bx-tm-field">
			<span class="bx-tm-report-caption"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_DURATION_TITLE')); ?>:</span>
			<span class="bx-tm-report-field"
					data-role="timeman-work-time-start-end-delta"></span>
		</div>
	<? endif; ?>
	<? if ($arResult['SHOW_EDIT_BREAK_LENGTH']): ?>
		<div class="bx-tm-popup-clock-wnd-report">
			<div class="bx-tm-popup-clock-wnd-subtitle"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_EDIT_BREAK_LENGTH_TITLE')); ?></div>
			<div class="bx-tm-edit-section">
				<input value="<?= htmlspecialcharsbx($arResult['BREAK_LENGTH_VALUE']); ?>" type="text"
						name="<?= htmlspecialcharsbx($arResult['EDIT_BREAK_LENGTH_ATTRIBUTE_NAME']); ?>"
						class="bx-tm-report-edit" style="width: 40px;"
						data-role="tm-time-picker-break-length">
			</div>
		</div>
	<? endif; ?>
	<? if ($arResult['BREAK_LENGTH_INPUT_ID'] !== null): ?>
		<div class="bx-tm-popup-edit-clock-wnd main-ui-hide timeman-pick-time-hide-clock"
				data-role="<?= htmlspecialcharsbx($arResult['BREAK_LENGTH_ATTRIBUTE_DATA_ROLE']); ?>">
			<?
			CClock::Show([
				'view' => 'inline',
				'showIcon' => false,
				'inputName' => $arResult['BREAK_LENGTH_INPUT_NAME'],
				'inputId' => $arResult['BREAK_LENGTH_INPUT_ID'],
				'initTime' => $arResult['BREAK_LENGTH_INIT_TIME'],
			]); ?>
		</div>
	<? endif; ?>
	<? if ($arResult['SHOW_EDIT_REASON']): ?>
		<div class="bx-tm-popup-clock-wnd-report">
			<div class="bx-tm-popup-clock-wnd-subtitle"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_EDIT_REASON_TITLE')); ?></div>
			<textarea name="<?= htmlspecialcharsbx($arResult['EDIT_REASON_ATTRIBUTE_NAME']); ?>" class="tm-timepicker-popup-reason-block"></textarea>
		</div>
	<? endif; ?>

</div>
<script>
	BX.ready(function ()
	{
		BX.message({
			AMPM_MODE: <?= CUtil::PhpToJSObject((bool)\IsAmPmMode(true)); ?>
		});
		new BX.Timeman.Component.Popup.TimePicker({
			containerSelector: '[data-role="<?= CUtil::JSEscape($arResult['TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE']); ?>"]',
			startDateDefault: '<?= CUtil::JSEscape($arResult['START_DATE_DEFAULT_VALUE'])?>',
			endDateDefault: '<?= CUtil::JSEscape($arResult['END_DATE_DEFAULT_VALUE'])?>',
			startDateInputSelector: '<?= CUtil::JSEscape($arResult['START_DATE_INPUT_SELECTOR_ROLE'])?>',
			endDateInputSelector: '<?= CUtil::JSEscape($arResult['END_DATE_INPUT_SELECTOR_ROLE'])?>',
			inputStartId: '<?= CUtil::JSEscape($arResult['START_INPUT_ID'])?>',
			inputEndId: '<?= CUtil::JSEscape($arResult['END_INPUT_ID'])?>'
		});
	});
</script>
