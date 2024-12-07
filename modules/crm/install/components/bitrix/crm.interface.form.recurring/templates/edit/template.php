<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Recurring\Manager;
use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Entity\Base;
use Bitrix\Crm\Recurring\Entity\Invoice;

CJSCore::RegisterExt(
	"recurringLang",
	array(
		"lang" => CUtil::JSEscape($component->getPath())."/lang/".LANGUAGE_ID."/class.php"
	)
);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'recurringLang',
]);


$templateId = $arParams['TEMPLATE_ID'];
$data = $arParams['DATA'];

?>
<div id="crm-recur-edit-replication-block"
	 class="crm-recur-options-item-open-inner">
	<label class="crm-recur-field-label crm-recur-field-label-repeat <?=$arParams['IS_RECURRING']=='Y' ? ' crm-recur-invisible' : ''?>">
		<?= Loc::getMessage("CRM_RECURRING_{$arParams['ENTITY_TYPE']}_SWITCHER_BLOCK") ?>
		<input id="crm-recurring-flag"
				class="crm-recur-options-checkbox crm-recur-invisible"
				name="<?= $arResult['RESTRICTED_LICENCE'] == 'Y' ?: "RECUR_PARAM[RECURRING_SWITCHER]"?>"
				type="checkbox"
			   	value="Y"
				<?= $arResult['RESTRICTED_LICENCE'] !== 'Y' ?: "disabled"?>
				<?= ($data['RECURRING_SWITCHER'] == 'Y' && $arResult['RESTRICTED_LICENCE'] !== 'Y') ? 'checked' : ''?>>
		<span class="crm-recur-custom-checkbox
				<?=($data['RECURRING_SWITCHER'] == 'Y' && $arResult['RESTRICTED_LICENCE'] !== 'Y') ? 'crm-recur-checkbox-checked' : ''?>
				<?=($arResult['RESTRICTED_LICENCE'] == 'Y') ? 'crm-recur-checkbox-blocked' : ''?>"
		></span>
<?
if ($arResult['RESTRICTED_LICENCE'] == 'Y')
{
	?>
		<span class="tariff-lock"></span>
	</label>
	</div>
	<script>
		BX.bind(BX('crm-recur-edit-replication-block'), 'click', function(e){
			<?=$arResult["TRIAL_TEXT"]['LOCK_SCRIPT']?>
		});
	</script>

	<?
		return;
}
?>
	</label>
	<div id="crm-recur-edit-recurring-panel"
		 class="crm-recur-options-repeat crm-recur-openable-block<?= ($data['RECURRING_SWITCHER'] == 'Y' || $arParams['IS_RECURRING']==='Y'? '' : ' crm-recur-invisible') ?>">
		<div id="bx-component-scope-<?=htmlspecialcharsbx($templateId)?>" class="crm-recur-options-repeat">
			<div class="crm-recur-options-field">
				<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_TYPE')?></span>
				<?
				if ($arResult["ENTITY_TYPE_ID"] == \CCrmOwnerType::Invoice)
				{
					/**Invoice recurring data filter*/
					?>
						<span id="period-type-selector" class="crm-recur-option-tab-container">
							<?
							if (empty($data['PERIOD']))
							{
								$data['PERIOD'] = 1;
							}
							?>
							<span data-bx-id="replication-period-type-option"
									class="crm-recur-option-tab period-type-option <?=(int)($data['PERIOD']) <= 1 || (int)($data['PERIOD']) > 4 ? "active-recur" : ""?>"
									data-type="1">
								<?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY')?>
							</span>
							<span data-bx-id="replication-period-type-option"
									class="crm-recur-option-tab period-type-option <?=(int)($data['PERIOD']) == 2 ? "active-recur" : ""?>"
									data-type="2"><?=Loc::getMessage('CRM_RECURRING_FILTER_WEEKLY')?></span>
							<span data-bx-id="replication-period-type-option"
									class="crm-recur-option-tab period-type-option <?=(int)($data['PERIOD']) == 3 ? "active-recur" : ""?>"
									data-type="3"><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTHLY')?></span>
							<span data-bx-id="replication-period-type-option"
									class="crm-recur-option-tab period-type-option <?=(int)($data['PERIOD']) == 4 ? "active-recur" : ""?>"
									data-type="4"><?=Loc::getMessage('CRM_RECURRING_FILTER_YEARLY')?></span>
						</span>
						<input id="period"
								type="hidden"
								name="RECUR_PARAM[PERIOD]"
								value="<?=(int)($data['PERIOD']) > 0 && (int)($data['PERIOD']) <= 4 ? (int)($data['PERIOD']) : 1?>" />
					</div>
					<div data-bx-id="replication-panel" class="crm-recur-replication-panel">
						<?//daily?>
						<div id="panel-period-1" class="crm-recur-replication-params<?=((int)($data['PERIOD']) == 1 ? ' opacity-1' : ' nodisplay')?>">
							<div class="crm-recur-options-field">
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M')?></span>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="daily-interval-day"
										   type="text"
										   class="crm-recur-options-inp"
										   name="RECUR_PARAM[DAILY_INTERVAL_DAY]"
										   value="<?=(int)($data['DAILY_INTERVAL_DAY']) > 0 ? (int)($data['DAILY_INTERVAL_DAY']) : 1?>">
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="daily-workday-only" name="RECUR_PARAM[DAILY_WORKDAY_ONLY]" class="crm-recur-options-inp">
										<option class="workday-only-select" value="Y"<?=($data['DAILY_WORKDAY_ONLY'] == 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_WORK')?></option>
										<option class="workday-only-select" value="N"<?=($data['DAILY_WORKDAY_ONLY'] != 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_ANY')?></option>
									</select>
								</label>
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_DAY_INTERVAL')?></span>
							</div>
						</div>

						<?//weekly?>
						<div id="panel-period-2" class="crm-recur-replication-params<?=((int)($data['PERIOD']) == 2 ? ' opacity-1' : ' nodisplay')?>">
							<div class="crm-recur-options-field">
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_F')?></span>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="weekly-interval-week"
										   type="text"
										   name="RECUR_PARAM[WEEKLY_INTERVAL_WEEK]"
										   class="crm-recur-options-inp"
										   value="<?=(int)($data['WEEKLY_INTERVAL_WEEK']) ? (int)($data['WEEKLY_INTERVAL_WEEK']) : 1?>">
								</label>
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_WEEK_ALT')?></span>
							</div>
							<div class="crm-recur-options-field">
								<div class="crm-recur-options-day-container">
									<?for($k = 1; $k <= 7; $k++):?>
										<label class="crm-recur-options-day">
											<input class="crm-recur-options-checkbox weekly-week-days crm-recur-invisible"
												   type="checkbox"
												   name="RECUR_PARAM[WEEKLY_WEEK_DAYS][]"
												   value="<?=$k?>"
												<?
													if (is_array($data['WEEKLY_WEEK_DAYS']) && in_array($k, $data['WEEKLY_WEEK_DAYS'])
														|| ($k === 1 && empty($data['WEEKLY_WEEK_DAYS'])))
													{
														echo  'checked';
													}
												?>/>
											<span class="crm-recur-custom-checkbox
													<?
												if (is_array($data['WEEKLY_WEEK_DAYS']) && in_array($k, $data['WEEKLY_WEEK_DAYS'])
													|| ($k === 1 && empty($data['WEEKLY_WEEK_DAYS'])))
												{
													echo  ' crm-recur-checkbox-checked';
												}
											?>"></span>
											&nbsp;<?=Loc::getMessage('CRM_RECURRING_FILTER_WD_SH_'.$k)?>
										</label>
									<?endfor?>
								</div>
							</div>
						</div>

						<?//monthly?>
						<div id="panel-period-3" class="crm-recur-replication-params<?=((int)($data['PERIOD']) == 3 ? ' opacity-1' :  ' nodisplay')?>">
							<div class="crm-recur-options-field">
								<label for="monthly-type-1">
									<input data-bx-id="replication-monthly-type"
										   id="monthly-type-1"
										   name="RECUR_PARAM[MONTHLY_TYPE]" value="1"
										   <?if($data['MONTHLY_TYPE'] == 1 || empty($data['MONTHLY_TYPE'])):?>checked<?endif?> class="crm-recur-options-radio monthly-type crm-recur-invisible"
										   type="radio">
									<span class="crm-recur-custom-radio <?=($data['MONTHLY_TYPE'] == 1 || empty($data['MONTHLY_TYPE'])) ? 'crm-recur-radio-checked' : ''?>"></span>
									<span class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M')?></span>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="monthly-day-num"
										   name="RECUR_PARAM[MONTHLY_INTERVAL_DAY]"
										   value="<?=(int)($data['MONTHLY_INTERVAL_DAY']) ? (int)($data['MONTHLY_INTERVAL_DAY']) : 1?>"
										   type="text"
										   class="crm-recur-options-inp" />
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="monthly-workday-only" name="RECUR_PARAM[MONTHLY_WORKDAY_ONLY]" class="crm-recur-options-inp">
										<option class="monthly-only-select" value="Y"<?=($data['MONTHLY_WORKDAY_ONLY'] == 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_WORK')?></option>
										<option class="monthly-only-select" value="N"<?=($data['MONTHLY_WORKDAY_ONLY'] != 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_ANY')?></option>
									</select>
								</label>
								<label class="crm-recur-field-label" for="replication-monthly-type-1">
									<?=Loc::getMessage('CRM_RECURRING_FILTER_NUMBER_OF_EACH_M_ALT')?>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="monthly-month-num-1"
										   name="RECUR_PARAM[MONTHLY_MONTH_NUM_1]"
										   value="<?=(int)($data['MONTHLY_MONTH_NUM_1']) ? (int)($data['MONTHLY_MONTH_NUM_1']) : 1?>"
										   type="text"
										   class="crm-recur-options-inp">
								</label>
								<label class="crm-recur-field-label" for="replication-monthly-type-1"><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTH_ALT')?></label>
							</div>
							<div class="crm-recur-options-field">
								<label for="monthly-type-2">
									<input data-bx-id="replication-monthly-type"
										   id="monthly-type-2"
										   name="RECUR_PARAM[MONTHLY_TYPE]"
										   value="2"
										   <?if($data['MONTHLY_TYPE'] == 2):?>checked<?endif?> class="crm-recur-options-radio monthly-type crm-recur-invisible"
										   type="radio">
									<span class="crm-recur-custom-radio <?=($data['MONTHLY_TYPE'] == 2) ? 'crm-recur-radio-checked' : ''?>"></span>
									<span class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M')?></span>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="monthly-week-day-num"
											name="RECUR_PARAM[MONTHLY_WEEKDAY_NUM]"
											class="crm-recur-options-inp">
										<?for($i = 0; $i <= 4; $i++):?>
											<option value="<?=$i?>" <?if($data['MONTHLY_WEEKDAY_NUM'] == $i):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_NUMBER_'.$i.'_M')?></option>
										<?endfor?>
									</select>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="monthly-week-day" name="RECUR_PARAM[MONTHLY_WEEK_DAY]" class="crm-recur-options-inp">
										<?for($k = 1; $k <= 7; $k++):?>
											<option value="<?=$k?>" <?if($data['MONTHLY_WEEK_DAY'] == $k):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_WD_'.$k)?></option>
										<?endfor?>
									</select>
								</label>
								<label class="crm-recur-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M_ALT')?></label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="monthly-month-num-2"
										   type="text" class="crm-recur-options-inp"
										   name="RECUR_PARAM[MONTHLY_MONTH_NUM_2]"
										   value="<?=(int)($data['MONTHLY_MONTH_NUM_2']) ? (int)($data['MONTHLY_MONTH_NUM_2']) : 1?>">
								</label>
								<label class="crm-recur-field-label" for="replication-monthly-type-2"><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTH_ALT')?></label>
							</div>
						</div>

						<?//yearly?>
						<div id="panel-period-4" class="crm-recur-replication-params<?=((int)($data['PERIOD']) == 4 ? ' opacity-1' : ' nodisplay')?>">
							<div class="crm-recur-options-field">
								<label for="yearly-type-1">
									<input id="yearly-type-1"
										   name="RECUR_PARAM[YEARLY_TYPE]"
										   value="1" <?if($data['YEARLY_TYPE'] == 1 || empty($data['YEARLY_TYPE'] )):?>checked<?endif?>
										   class="crm-recur-options-radio yearly-type crm-recur-invisible"
										   type="radio">
									<span class="crm-recur-custom-radio <?=($data['YEARLY_TYPE'] == 1 || empty($data['YEARLY_TYPE'])) ? 'crm-recur-radio-checked' : ''?>"></span>
									<span class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M')?></span>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
									<input id="yearly-interval-day"
										   name="RECUR_PARAM[YEARLY_INTERVAL_DAY]"
										   value="<?=(int)($data['YEARLY_INTERVAL_DAY']) ? (int)($data['YEARLY_INTERVAL_DAY']) : 1?>"
										   type="text"
										   class="crm-recur-options-inp">
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="yearly-workday-only" name="RECUR_PARAM[YEARLY_WORKDAY_ONLY]" class="crm-recur-options-inp">
										<option class="yearly-only-select" value="Y"<?=($data['YEARLY_WORKDAY_ONLY'] == 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_WORK')?></option>
										<option class="yearly-only-select" value="N"<?=($data['YEARLY_WORKDAY_ONLY'] != 'Y' ? ' selected' : '')?>><?=Loc::getMessage('CRM_RECURRING_FILTER_DAILY_ANY')?></option>
									</select>
								</label>
								<label class="crm-recur-field-label" for="replication-yearly-type-1"><?=Loc::getMessage('CRM_RECURRING_FILTER_DAY_OF_MONTH')?></label>

								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="yearly-month-1"
											name="RECUR_PARAM[YEARLY_MONTH_NUM_1]"
											class="crm-recur-options-inp">
										<?for($i = 1; $i <= 12; $i++):?>
											<option value="<?=$i?>" <?if($data['YEARLY_MONTH_NUM_1'] == $i):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTH_'.$i)?></option>
										<?endfor?>
									</select>
								</label>
							</div>
							<div class="crm-recur-options-field">
								<label for="yearly-type-2">
									<input id="yearly-type-2"
										   name="RECUR_PARAM[YEARLY_TYPE]"
										   value="2" <?if($data['YEARLY_TYPE'] == 2):?>checked<?endif?>
										   class="crm-recur-options-radio yearly-type crm-recur-invisible"
										   type="radio">
									<span class="crm-recur-custom-radio <?=($data['YEARLY_TYPE'] == 2) ? 'crm-recur-radio-checked' : ''?>"></span>
									<span class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_EACH_M')?></span>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="yearly-week-day-num"
											name="RECUR_PARAM[YEARLY_WEEK_DAY_NUM]"
											class="crm-recur-options-inp">
										<?for($i = 0; $i <= 4; $i++):?>
											<option value="<?=$i?>" <?if($data['YEARLY_WEEK_DAY_NUM'] == $i):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_NUMBER_'.$i.'_M')?></option>
										<?endfor?>
									</select>
								</label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="yearly-week-day" name="RECUR_PARAM[YEARLY_WEEK_DAY]" class="crm-recur-options-inp">
										<?for($k = 1; $k <= 7; $k++):?>
											<option value="<?=$k?>" <?if($data['YEARLY_WEEK_DAY'] == $k):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_WD_'.$k)?></option>
										<?endfor?>

									</select>
								</label>
								<label class="crm-recur-field-label" for="replication-yearly-type-2"><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTH_ALT')?></label>
								<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
									<select id="yearly-month-2" name="RECUR_PARAM[YEARLY_MONTH_NUM_2]" class="crm-recur-options-inp">
										<?for($i = 1; $i <= 12; $i++):?>
											<option value="<?=$i?>" <?if($data['YEARLY_MONTH_NUM_2'] == $i):?>selected<?endif?>><?=Loc::getMessage('CRM_RECURRING_FILTER_MONTH_'.$i)?></option>
										<?endfor?>
									</select>
								</label>
							</div>
						</div>
					</div>
					<?
						/**Beginning block*/
					?>
					<div class="crm-recur-options-field">
						<div class="crm-recur-options-field crm-recur-options-field-left">
							<label for="" class="crm-recur-field-label crm-recur-field-label-br"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_START')?>:</label>
							<label data-bx-id="replication-start-date-datepicker" class="crm-recur-options-inp-container crm-recur-options-date">
								<input id="datepicker-display-start"
									   type="text"
									   class="crm-recur-options-inp"
									   value="<?=htmlspecialcharsbx($data['START_DATE'])?>"
									   name="RECUR_PARAM[START_DATE]">
								<span data-bx-id="datepicker-clear" class="crm-recur-option-inp-del"></span>
							</label>
						</div>
					</div>
					<?
				}
				elseif ($arResult["ENTITY_TYPE_ID"] == \CCrmOwnerType::Deal)
				{
					/**Deal recurring data filter*/
					?>
						<div class="crm-recur-options-field">
							<label>
								<input id="crm-recur-deal-every"
									   name="RECUR_PARAM[EXECUTION_TYPE]" value="<?=Manager::MULTIPLY_EXECUTION?>"
									   <?if($data['EXECUTION_TYPE'] == Manager::MULTIPLY_EXECUTION || empty($data['MONTHLY_TYPE'])):?>checked<?endif?> class="crm-recur-options-radio selected-deal-type crm-recur-invisible"
									   type="radio">
								<span class="crm-recur-custom-radio <?=($data['EXECUTION_TYPE'] == Manager::MULTIPLY_EXECUTION || empty($data['EXECUTION_TYPE'])) ? 'crm-recur-radio-checked' : ''?>"></span>
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_DO_REPEAT')?></span>
							</label>
							<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
								<select id="period-deal-select" name="RECUR_PARAM[PERIOD_DEAL]" class="crm-recur-options-inp">
									<option class="period-deal-select"
											value="<?=Calculator::SALE_TYPE_DAY_OFFSET?>"
											<?=($data['PERIOD_DEAL'] == Calculator::SALE_TYPE_DAY_OFFSET || empty($data['PERIOD_DEAL'])? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_EVERY_DAY')?>
									</option>
									<option class="period-deal-select"
											value="<?=Calculator::SALE_TYPE_WEEK_OFFSET?>"
											<?=($data['PERIOD_DEAL'] == Calculator::SALE_TYPE_WEEK_OFFSET ? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_EVERY_WEEK')?>
									</option>
									<option class="period-deal-select"
											value="<?=Calculator::SALE_TYPE_MONTH_OFFSET?>"
											<?=($data['PERIOD_DEAL'] == Calculator::SALE_TYPE_MONTH_OFFSET ? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_EVERY_MONTH')?>
									</option>
									<option class="period-deal-select"
											value="<?=Calculator::SALE_TYPE_YEAR_OFFSET?>"
											<?=($data['PERIOD_DEAL'] == Calculator::SALE_TYPE_YEAR_OFFSET ? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_EVERY_YEAR')?>
									</option>
								</select>
							</label>
						</div>
						<div class="crm-recur-options-field">
							<label>
								<input id="crm-recur-deal-before"
									   name="RECUR_PARAM[EXECUTION_TYPE]" value="<?=Manager::SINGLE_EXECUTION?>"
									   <?if($data['EXECUTION_TYPE'] == Manager::SINGLE_EXECUTION):?>checked<?endif?> class="crm-recur-options-radio selected-deal-type crm-recur-invisible"
									   type="radio">
								<span class="crm-recur-custom-radio <?=($data['EXECUTION_TYPE'] == Manager::SINGLE_EXECUTION) ? 'crm-recur-radio-checked' : ''?>"></span>
								<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_DEAL_CREATE_BY')?></span>
							</label>
							<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
								<input id="deal-count-before"
									   type="text"
									   name="RECUR_PARAM[DEAL_COUNT_BEFORE]"
									   class="crm-recur-options-inp",
									   value="<?=(int)($data['DEAL_COUNT_BEFORE']) ? (int)($data['DEAL_COUNT_BEFORE']) : 0?>">
							</label>
							<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
								<select id="deal-type-before" name="RECUR_PARAM[DEAL_TYPE_BEFORE]" class="crm-recur-options-inp">
									<option class="deal-type-before-select"
											value="<?=Calculator::SALE_TYPE_DAY_OFFSET?>"
											<?=($data['DEAL_TYPE_BEFORE'] == Calculator::SALE_TYPE_DAY_OFFSET || empty($data['DEAL_TYPE_BEFORE'])? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_DAYS')?>
									</option>
									<option class="deal-type-before-select"
											value="<?=Calculator::SALE_TYPE_WEEK_OFFSET?>"
											<?=($data['DEAL_TYPE_BEFORE'] == Calculator::SALE_TYPE_WEEK_OFFSET ? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_WEEKS')?>
									</option>
									<option class="deal-type-before-select"
											value="<?=Calculator::SALE_TYPE_MONTH_OFFSET?>"
											<?=($data['DEAL_TYPE_BEFORE'] == Calculator::SALE_TYPE_MONTH_OFFSET ? ' selected' : '')?>>
										<?=Loc::getMessage('CRM_RECURRING_FILTER_MONTHS')?>
									</option>
								</select>
							</label>
							<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_BEFORE_DATE')?></span>
							<label data-bx-id="replication-start-date-datepicker" class="crm-recur-options-inp-container crm-recur-options-date">
								<input id="deal-datepicker-before"
									   type="text"
									   class="crm-recur-options-inp"
									   value="<?=htmlspecialcharsbx($data['DEAL_DATEPICKER_BEFORE'])?>"
									   name="RECUR_PARAM[DEAL_DATEPICKER_BEFORE]">
								<span data-bx-id="datepicker-clear" class="crm-recur-option-inp-del"></span>
							</label>
						</div>
					</div>
					<?
				}
				?>
			<div id="crm-recur-end-block"  class="crm-recur-options-field crm-recur-options-field-nol">
				<label for="" class="crm-recur-field-label crm-recur-field-label-br"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_END')?>:</label>
				<div class="crm-recur-options-field crm-recur-options-field-left">
					<input data-bx-id="replication-repeat-till"
							name="RECUR_PARAM[REPEAT_TILL]"
							value="<?=$data['REPEAT_TILL'] == Base::NO_LIMITED?>"
							class="crm-recur-options-radio selected-end"
							id="replication-repeat-constraint-none"
							type="radio"
						<?=($data['REPEAT_TILL'] == 'endless' || empty($data['REPEAT_TILL']) || $data['REPEAT_TILL'] == Base::NO_LIMITED)? 'checked' : ''?> />
					<span class="crm-recur-option-fn"><label for="replication-repeat-constraint-none" class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_END_C_NONE')?></label></span>
				</div>
				<div class="crm-recur-options-field crm-recur-options-field-left">
					<input data-bx-id="replication-repeat-till"
							name="RECUR_PARAM[REPEAT_TILL]"
							value="<?=Base::LIMITED_BY_DATE?>"
							class="crm-recur-options-radio selected-end"
							id="replication-repeat-constraint-date"
							type="radio" <?=($data['REPEAT_TILL'] == 'date' || $data['REPEAT_TILL'] == Base::LIMITED_BY_DATE) ? 'checked' : ''?> />
					<span class="crm-recur-option-fn"><label for="replication-repeat-constraint-date" class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_END_C_DATE')?></label></span>
					<label data-bx-id="replication-end-date-datepicker" class="crm-recur-options-inp-container crm-recur-options-date">
						<input id="datepicker-display-end"
								type="text"
								class="crm-recur-options-inp"
								name="RECUR_PARAM[END_DATE]"
								value="<?=htmlspecialcharsbx($data['END_DATE'])?>">
						<span data-bx-id="datepicker-clear" class="crm-recur-option-inp-del"></span>
					</label>
				</div>
				<div class="crm-recur-options-field crm-recur-options-field-left">
					<input data-bx-id="replication-repeat-till"
							name="RECUR_PARAM[REPEAT_TILL]"
							value="<?=Base::LIMITED_BY_TIMES?>"
							class="crm-recur-options-radio selected-end"
							id="replication-repeat-constraint-times"
							type="radio" <?=($data['REPEAT_TILL'] == 'times' || $data['REPEAT_TILL'] == Base::LIMITED_BY_TIMES) ? 'checked' : ''?> />
					<span class="crm-recur-option-fn"><label for="replication-repeat-constraint-times" class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEAT_END_C_TIMES')?></label></span>
					<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
						<input id="end-times"
								type="text"
								name="RECUR_PARAM[LIMIT_REPEAT]"
								class="crm-recur-options-inp"
								value="<?=(int)($data['LIMIT_REPEAT']) ? (int)($data['LIMIT_REPEAT']) : 0?>">
					</label>
					<span class="crm-recur-option-fn"><label class="crm-recur-field-label"><?=Loc::getMessage('CRM_RECURRING_FILTER_REPEATS')?></label></span>
				</div>
			</div>
			<?
			if ($arResult["ENTITY_TYPE_ID"] == \CCrmOwnerType::Deal && is_array($arResult["DEAL_CATEGORISES"]))
			{
				?>
				<div class="crm-recur-options-field">
					<div class="crm-recur-options-field crm-recur-options-field-left">
						<label for="" class="crm-recur-field-label crm-recur-field-label-br"><?=Loc::getMessage("CRM_RECURRING_FILTER_{$arParams['ENTITY_TYPE']}_CATEGORISES")?>:</label>
						<label class="crm-recur-options-inp-container">
							<select id="yearly-week-day" name="ENTITY_CATEGORY_ID" class="crm-recur-options-inp">
								<?
								foreach($arResult["DEAL_CATEGORISES"] as $id => $name)
								{
									?>
									<option value="<?=$id?>" <?if($data['CATEGORY_ID'] == $id):?>selected<?endif?>><?=htmlspecialcharsbx($name)?></option>
									<?
								}
								?>
							</select>
						</label>
					</div>
				</div>
				<?
			}
			if ($arResult["ENTITY_TYPE_ID"] == \CCrmOwnerType::Invoice)
			{
				?>
				<div id="crm-recur-payment-data-block" class="crm-recur-options-field crm-recur-options-field-nol">
					<label for="" class="crm-recur-field-label crm-recur-field-label-br"><?=Loc::getMessage('CRM_RECURRING_FILTER_CREATE_DATE_PAYMENT')?>:</label>
					<div class="crm-recur-options-field">
						<label>
							<input data-bx-id="replication-repeat-till"
								   name="RECUR_PARAM[DATE_PAY_BEFORE_TYPE]"
								   value="<?=Invoice::UNSET_DATE_PAY_BEFORE?>"
								   class="crm-recur-options-radio"
								   type="radio"
								<?=(empty($data['DATE_PAY_BEFORE_TYPE']) || $data['DATE_PAY_BEFORE_TYPE'] == Invoice::UNSET_DATE_PAY_BEFORE)? 'checked' : ''?> />
							<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_UNSET_DATE_PAYMENT')?></span>
						</label>
					</div>
					<div class="crm-recur-options-field">
						<label>
							<input data-bx-id="replication-repeat-till"
								   name="RECUR_PARAM[DATE_PAY_BEFORE_TYPE]"
								   value="<?=Invoice::SET_DATE_PAY_BEFORE?>"
								   class="crm-recur-options-radio"
								   type="radio"
								<?=($data['DATE_PAY_BEFORE_TYPE'] == Invoice::SET_DATE_PAY_BEFORE)? 'checked' : ''?> />
							<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_DATE_PAYMENT_IN_DATE')?></span>
						</label>
						<label class="crm-recur-options-inp-container crm-recur-options-inp-int">
							<input type="text"
								   name="RECUR_PARAM[DATE_PAY_BEFORE_COUNT]"
								   class="crm-recur-options-inp"
								   value="<?=(int)($data['DATE_PAY_BEFORE_COUNT']) ? (int)($data['DATE_PAY_BEFORE_COUNT']) : 0?>">
						</label>
						<label class="crm-recur-options-inp-container crm-recur-options-inp-container-period">
							<select name="RECUR_PARAM[DATE_PAY_BEFORE_PERIOD]" class="crm-recur-options-inp">
								<option class="payment-date-type-before-select"
										value="<?=Calculator::SALE_TYPE_DAY_OFFSET?>"
									<?=($data['DATE_PAY_BEFORE_PERIOD'] == Calculator::SALE_TYPE_DAY_OFFSET || empty($data['DATE_PAY_BEFORE_PERIOD'])? ' selected' : '')?>>
									<?=Loc::getMessage('CRM_RECURRING_FILTER_DAYS')?>
								</option>
								<option class="payment-date-type-before-select"
										value="<?=Calculator::SALE_TYPE_WEEK_OFFSET?>"
									<?=($data['DATE_PAY_BEFORE_PERIOD'] == Calculator::SALE_TYPE_WEEK_OFFSET ? ' selected' : '')?>>
									<?=Loc::getMessage('CRM_RECURRING_FILTER_WEEKS')?>
								</option>
								<option class="payment-date-type-before-select"
										value="<?=Calculator::SALE_TYPE_MONTH_OFFSET?>"
									<?=($data['DATE_PAY_BEFORE_PERIOD'] == Calculator::SALE_TYPE_MONTH_OFFSET ? ' selected' : '')?>>
									<?=Loc::getMessage('CRM_RECURRING_FILTER_MONTHS')?>
								</option>
							</select>
						</label>
						<span class="crm-recur-option-fn"><?=Loc::getMessage('CRM_RECURRING_FILTER_DATE_PAYMENT_AFTER_EXPOSING')?></span>
					</div>
				</div>
				<div id="crm-recur-email-block"	class="<?= ($arResult['EMAIL_LIST'][0]['text'] <> '') ? "" : " crm-recur-invisible"?>">
					<div id="crm-recurring-empty-owner-email" class="errortext"></div>

					<label for="crm-recurring-email">
						<input id="crm-recurring-email"
							   class="crm-recur-options-checkbox  crm-recur-invisible"
							   name="RECUR_PARAM[RECURRING_EMAIL_SEND]"
							   type="checkbox"
							   value="Y"
							<?= ($data['RECURRING_EMAIL_SEND'] == 'Y' ? 'checked' : '') ?>>
						<span class="crm-recur-custom-checkbox <?=($data['RECURRING_EMAIL_SEND'] == 'Y') ? 'crm-recur-checkbox-checked' : ''?>"></span>
					</label>

					<span><?=Loc::getMessage('CRM_RECURRING_EMAIL_LABEL')?></span>
					<a id="crm-recur-email-change">
					<span id="crm-recur-client-email-value" class="crm-recur-options-client-email-list">
						<?=$arResult['EMAIL_LIST'][0]['text']?>
					</span>
						<input type="hidden"
							   id="crm-recur-client-email-input"
							   name="RECUR_PARAM[RECURRING_EMAIL_ID]"
							   value="<?=(int)$data['RECURRING_EMAIL_ID'] > 0 ? (int)$data['RECURRING_EMAIL_ID'] : $arResult['EMAIL_LIST'][0]['value']?>">
						<span class="crm-client-selector-arrow"></span>
					</a>
					<div class="crm-recur-template-field">
						<span><?=Loc::getMessage('CRM_RECURRING_EMAIL_TEMPLATE')?></span>:
						<?
							if ((int)($data['EMAIL_TEMPLATE_ID']) > 0)
							{
								$selectedTemplate = $data['EMAIL_TEMPLATE_ID'];
							}
							elseif ((int)($arResult['EMAIL_TEMPLATE_LAST']) > 0)
							{
								$selectedTemplate = $data['RECURRING_EMAIL_SEND'] = $arResult['EMAIL_TEMPLATE_LAST'];
							}
							else
							{
								$selectedTemplate = key($arResult['EMAIL_TEMPLATES']);
							}
						?>
						<label class="crm-recur-options-inp-container <?=empty($arResult['EMAIL_TEMPLATES']) ? "disabled" : ""?>">
							<select id="email_template"
									name="RECUR_PARAM[EMAIL_TEMPLATE_ID]"
									class="crm-recur-options-inp"
								<?=empty($arResult['EMAIL_TEMPLATES']) ? "disabled" : ""?>
							>
								<?
									foreach ($arResult['EMAIL_TEMPLATES'] as $id => $title)
									{
										?>
										<option value="<?=$id?>" <?if ($selectedTemplate == $id ):?>selected<?endif?>><?=htmlspecialcharsbx($title)?></option>
										<?
									}
								?>
							</select>
						</label>
						<p class="crm-recur-options-client-email-list" id="crm-recur-create-mail-template-link"><?=Loc::getMessage('CRM_RECURRING_CREATE_NEW')?></p>
					</div>
				</div>
				<?
			}
			if (LANGUAGE_ID == 'ru')
			{
				?>
				<div class="crm-recur-options-field-fn crm-recur-options-field-ok">
					<div class="crm-recur-options-hint-wrapper">
						<span data-bx-id="replication-hint" id="hint">
							<?=$arResult['HINT']?>
						</span>
						<span id="next-data-hint"><?=$arResult['NEXT_EXECUTION_HINT']?></span>
					</div>
				</div>
				<?
			}
			else
			{
				?>
				<div id="next-data-hint" class="crm-recur-options-field-fn crm-recur-options-field-ok">
				</div>
				<?
			}
			?>
		</div>
		<?
		if ((int)($arParams['ID']) <= 0 || ((int)($arParams['ID']) && $arParams['IS_RECURRING'] !== 'Y'))
		{
			?>
			<div class="crm-recur-options-field-fn crm-recur-options-field-norm">
				<?=Loc::getMessage(
					$arResult['ENTITY_TYPE_ID'] == \CCrmOwnerType::Deal
						? 'CRM_RECURRING_DEAL_TEMPLATE_WILL_BE_CREATED'
						: 'CRM_RECURRING_TEMPLATE_WILL_BE_CREATED'
				);?>
			</div>
			<?
		}
		?>
	</div>
</div>

<?
$jsData = array(
	"EMAILS" => $arResult['EMAIL_LIST'],
	"CONTEXT" => $data['CONTEXT'],
	"ALLOW_SEND_BILL" => $arResult['ALLOW_SEND_BILL'],
	"TEMPLATE_URL" => $arResult['PATH_TO_EMAIL_TEMPLATE_ADD'],
	"AJAX_URL" => $arResult['AJAX_URL'],
	"LAST_EXECUTION" => $data['LAST_EXECUTION'],
	"ENTITY_TYPE_NAME" => $arParams['ENTITY_TYPE'],
	"ENTITY_TYPE_ID" =>  $arResult["ENTITY_TYPE_ID"]
);
?>

<script>
	$recurringScripts = new BX.Crm.Component.FormRecurring();
	$recurringScripts.construct(
		<?=CUtil::PhpToJSObject($jsData)?>
	);
</script>