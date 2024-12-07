<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 */

use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.forms',
]);


$arResult['KPI_MENU']['kpiFirstAnswer']['element'] = 'imol_kpi_first_answer_time_menu';
$arResult['KPI_MENU']['kpiFirstAnswer']['bindElement'] = 'imol_kpi_first_answer_time_menu';
$arResult['KPI_MENU']['kpiFirstAnswer']['inputElement'] = 'imol_kpi_first_answer_time';
$arResult['KPI_MENU']['kpiFurtherAnswer']['element'] = 'imol_kpi_further_answer_time_menu';
$arResult['KPI_MENU']['kpiFurtherAnswer']['bindElement'] = 'imol_kpi_further_answer_time_menu';
$arResult['KPI_MENU']['kpiFurtherAnswer']['inputElement'] = 'imol_kpi_further_answer_time';
$arResult['kpiSelector'] = [
	'firstAnswer' => [
		'id' => 'kpi-first-answer-selector',
		'inputName' => 'CONFIG[KPI_FIRST_ANSWER_LIST][]',
		'inputId' => 'kpi-first-answer-input',
		'list' => $arResult['CONFIG']['KPI_FIRST_ANSWER_LIST'],
		'readOnly' => !$arResult['CAN_EDIT']
	],
	'furtherAnswer' => [
		'id' => 'kpi-first-further-selector',
		'inputName' => 'CONFIG[KPI_FURTHER_ANSWER_LIST][]',
		'inputId' => 'kpi-first-further-input',
		'list' => $arResult['CONFIG']['KPI_FURTHER_ANSWER_LIST'],
		'readOnly' => !$arResult['CAN_EDIT']
	],
];
?>
<script>
	BX.ready(function(){
		BX.OpenLinesConfigEdit.loadKpiTimeMenus(<?=CUtil::PhpToJSObject($arResult['KPI_MENU'])?>);
		BX.OpenLinesConfigEdit.loadKpiEntitySelector(<?=CUtil::PhpToJSObject($arResult['kpiSelector'])?>);
		BX.message({
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SET: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SET')?>',
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS')?>',
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES')?>',
		});
	});
</script>
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
	<div class="imopenlines-control-container ui-form-border-bottom">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_FIRST_ANSWER_TIME')?>
			</div>
			<div class="imopenlines-control-inner">
				<div class="imopenlines-control-input imopenlines-control-input-kpi" id="imol_kpi_first_answer_time_menu">
					<?=$arResult['KPI_MENU']['kpiFirstAnswer']['currentTitle']?>
				</div>
				<input type="hidden" name="CONFIG[KPI_FIRST_ANSWER_TIME]" id="imol_kpi_first_answer_time" value="<?=$arResult['CONFIG']['KPI_FIRST_ANSWER_TIME']?>">
			</div>
		</div>
		<div id="imol_kpi_first_answer_full_block" <?if((int)$arResult['CONFIG']['KPI_FIRST_ANSWER_TIME'] === 0) { ?>class="invisible"<? } ?>>
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_kpi_first_answer_alert"
						   type="checkbox"
						   class="imopenlines-control-checkbox"
						   name="CONFIG[KPI_FIRST_ANSWER_ALERT]"
						   <?if($arResult['CONFIG']['KPI_FIRST_ANSWER_ALERT'] === 'Y') {?>checked="checked"<?}?>
						   value="Y">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT')?>
				</label>
			</div>
			<div id="imol_kpi_first_answer_inner_block" <?if($arResult['CONFIG']['KPI_FIRST_ANSWER_ALERT'] !== 'Y') {?>class="invisible"<? } ?>>
				<div class="imopenlines-form-settings-inner">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_LIST')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_KPI_FIRST_ANSWER_DESC'))?>"></span>
					</div>
					<span id="<?=$arResult['kpiSelector']['firstAnswer']['id']?>"></span>
					<span id="<?=$arResult['kpiSelector']['firstAnswer']['inputId']?>"></span>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_TEXT')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_DESC'))?>"></span>
					</div>
					<div class="imopenlines-control-inner">
						<textarea type="text"
								  class="imopenlines-control-input imopenlines-control-input-textarea-kpi"
								  name="CONFIG[KPI_FIRST_ANSWER_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['KPI_FIRST_ANSWER_TEXT'])?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="imopenlines-control-container ui-form-border-bottom">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_FURTHER_ANSWER_TIME')?>
			</div>
			<div class="imopenlines-control-inner">
				<div class="imopenlines-control-input imopenlines-control-input-kpi" id="imol_kpi_further_answer_time_menu">
					<?=$arResult['KPI_MENU']['kpiFurtherAnswer']['currentTitle']?>
				</div>
				<input type="hidden" name="CONFIG[KPI_FURTHER_ANSWER_TIME]" id="imol_kpi_further_answer_time" value="<?=$arResult['CONFIG']['KPI_FURTHER_ANSWER_TIME']?>">
			</div>
		</div>
		<div id="imol_kpi_further_answer_full_block" <?if((int)$arResult['CONFIG']['KPI_FURTHER_ANSWER_TIME'] === 0) { ?>class="invisible"<? } ?>>
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_kpi_further_answer_alert"
						   type="checkbox"
						   class="imopenlines-control-checkbox"
						   name="CONFIG[KPI_FURTHER_ANSWER_ALERT]"
						   <?if($arResult['CONFIG']['KPI_FURTHER_ANSWER_ALERT'] === 'Y') {?>checked="checked"<?}?>
						   value="Y">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT')?>
				</label>
			</div>
			<div id="imol_kpi_further_answer_inner_block" <?if($arResult['CONFIG']['KPI_FURTHER_ANSWER_ALERT'] !== 'Y') {?>class="invisible"<? } ?>>
				<div class="imopenlines-form-settings-inner">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_LIST')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_KPI_FURTHER_ANSWER_DESC'))?>"></span>
					</div>
					<span id="<?=$arResult['kpiSelector']['furtherAnswer']['id']?>"></span>
					<span id="<?=$arResult['kpiSelector']['furtherAnswer']['inputId']?>"></span>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_TEXT')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_DESC'))?>"></span>
					</div>
					<div class="imopenlines-control-inner">
					<textarea type="text"
							  class="imopenlines-control-input imopenlines-control-input-textarea-kpi"
							  name="CONFIG[KPI_FURTHER_ANSWER_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['KPI_FURTHER_ANSWER_TEXT'])?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?/*<div class="imopenlines-control-container">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   class="imopenlines-control-checkbox"
					   name="CONFIG[KPI_CHECK_OPERATOR_ACTIVITY]"
					   <?if($arResult['CONFIG']['KPI_CHECK_OPERATOR_ACTIVITY'] == 'Y') {?>checked="checked"<?}?>
					   value="Y">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_CHECK_OPERATOR_ACTIVITY')?>
			</label>
		</div>
	</div>*/?>
</div>

