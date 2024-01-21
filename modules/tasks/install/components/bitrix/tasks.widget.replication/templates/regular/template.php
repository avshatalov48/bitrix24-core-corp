<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var array $arParams */

/** @var CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];

$prefix = htmlspecialcharsbx($arParams['INPUT_PREFIX']);
$data = $arParams['DATA'];
$tzOffset = intval($arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET']);
?>

<?php
$helper->displayFatals(); ?>
<?php
if (!$helper->checkHasFatals()): ?>

<div id="<?= $helper->getScopeId() ?>" class="tasks task-options-repeat">

	<?php
	$helper->displayWarnings(); ?>

	<div class="task-options-field">
		<span class="task-option-fn"><?= Loc::getMessage('TASKS_TTDP_REGULAR_REPEAT_TYPE') ?></span>
		<span class="js-id-regular-period-type-selector tasks-option-tab-container">
	            <span class="js-id-regular-period-type-option tasks-option-tab daily" data-type="daily"><?= Loc::getMessage('TASKS_TTDP_REGULAR_DAILY') ?></span>
	            <span class="js-id-regular-period-type-option tasks-option-tab weekly" data-type="weekly"><?= Loc::getMessage('TASKS_TTDP_REGULAR_WEEKLY') ?></span>
	            <span class="js-id-regular-period-type-option tasks-option-tab monthly" data-type="monthly"><?= Loc::getMessage('TASKS_TTDP_REGULAR_MONTHLY') ?></span>
	            <span class="js-id-regular-period-type-option tasks-option-tab yearly" data-type="yearly"><?= Loc::getMessage('TASKS_TTDP_REGULAR_YEARLY') ?></span>
	        </span>
		<input class="js-id-regular-period-type" type="hidden" name="<?= $prefix ?>[PERIOD]" value="<?= htmlspecialcharsbx($data['PERIOD']) ?>"/>
	</div>

	<div class="js-id-regular-panel task-regular-panel">

		<?php
		//daily?>
		<div class="js-id-regular-panel-daily task-replication-params<?= ($data['PERIOD'] != 'daily' ? ' nodisplay'
			: ' opacity-1') ?>">
			<div class="task-options-field">
				<input type="hidden" class="js-id-regular-every-day task-options-inp" name="<?= $prefix ?>[EVERY_DAY]" value="<?= intval($data['EVERY_DAY']) ?>">
				<span class="task-options-inp-container task-options-inp-container-period">
                    <select name="<?= $prefix ?>[WORKDAY_ONLY]" class="js-id-regular-day-type task-options-inp">
	                    <option value="Y"<?= ($data['WORKDAY_ONLY'] == 'Y' ? ' selected'
							: '') ?>><?= Loc::getMessage('TASKS_TTDP_REGULAR_DAILY_WORK') ?></option>
	                    <option value="N"<?= ($data['WORKDAY_ONLY'] != 'Y' ? ' selected'
							: '') ?>><?= Loc::getMessage('TASKS_TTDP_REGULAR_ANY') ?></option>
                    </select>
                </span>
				<span class="task-option-fn"><?= Loc::getMessage('TASKS_TTDP_REGULAR_DAY') ?></span>
				<input type="hidden" name="<?= $prefix ?>[DAILY_MONTH_INTERVAL]" value="<?= (int)($data['DAILY_MONTH_INTERVAL']
					??
					null) ?>" class="js-id-regular-daily-month-interval task-options-inp">
			</div>
		</div>

		<?php
		//weekly?>
		<div class="js-id-regular-panel-weekly task-replication-params<?= ($data['PERIOD'] != 'weekly' ? ' nodisplay'
			: ' opacity-1') ?>">
			<div class="task-options-field">
				<input type="hidden" name="<?= $prefix ?>[EVERY_WEEK]" class="js-id-regular-every-week task-options-inp" value="<?= intval($data['EVERY_WEEK']) ?>">
			</div>
			<div class="task-options-field">
				<div class="task-options-day-container">
					<?php
					for ($k = 0; $k <= 6; $k++): ?>
						<?php
						$i = $arResult['AUX_DATA']['WEEKDAY_MAP'][$k] + 1; ?>
						<label class="task-options-day"><input class="js-id-regular-week-days" type="checkbox" name="<?= $prefix ?>[WEEK_DAYS][]" value="<?= $i ?>" <?php
							if (in_array($i, $data['WEEK_DAYS'])): ?>checked="checked"<?php
							endif ?>/>&nbsp;<?= Loc::getMessage('TASKS_TTDP_REGULAR_WD_SH_' . ($i - 1)) ?></label>
					<?php
					endfor ?>
				</div>
			</div>
		</div>

		<?php
		//monthly?>
		<div class="js-id-regular-panel-monthly task-replication-params<?= ($data['PERIOD'] != 'monthly' ? ' nodisplay'
			: ' opacity-1') ?>">
			<input id="replication-monthly-type-1" name="<?= $prefix ?>[MONTHLY_TYPE]" value="1" <?php
			if ($data['MONTHLY_TYPE'] == 1): ?>checked<?php
			endif ?> class="js-id-regular-monthly-type task-options-radio" type="hidden">
			<label class="task-field-label" for="replication-monthly-type-1"><?= Loc::getMessage('TASKS_TTDP_REGULAR_EACH') ?></label>
			<span class="task-options-inp-container task-options-inp-int">
	                    <input name="<?= $prefix ?>[MONTHLY_DAY_NUM]" value="<?= intval($data['MONTHLY_DAY_NUM']) ?>" type="text" class="js-id-regular-monthly-day-num task-options-inp"/>
	                </span>
			<label class="task-field-label" for="replication-monthly-type-1"><?= Loc::getMessage('TASKS_TTDP_REGULAR_NUMBER_OF_EACH') ?></label>
			<input name="<?= $prefix ?>[MONTHLY_MONTH_NUM_1]" value="<?= intval($data['MONTHLY_MONTH_NUM_1']) ?>" type="hidden" class="js-id-regular-monthly-month-num-1 task-options-inp">
		</div>

		<?php
		//yearly?>
		<div class="js-id-regular-panel-yearly task-replication-params<?= ($data['PERIOD'] != 'yearly' ? ' nodisplay'
			: ' opacity-1') ?>">
			<input id="replication-yearly-type-1" name="<?= $prefix ?>[YEARLY_TYPE]" value="1" <?php
			if ($data['YEARLY_TYPE'] == 1): ?>checked<?php
			endif ?> class="js-id-regular-yearly-type task-options-radio" type="hidden">
			<label class="task-field-label" for="replication-yearly-type-1"><?= Loc::getMessage('TASKS_TTDP_REGULAR_EACH') ?></label>
			<span class="task-options-inp-container task-options-inp-int">
	                    <input name="<?= $prefix ?>[YEARLY_DAY_NUM]" value="<?= intval($data['YEARLY_DAY_NUM']) ?>" type="text" class="js-id-regular-yearly-day-num task-options-inp">
	                </span>
			<label class="task-field-label" for="replication-yearly-type-1"><?= Loc::getMessage('TASKS_TTDP_REGULAR_NUMBER_OF_EACH_MONTH') ?></label>
			<span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?= $prefix ?>[YEARLY_MONTH_1]" class="js-id-regular-yearly-month-1 task-options-inp">
		                    <?php
							for ($i = 0; $i <= 11; $i++): ?>
								<option value="<?= $i ?>" <?php
								if ($data['YEARLY_MONTH_1'] == $i): ?>selected<?php
								endif ?>><?= Loc::getMessage('TASKS_TTDP_REGULAR_MONTH_' . $i) ?></option>
							<?php
							endfor ?>
	                    </select>
	                </span>
		</div>
	</div>

	<div class="task-options-field">
		<div class="task-options-field task-options-field-left">
			<label for="" class="task-field-label task-field-label-br"><?= Loc::getMessage('TASKS_TTDP_REGULAR_TASK_CTIME') ?>:</label>
			<span class="js-id-regular-timepicker task-options-inp-container task-options-inp-container-period task-options-inp-container-timer">
	                <input type="text" class="js-id-timepicker-display task-options-inp" readonly="readonly"/>
		            <input class="js-id-regular-time js-id-timepicker-value" name="<?= $prefix ?>[TIME]" type="hidden" value="<?= htmlspecialcharsbx($data['TIME']) ?>"/>
	                <div class="task-main-clock-monkeyfix">
		                <?php
						$APPLICATION->IncludeComponent('bitrix:main.clock', '', [
							'INIT_TIME' => $data['TIME'] ? $data['TIME'] : '00:00',
							'INPUT_ID' => $arResult['JS_DATA']['timerId'],
						]); ?>
	                </div>
	            </span>
			<span class="js-id-hint-help task-options-timezone-indicator"><?= Loc::getMessage('TASKS_TTDP_REGULAR_TZ_HINT',
					['#TIMEZONE#' => \Bitrix\Tasks\UI::formatTimezoneOffsetUTC($tzOffset)]) ?></span>
			<input type="hidden" name="<?= $prefix ?>[TIMEZONE_OFFSET]" value="<?= intval($arResult['CURRENT_TIMEZONE_OFFSET']) ?>"/>
		</div>
		<label class="task-field-label" for="replication-monthly-type-1"><?= Loc::getMessage('TASKS_TTDP_REGULAR_DEADLINE_OFFSET') ?></label>
		<span class="task-options-inp-container task-options-inp-int">
			<input name="<?= $prefix ?>[DEADLINE_OFFSET]" value="<?= (int)($data['DEADLINE_OFFSET'] ?? null) ?>" type="text" class="js-id-regular-deadline task-options-inp"/>
		</span>
		<div class="task-options-field task-options-field-left">
	            <span class="js-id-regular-start-date-datepicker" type="hidden">
	                <input type="hidden" class="js-id-datepicker-display task-options-inp" value="" readonly="readonly">
	                <input class="js-id-datepicker-value" type="hidden" name="<?= $prefix ?>[START_DATE]" value="<?= htmlspecialcharsbx($data['START_DATE']) ?>"/>
	            </span>
		</div>
	</div>
	<div class="task-options-field task-options-field-nol">
		<input name="<?= $prefix ?>[REPEAT_TILL]" value="endless" class="js-id-regular-repeat-till" id="replication-repeat-constraint-none" type="hidden"/>
		<span type="hidden" class="js-id-regular-end-date-datepicker">
				<input type="hidden" class="js-id-datepicker-display" value="" readonly="readonly">
				<input class="js-id-datepicker-value" type="hidden" name="<?= $prefix ?>[END_DATE]" value="<?= htmlspecialcharsbx($data['END_DATE']) ?>"/>
			</span>
	</div>
	<?php
	$helper->initializeExtension(); ?>

<?php
endif ?>