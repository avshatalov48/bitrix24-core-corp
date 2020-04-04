<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$prefix = htmlspecialcharsbx($arResult['TEMPLATE_DATA']['INPUT_PREFIX']);
$data = $arResult['TEMPLATE_DATA']['DATA'];
$tzOffset = intval($arResult['TEMPLATE_DATA']['UTC_TIME_ZONE_OFFSET']);
?>

<div id="bx-component-scope-<?=htmlspecialcharsbx($templateId)?>" class="task-options-repeat">

    <div class="task-options-field">
        <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_TYPE')?></span>
        <span data-bx-id="replication-period-type-selector" class="task-option-tab-container">
            <span data-bx-id="replication-period-type-option" class="task-option-tab daily" data-type="daily"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAILY')?></span>
            <span data-bx-id="replication-period-type-option" class="task-option-tab weekly" data-type="weekly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WEEKLY')?></span>
            <span data-bx-id="replication-period-type-option" class="task-option-tab monthly" data-type="monthly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTHLY')?></span>
            <span data-bx-id="replication-period-type-option" class="task-option-tab yearly" data-type="yearly"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_YEARLY')?></span>
        </span>
        <input data-bx-id="replication-period-type" type="hidden" name="<?=$prefix?>[PERIOD]" value="<?=htmlspecialcharsbx($data['PERIOD'])?>" />
    </div>

    <div data-bx-id="replication-panel" class="task-replication-panel">

        <?//daily?>
        <div data-bx-id="replication-panel-daily" class="task-replication-params<?=($data['PERIOD'] != 'daily' ? ' nodisplay' : ' opacity-1')?>">
            <div class="task-options-field">
                <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></span>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-every-day" type="text" class="task-options-inp" name="<?=$prefix?>[EVERY_DAY]" value="<?=intval($data['EVERY_DAY'])?>">
                </span>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-day-type" name="<?=$prefix?>[WORKDAY_ONLY]" class="task-options-inp">
                        <option value="Y"<?=($data['WORKDAY_ONLY'] == 'Y' ? ' selected' : '')?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAILY_WORK')?></option>
                        <option value="N"<?=($data['WORKDAY_ONLY'] != 'Y' ? ' selected' : '')?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_ANY')?></option>
                    </select>
                </span>
                <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAY_INTERVAL')?></span>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-daily-month-interval" type="text" name="<?=$prefix?>[DAILY_MONTH_INTERVAL]" value="<?=intval($data['DAILY_MONTH_INTERVAL'])?>" class="task-options-inp">
                </span>
                <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_SHORT')?></span>
            </div>
        </div>

        <?//weekly?>
        <div data-bx-id="replication-panel-weekly" class="task-replication-params<?=($data['PERIOD'] != 'weekly' ? ' nodisplay' : ' opacity-1')?>">
            <div class="task-options-field">
                <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_F')?></span>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-every-week" type="text" name="<?=$prefix?>[EVERY_WEEK]" class="task-options-inp" value="<?=intval($data['EVERY_WEEK'])?>">
                </span>
                <span class="task-option-fn"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WEEK_ALT')?></span>
            </div>
            <div class="task-options-field">
                <div class="task-options-day-container">
                    <?for($k = 0; $k <= 6; $k++):?>
                        <?$i = $arResult['TEMPLATE_DATA']['WEEKDAY_MAP'][$k] + 1;?>
                        <label class="task-options-day"><input data-bx-id="replication-week-days" type="checkbox" name="<?=$prefix?>[WEEK_DAYS][]" value="<?=$i?>" <?if(in_array($i, $data['WEEK_DAYS'])):?>checked="checked"<?endif?>/>&nbsp;<?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_SH_'.($i - 1))?></label>
                    <?endfor?>
                </div>
            </div>
        </div>

        <?//monthly?>
        <div data-bx-id="replication-panel-monthly" class="task-replication-params<?=($data['PERIOD'] != 'monthly' ? ' nodisplay' : ' opacity-1')?>">
            <div class="task-options-field">
                <input data-bx-id="replication-monthly-type" id="replication-monthly-type-1" data-bx-id="replication-monthly-type" name="<?=$prefix?>[MONTHLY_TYPE]" value="1" <?if($data['MONTHLY_TYPE'] == 1):?>checked<?endif?> class="task-options-radio" type="radio">
                <label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH')?></label>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-monthly-day-num" name="<?=$prefix?>[MONTHLY_DAY_NUM]" value="<?=intval($data['MONTHLY_DAY_NUM'])?>" type="text" class="task-options-inp" />
                </span>
                <label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_OF_EACH_M_ALT')?></label>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-monthly-month-num-1" name="<?=$prefix?>[MONTHLY_MONTH_NUM_1]" value="<?=intval($data['MONTHLY_MONTH_NUM_1'])?>" type="text" class="task-options-inp">
                </span>
                <label class="task-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
            </div>
            <div class="task-options-field">
                <input data-bx-id="replication-monthly-type" id="replication-monthly-type-2" name="<?=$prefix?>[MONTHLY_TYPE]" value="2" <?if($data['MONTHLY_TYPE'] == 2):?>checked<?endif?> class="task-options-radio" type="radio">
                <label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-monthly-week-day-num" name="<?=$prefix?>[MONTHLY_WEEK_DAY_NUM]" class="task-options-inp">
                        <?for($i = 0; $i <= 4; $i++):?>
                            <option value="<?=$i?>" <?if($data['MONTHLY_WEEK_DAY_NUM'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_'.$i.'_M')?></option>
                        <?endfor?>
                    </select>
                </span>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-monthly-week-day" name="<?=$prefix?>[MONTHLY_WEEK_DAY]" class="task-options-inp">
                        <?for($k = 0; $k <= 6; $k++):?>
                            <?$i = $arResult['TEMPLATE_DATA']['WEEKDAY_MAP'][$k];?>
                            <option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_'.$i)?></option>
                        <?endfor?>
                    </select>
                </span>
                <label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M_ALT')?></label>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-monthly-month-num-2" type="text" class="task-options-inp" name="<?=$prefix?>[MONTHLY_MONTH_NUM_2]" value="<?=intval($data['MONTHLY_MONTH_NUM_2'])?>">
                </span>
                <label class="task-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
            </div>
        </div>

        <?//yearly?>
        <div data-bx-id="replication-panel-yearly" class="task-replication-params<?=($data['PERIOD'] != 'yearly' ? ' nodisplay' : ' opacity-1')?>">
            <div class="task-options-field">
                <input data-bx-id="replication-yearly-type" id="replication-yearly-type-1" name="<?=$prefix?>[YEARLY_TYPE]" value="1" <?if($data['YEARLY_TYPE'] == 1):?>checked<?endif?> class="task-options-radio" type="radio">
                <label class="task-field-label" for="replication-yearly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
                <span class="task-options-inp-container task-options-inp-int">
                    <input data-bx-id="replication-yearly-day-num" name="<?=$prefix?>[YEARLY_DAY_NUM]" value="<?=intval($data['YEARLY_DAY_NUM'])?>" type="text" class="task-options-inp">
                </span>
                <label class="task-field-label" for="replication-yearly-type-1"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_DAY_OF_MONTH')?></label>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-yearly-month-1" name="<?=$prefix?>[YEARLY_MONTH_1]" class="task-options-inp">
                        <?for($i = 0; $i <= 11; $i++):?>
                            <option value="<?=$i?>" <?if($data['YEARLY_MONTH_1'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_'.$i)?></option>
                        <?endfor?>
                    </select>
                </span>
            </div>
            <div class="task-options-field">
                <input data-bx-id="replication-yearly-type" id="replication-yearly-type-2" name="<?=$prefix?>[YEARLY_TYPE]" value="2" <?if($data['YEARLY_TYPE'] == 2):?>checked<?endif?> class="task-options-radio" type="radio">
                <label class="task-field-label" for="replication-yearly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_EACH_M')?></label>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-yearly-week-day-num" name="<?=$prefix?>[YEARLY_WEEK_DAY_NUM]" class="task-options-inp">
                        <?for($i = 0; $i <= 4; $i++):?>
                            <option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY_NUM'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_NUMBER_'.$i.'_M')?></option>
                        <?endfor?>
                    </select>
                </span>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-yearly-week-day" name="<?=$prefix?>[YEARLY_WEEK_DAY]" class="task-options-inp">
                        <?for($k = 0; $k <= 6; $k++):?>
                            <?$i = $arResult['TEMPLATE_DATA']['WEEKDAY_MAP'][$k];?>
                            <option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_WD_'.$i)?></option>
                        <?endfor?>
                    </select>
                </span>
                <label class="task-field-label" for="replication-yearly-type-2"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_ALT')?></label>
                <span class="task-options-inp-container task-options-inp-container-period">
                    <select data-bx-id="replication-yearly-month-2" name="<?=$prefix?>[YEARLY_MONTH_2]" class="task-options-inp">
                        <?for($i = 0; $i <= 11; $i++):?>
                            <option value="<?=$i?>" <?if($data['YEARLY_MONTH_2'] == $i):?>checked<?endif?>><?=Loc::getMessage('TASKS_TTDP_REPLICATION_MONTH_'.$i)?></option>
                        <?endfor?>
                    </select>
                </span>
            </div>
        </div>

    </div>

    <div class="task-options-field">
        <div class="task-options-field task-options-field-left">
            <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_TASK_CTIME')?>:</label>
            <span data-bx-id="replication-timepicker" class="task-options-inp-container task-options-inp-container-period task-options-inp-container-timer">
                <input data-bx-id="timepicker-display" type="text" class="task-options-inp" readonly="readonly" />
	            <input data-bx-id="replication-time timepicker-value" name="<?=$prefix?>[TIME]" type="hidden" value="<?=htmlspecialcharsbx($data['TIME'])?>" />
                <div class="task-main-clock-monkeyfix">
                    <?$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.clock', '', array(
                        'INIT_TIME' => $data['TIME'] ? $data['TIME'] : '00:00',
                        'INPUT_ID' => 'taskReplicationTimeFake'
                    ));?>
                </div>
            </span>
	        <span class="js-id-hint-help task-options-timezone-indicator"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_TZ_HINT', array('#TIMEZONE#' => \Bitrix\Tasks\UI::formatTimezoneOffsetUTC($tzOffset)))?></span>
            <input type="hidden" name="<?=$prefix?>[TIMEZONE_OFFSET]" value="<?=intval($arResult['TEMPLATE_DATA']['DATA']['TIMEZONE_OFFSET'])?>" />
        </div>
        <div class="task-options-field task-options-field-left">
            <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_START')?>:</label>
            <span data-bx-id="replication-start-date-datepicker" class="task-options-inp-container task-options-date">
                <input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
                <span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
                <input data-bx-id="datepicker-value" type="hidden" name="<?=$prefix?>[START_DATE]" value="<?=htmlspecialcharsbx($data['START_DATE'])?>" />
            </span>
        </div>
    </div>
    <div class="task-options-field task-options-field-nol">
        <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END')?>:</label>
        <div class="task-options-field task-options-field-left">
            <input data-bx-id="replication-repeat-till" name="<?=$prefix?>[REPEAT_TILL]" value="endless" class="task-options-radio" id="replication-repeat-constraint-none" type="radio" <?=($data['REPEAT_TILL'] == 'endless' || empty($data['END_DATE']) ? 'checked' : '')?> />
            <span class="task-option-fn"><label for="replication-repeat-constraint-none" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_NONE')?></label></span>
        </div>
        <div class="task-options-field task-options-field-left">
            <input data-bx-id="replication-repeat-till" name="<?=$prefix?>[REPEAT_TILL]" value="date" class="task-options-radio" id="replication-repeat-constraint-date" type="radio" <?=($data['REPEAT_TILL'] == 'date' ? 'checked' : '')?> />
            <span class="task-option-fn"><label for="replication-repeat-constraint-date" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_DATE')?></label></span>
            <span data-bx-id="replication-end-date-datepicker" class="task-options-inp-container task-options-date">
                <input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
                <span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
                <input data-bx-id="datepicker-value" type="hidden" name="<?=$prefix?>[END_DATE]" value="<?=htmlspecialcharsbx($data['END_DATE'])?>" />
            </span>
        </div>
        <div class="task-options-field task-options-field-left">
            <input data-bx-id="replication-repeat-till" name="<?=$prefix?>[REPEAT_TILL]" value="times" class="task-options-radio" id="replication-repeat-constraint-times" type="radio" <?=($data['REPEAT_TILL'] == 'times' ? 'checked' : '')?> />
            <span class="task-option-fn"><label for="replication-repeat-constraint-times" class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEAT_END_C_TIMES')?></label></span>
            <span class="task-options-inp-container task-options-inp-int">
                <input data-bx-id="replication-times" type="text" name="<?=$prefix?>[TIMES]" class="task-options-inp" value="<?=intval($data['TIMES'])?>">
            </span>
            <span class="task-option-fn"><label class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_REPLICATION_REPEATS')?></label></span>
        </div>
    </div>

	<?if(LANGUAGE_ID == 'ru'):?>
        <div class="task-options-field-fn task-options-field-ok"><span data-bx-id="replication-hint"></span></div>
	<?endif?>
</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsReplication(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'data' => $data,
		'tzOffset' => $tzOffset
	), false, false, true)?>);
</script>