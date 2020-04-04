<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];

$prefix = htmlspecialcharsbx($arParams['INPUT_PREFIX']);
$data = $arParams['DATA'];
$tzOffset = intval($arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET']);
?>

<?$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks task-options-repeat">

		<?$helper->displayWarnings();?>

		<div class="task-options-field">
			<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_TYPE')?></span>
	        <span class="js-id-replication-period-type-selector tasks-option-tab-container">
	            <span class="js-id-replication-period-type-option tasks-option-tab daily" data-type="daily"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAILY')?></span>
	            <span class="js-id-replication-period-type-option tasks-option-tab weekly" data-type="weekly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WEEKLY')?></span>
	            <span class="js-id-replication-period-type-option tasks-option-tab monthly" data-type="monthly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTHLY')?></span>
	            <span class="js-id-replication-period-type-option tasks-option-tab yearly" data-type="yearly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_YEARLY')?></span>
	        </span>
			<input class="js-id-replication-period-type" type="hidden" name="<?=$prefix?>[PERIOD]" value="<?=htmlspecialcharsbx($data['PERIOD'])?>" />
		</div>

		<div class="js-id-replication-panel task-replication-panel">

			<?//daily?>
			<div class="js-id-replication-panel-daily task-replication-params<?=($data['PERIOD'] != 'daily' ? ' nodisplay' : ' opacity-1')?>">
				<div class="task-options-field">
					<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></span>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input type="text" class="js-id-replication-every-day task-options-inp" name="<?=$prefix?>[EVERY_DAY]" value="<?=intval($data['EVERY_DAY'])?>">
	                </span>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select name="<?=$prefix?>[WORKDAY_ONLY]" class="js-id-replication-day-type task-options-inp">
	                    <option value="Y"<?=($data['WORKDAY_ONLY'] == 'Y' ? ' selected' : '')?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAILY_WORK')?></option>
	                    <option value="N"<?=($data['WORKDAY_ONLY'] != 'Y' ? ' selected' : '')?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_ANY')?></option>
                    </select>
                </span>
					<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAY_INTERVAL')?></span>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input type="text" name="<?=$prefix?>[DAILY_MONTH_INTERVAL]" value="<?=intval($data['DAILY_MONTH_INTERVAL'])?>" class="js-id-replication-daily-month-interval task-options-inp">
	                </span>
					<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_SHORT')?></span>
				</div>
			</div>

			<?//weekly?>
			<div class="js-id-replication-panel-weekly task-replication-params<?=($data['PERIOD'] != 'weekly' ? ' nodisplay' : ' opacity-1')?>">
				<div class="task-options-field">
					<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_F')?></span>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input type="text" name="<?=$prefix?>[EVERY_WEEK]" class="js-id-replication-every-week task-options-inp" value="<?=intval($data['EVERY_WEEK'])?>">
	                </span>
					<span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WEEK_ALT')?></span>
				</div>
				<div class="task-options-field">
					<div class="task-options-day-container">
						<?for($k = 0; $k <= 6; $k++):?>
							<?$i = $arResult['AUX_DATA']['WEEKDAY_MAP'][$k] + 1;?>
							<label class="task-options-day"><input class="js-id-replication-week-days" type="checkbox" name="<?=$prefix?>[WEEK_DAYS][]" value="<?=$i?>" <?if(in_array($i, $data['WEEK_DAYS'])):?>checked="checked"<?endif?>/>&nbsp;<?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_SH_'.($i - 1))?></label>
						<?endfor?>
					</div>
				</div>
			</div>

			<?//monthly?>
			<div class="js-id-replication-panel-monthly task-replication-params<?=($data['PERIOD'] != 'monthly' ? ' nodisplay' : ' opacity-1')?>">
				<div class="task-options-field">
					<input id="replication-monthly-type-1" name="<?=$prefix?>[MONTHLY_TYPE]" value="1" <?if($data['MONTHLY_TYPE'] == 1):?>checked<?endif?> class="js-id-replication-monthly-type task-options-radio" type="radio">
					<label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH')?></label>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input name="<?=$prefix?>[MONTHLY_DAY_NUM]" value="<?=intval($data['MONTHLY_DAY_NUM'])?>" type="text" class="js-id-replication-monthly-day-num task-options-inp" />
	                </span>
					<label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_OF_EACH_M_ALT')?></label>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input name="<?=$prefix?>[MONTHLY_MONTH_NUM_1]" value="<?=intval($data['MONTHLY_MONTH_NUM_1'])?>" type="text" class="js-id-replication-monthly-month-num-1 task-options-inp">
	                </span>
					<label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
				</div>
				<div class="task-options-field">
					<input id="replication-monthly-type-2" name="<?=$prefix?>[MONTHLY_TYPE]" value="2" <?if($data['MONTHLY_TYPE'] == 2):?>checked<?endif?> class="js-id-replication-monthly-type task-options-radio" type="radio">
					<label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[MONTHLY_WEEK_DAY_NUM]" class="js-id-replication-monthly-week-day-num task-options-inp">
		                    <?for($i = 0; $i <= 4; $i++):?>
			                    <option value="<?=$i?>" <?if($data['MONTHLY_WEEK_DAY_NUM'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_'.$i.'_M')?></option>
		                    <?endfor?>
	                    </select>
	                </span>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[MONTHLY_WEEK_DAY]" class="js-id-replication-monthly-week-day task-options-inp">
							<?/** do not reorder weekday numbers, because strtotime() accepts 0 - monday, 6 - sunday, @see \Bitrix\Tasks\Util\Replicator\Task\FromTemplate::getNextTime() */?>
							<?for($k = 0; $k <= 6; $k++):?>
			                    <?$i = $arResult['AUX_DATA']['WEEKDAY_MAP'][$k]; // wee need mapping because of different week start?>
			                    <option value="<?=$i?>" <?if($data['MONTHLY_WEEK_DAY'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_'.$i)?></option>
		                    <?endfor?>
	                    </select>
	                </span>
					<label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M_ALT')?></label>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input type="text" class="js-id-replication-monthly-month-num-2 task-options-inp" name="<?=$prefix?>[MONTHLY_MONTH_NUM_2]" value="<?=intval($data['MONTHLY_MONTH_NUM_2'])?>">
	                </span>
					<label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
				</div>
			</div>

			<?//yearly?>
			<div class="js-id-replication-panel-yearly task-replication-params<?=($data['PERIOD'] != 'yearly' ? ' nodisplay' : ' opacity-1')?>">
				<div class="task-options-field">
					<input id="replication-yearly-type-1" name="<?=$prefix?>[YEARLY_TYPE]" value="1" <?if($data['YEARLY_TYPE'] == 1):?>checked<?endif?> class="js-id-replication-yearly-type task-options-radio" type="radio">
					<label class="task-field-label" for="replication-yearly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
	                <span class="task-options-inp-container task-options-inp-int">
	                    <input name="<?=$prefix?>[YEARLY_DAY_NUM]" value="<?=intval($data['YEARLY_DAY_NUM'])?>" type="text" class="js-id-replication-yearly-day-num task-options-inp">
	                </span>
					<label class="task-field-label" for="replication-yearly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAY_OF_MONTH')?></label>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[YEARLY_MONTH_1]" class="js-id-replication-yearly-month-1 task-options-inp">
		                    <?for($i = 0; $i <= 11; $i++):?>
			                    <option value="<?=$i?>" <?if($data['YEARLY_MONTH_1'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_'.$i)?></option>
		                    <?endfor?>
	                    </select>
	                </span>
				</div>
				<div class="task-options-field">
					<input id="replication-yearly-type-2" name="<?=$prefix?>[YEARLY_TYPE]" value="2" <?if($data['YEARLY_TYPE'] == 2):?>checked<?endif?> class="js-id-replication-yearly-type task-options-radio" type="radio">
					<label class="task-field-label" for="replication-yearly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[YEARLY_WEEK_DAY_NUM]" class="js-id-replication-yearly-week-day-num task-options-inp">
		                    <?for($i = 0; $i <= 4; $i++):?>
			                    <option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY_NUM'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_'.$i.'_M')?></option>
		                    <?endfor?>
	                    </select>
	                </span>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[YEARLY_WEEK_DAY]" class="js-id-replication-yearly-week-day task-options-inp">
							<?/** do not reorder weekday numbers, because strtotime() accepts 0 - monday, 6 - sunday, @see \Bitrix\Tasks\Util\Replicator\Task\FromTemplate::getNextTime() */?>
		                    <?for($k = 0; $k <= 6; $k++):?>
			                    <?$i = $arResult['AUX_DATA']['WEEKDAY_MAP'][$k]; // wee need mapping because of different week start?>
			                    <option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_'.$i)?></option>
		                    <?endfor?>
	                    </select>
	                </span>
					<label class="task-field-label" for="replication-yearly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
	                <span class="task-options-inp-container task-options-inp-container-period">
	                    <select name="<?=$prefix?>[YEARLY_MONTH_2]" class="js-id-replication-yearly-month-2 task-options-inp">
		                    <?for($i = 0; $i <= 11; $i++):?>
			                    <option value="<?=$i?>" <?if($data['YEARLY_MONTH_2'] == $i):?>selected<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_'.$i)?></option>
		                    <?endfor?>
	                    </select>
	                </span>
				</div>
			</div>
		</div>

		<div class="task-options-field">
			<div class="task-options-field task-options-field-left">
				<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_TASK_CTIME')?>:</label>
	            <span class="js-id-replication-timepicker task-options-inp-container task-options-inp-container-period task-options-inp-container-timer">
	                <input type="text" class="js-id-timepicker-display task-options-inp" readonly="readonly" />
		            <input class="js-id-replication-time js-id-timepicker-value" name="<?=$prefix?>[TIME]" type="hidden" value="<?=htmlspecialcharsbx($data['TIME'])?>" />
	                <div class="task-main-clock-monkeyfix">
		                <?$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.clock', '', array(
			                'INIT_TIME' => $data['TIME'] ? $data['TIME'] : '00:00',
			                'INPUT_ID' => $arResult['JS_DATA']['timerId'],
		                ));?>
	                </div>
	            </span>
				<span class="js-id-hint-help task-options-timezone-indicator"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_TZ_HINT', array('#TIMEZONE#' => \Bitrix\Tasks\UI::formatTimezoneOffsetUTC($tzOffset)))?></span>
				<input type="hidden" name="<?=$prefix?>[TIMEZONE_OFFSET]" value="<?=intval($arResult['CURRENT_TIMEZONE_OFFSET'])?>" />
			</div>
			<div class="task-options-field task-options-field-left">
				<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_START')?>:</label>
	            <span class="js-id-replication-start-date-datepicker task-options-inp-container task-options-date">
	                <input type="text" class="js-id-datepicker-display task-options-inp" value="" readonly="readonly">
	                <span class="js-id-datepicker-clear task-option-inp-del"></span>
	                <input class="js-id-datepicker-value" type="hidden" name="<?=$prefix?>[START_DATE]" value="<?=htmlspecialcharsbx($data['START_DATE'])?>" />
	            </span>
			</div>
		</div>
		<div class="task-options-field task-options-field-nol">
			<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END')?>:</label>
			<div class="task-options-field task-options-field-left">
				<input name="<?=$prefix?>[REPEAT_TILL]" value="endless" class="js-id-replication-repeat-till task-options-radio" id="replication-repeat-constraint-none" type="radio" <?=($data['REPEAT_TILL'] == 'endless' || empty($data['END_DATE']) ? 'checked' : '')?> />
				<span class="task-option-fn"><label for="replication-repeat-constraint-none" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_NONE')?></label></span>
			</div>
			<div class="task-options-field task-options-field-left">
				<input name="<?=$prefix?>[REPEAT_TILL]" value="date" class="js-id-replication-repeat-till task-options-radio" id="replication-repeat-constraint-date" type="radio" <?=($data['REPEAT_TILL'] == 'date' ? 'checked' : '')?> />
				<span class="task-option-fn"><label for="replication-repeat-constraint-date" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_DATE')?></label></span>
	            <span class="js-id-replication-end-date-datepicker task-options-inp-container task-options-date">
	                <input type="text" class="js-id-datepicker-display task-options-inp" value="" readonly="readonly">
	                <span class="js-id-datepicker-clear task-option-inp-del"></span>
	                <input class="js-id-datepicker-value" type="hidden" name="<?=$prefix?>[END_DATE]" value="<?=htmlspecialcharsbx($data['END_DATE'])?>" />
	            </span>
			</div>
			<div class="task-options-field task-options-field-left">
				<input name="<?=$prefix?>[REPEAT_TILL]" value="times" class="js-id-replication-repeat-till task-options-radio" id="replication-repeat-constraint-times" type="radio" <?=($data['REPEAT_TILL'] == 'times' ? 'checked' : '')?> />
				<span class="task-option-fn"><label for="replication-repeat-constraint-times" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_TIMES')?></label></span>
	            <span class="task-options-inp-container task-options-inp-int">
	                <input type="text" name="<?=$prefix?>[TIMES]" class="js-id-replication-times task-options-inp" value="<?=intval($data['TIMES'])?>">
	            </span>
				<span class="task-option-fn"><label class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEATS')?></label></span>
			</div>
		</div>

		<?if(LANGUAGE_ID == 'ru'):?>
			<div class="task-options-field-fn task-options-field-ok"><span class="js-id-replication-hint"></span></div>
		<?endif?>

	</div>

	<?$helper->initializeExtension();?>

<?endif?>