<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Imopenlines\Limit;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>

<div class="imopenlines-form-settings-section">
	<?if(!empty($arResult['ERROR'])):?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message">
			<?foreach ($arResult['ERROR'] as $error):?>
				<?= $error ?><br>
			<?endforeach;?>
			</span>
		</div>
	<?endif;?>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-form-settings-title imopenlines-form-settings-title-other">
			<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME')?>
		</div>
		<div class="imopenlines-form-settings-block">
			<div class="imopenlines-form-settings-inner">
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<input type="checkbox"
							   name="CONFIG[WORKTIME_ENABLE]"
							   value="Y"
							   id="imol_worktime_checkbox"
							   class="imopenlines-control-checkbox"
							   data-limit="<?=!Limit::canWorkHourSettings()?'Y':'N';?>"
							<? if ($arResult['CONFIG']['WORKTIME_ENABLE'] === 'Y') { ?>checked<? } ?>>
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_ENABLE')?>
						<?if(!Limit::canWorkHourSettings()):?>
							<span class="tariff-lock"></span>
							<script type="text/javascript">
								BX.bind(BX('imol_worktime_checkbox'), 'change', function(e){
									BX('imol_worktime_checkbox').checked = false;
									window.BX.imolTrialHandler.openPopupWorkTime();
								});
							</script>
						<?elseif(Limit::isDemoLicense()):?>
							<span class="tariff-lock" onclick="window.BX.imolTrialHandler.openPopupWorkTime(); return false;"></span>
						<?endif;?>
					</label>
				</div>
			</div>
			<div id="imol_worktime_block" <? if ($arResult['CONFIG']['WORKTIME_ENABLE'] !== 'Y') { ?>class="invisible" <? } ?>>
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_TIMEZONE')?>
					</div>
					<div class="imopenlines-control-inner">
						<select name="CONFIG[WORKTIME_TIMEZONE]" class="imopenlines-control-input">
							<?
							if (is_array($arResult['TIME_ZONE_LIST']) && !empty($arResult['TIME_ZONE_LIST']))
							{
								foreach($arResult['TIME_ZONE_LIST'] as $tz => $tz_name)
								{
									?>
									<option value="<?=htmlspecialcharsbx($tz)?>"<?=($arResult['CONFIG']['WORKTIME_TIMEZONE'] == $tz? ' selected="selected"' : '')?>>
										<?=htmlspecialcharsbx($tz_name)?>
									</option>
									<?
								}
							}
							?>
						</select>
					</div>
				</div>
				<?
				if (!empty($arResult['WORKTIME_LIST_FROM']) && !empty($arResult['WORKTIME_LIST_TO']))
				{
					?>
					<div class="imopenlines-control-container imopenlines-control-select">
						<div class="imopenlines-control-subtitle">
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_TIME')?>
						</div>
						<div class="imopenlines-control-inner">
							<select name="CONFIG[WORKTIME_FROM]" class="imopenlines-control-input">
								<?
								foreach($arResult['WORKTIME_LIST_FROM'] as $key => $val)
								{
									?>
									<option value="<?= $key?>" <?if($arResult['CONFIG']['WORKTIME_FROM'] == $key) echo ' selected="selected" ';?>>
										<?= $val?>
									</option>
									<?
								}
								?>
							</select>
							<select name="CONFIG[WORKTIME_TO]" class="imopenlines-control-input">
								<?
								foreach($arResult['WORKTIME_LIST_TO'] as $key => $val)
								{
									?>
									<option value="<?= $key?>" <?if($arResult['CONFIG']['WORKTIME_TO'] == $key) echo ' selected="selected" ';?>>
										<?= $val?>
									</option>
									<?
								}
								?>
							</select>
						</div>
					</div>
					<?
				}
				?>
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF_TIP'))?>"></span>
					</div>
					<div class="imopenlines-control-inner">
						<select size="7" multiple="true" name="CONFIG[WORKTIME_DAYOFF][]" class="imopenlines-control-input  imopenlines-control-select-multiple">
							<?
							foreach($arResult['WEEK_DAYS'] as $day)
							{
								?>
								<option value="<?=$day?>" <?=(is_array($arResult['CONFIG']['WORKTIME_DAYOFF']) && in_array($day, $arResult['CONFIG']['WORKTIME_DAYOFF']) ? ' selected="selected"' : '')?>>
									<?= Loc::getMessage('IMOL_CONFIG_WEEK_'.$day)?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_HOLIDAYS')?>
					</div>
					<div class="imopenlines-control-inner">
						<input type="text"
							   name="CONFIG[WORKTIME_HOLIDAYS]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($arResult['CONFIG']['WORKTIME_HOLIDAYS'])?>">
					</div>
					<div class="imopenlines-control-subtitle imopenlines-control-subtitle-decs">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_HOLIDAYS_EXAMPLE')?>
					</div>
				</div>
			</div>
			<div id="imol_worktime_answer_block" <?php if ($arResult['CONFIG']['CHECK_AVAILABLE'] !== 'Y' && $arResult['CONFIG']['WORKTIME_ENABLE'] !== 'Y') { ?>class="invisible" <?php } ?>>
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF_RULE_NEW')?>
					</div>
					<div class="imopenlines-control-inner">
						<select name="CONFIG[WORKTIME_DAYOFF_RULE]" id="imol_worktime_dayoff_rule" class="imopenlines-control-input">
							<?
							foreach($arResult['SELECT_RULES'] as $value => $name)
							{
								?>
								<option value="<?=$value?>" <?if($arResult['CONFIG']['WORKTIME_DAYOFF_RULE'] == $value) { ?>selected<? }?> <?if($value === 'disabled') { ?>disabled<? }?>>
									<?=$name?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="imopenlines-control-container imopenlines-control-select invisible" id="imol_worktime_dayoff_rule_form">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF_FORM_ID')?>
					</div>
					<div class="imopenlines-control-inner">
						<select class="imopenlines-control-input" name="CONFIG[WORKTIME_DAYOFF_FORM_ID]"></select>
					</div>
					<div class="imopenlines-control-subtitle imopenlines-control-subtitle-decs">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF_FORM_ID_NOTICE')?>
					</div>
				</div>
				<div class="imopenlines-control-container imopenlines-control-block" id="imol_worktime_dayoff_rule_text">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WORKTIME_DAYOFF_TEXT_NEW')?>
					</div>
					<div class="imopenlines-control-inner">
						<textarea class="imopenlines-control-input imopenlines-control-textarea" name="CONFIG[WORKTIME_DAYOFF_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['WORKTIME_DAYOFF_TEXT'])?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
