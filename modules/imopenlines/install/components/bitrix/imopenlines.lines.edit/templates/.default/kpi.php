<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Imopenlines\Limit;

\Bitrix\Main\UI\Extension::load("ui.forms");

$arResult["KPI_MENU"]["kpiFirstAnswer"]["element"] = "imol_kpi_first_answer_time_menu";
$arResult["KPI_MENU"]["kpiFirstAnswer"]["bindElement"] = "imol_kpi_first_answer_time_menu";
$arResult["KPI_MENU"]["kpiFirstAnswer"]["inputElement"] = "imol_kpi_first_answer_time";
$arResult["KPI_MENU"]["kpiFurtherAnswer"]["element"] = "imol_kpi_further_answer_time_menu";
$arResult["KPI_MENU"]["kpiFurtherAnswer"]["bindElement"] = "imol_kpi_further_answer_time_menu";
$arResult["KPI_MENU"]["kpiFurtherAnswer"]["inputElement"] = "imol_kpi_further_answer_time";
?>
<script type="text/javascript">
	BX.ready(function(){
		var params = <?=CUtil::PhpToJSObject($arResult["KPI_MENU"])?>;
		BX.OpenLinesConfigEdit.loadKpiTimeMenus(params);
		BX.message({
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SET: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SET')?>',
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS')?>',
			IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES: '<?=GetMessageJS('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES')?>',
		});
	});
</script>
<div class="imopenlines-form-settings-section">
	<div class="imopenlines-control-container ui-form-border-bottom">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_FIRST_ANSWER_TIME')?>
			</div>
			<div class="imopenlines-control-inner">
				<div class="imopenlines-control-input imopenlines-control-input-kpi" id="imol_kpi_first_answer_time_menu">
					<?=$arResult["KPI_MENU"]["kpiFirstAnswer"]["currentTitle"]?>
				</div>
				<input type="hidden" name="CONFIG[KPI_FIRST_ANSWER_TIME]" id="imol_kpi_first_answer_time" value="<?=$arResult["CONFIG"]["KPI_FIRST_ANSWER_TIME"]?>">
			</div>
		</div>
		<div id="imol_kpi_first_answer_full_block" <? if ($arResult["CONFIG"]["KPI_FIRST_ANSWER_TIME"] == 0) { ?>class="invisible"<? } ?>>
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_kpi_first_answer_alert"
						   type="checkbox"
						   class="imopenlines-control-checkbox"
						   name="CONFIG[KPI_FIRST_ANSWER_ALERT]"
						   <?if($arResult['CONFIG']['KPI_FIRST_ANSWER_ALERT'] == 'Y') {?>checked="checked"<?}?>
						   value="Y">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT')?>
				</label>
			</div>
			<div id="imol_kpi_first_answer_inner_block" <?if($arResult['CONFIG']['KPI_FIRST_ANSWER_ALERT'] != 'Y') {?>class="invisible"<? } ?>>
				<div class="imopenlines-form-settings-inner">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_LIST")?>
						<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_KPI_FIRST_ANSWER_DESC"))?>"></span>
					</div>
					<?$APPLICATION->IncludeComponent(
						'bitrix:main.user.selector',
						'',
						[
							'INPUT_NAME' => 'CONFIG[KPI_FIRST_ANSWER_LIST][]',
							'LIST' => $arResult['CONFIG']['KPI_FIRST_ANSWER_LIST'],
							'SELECTOR_OPTIONS' => ['enableDepartments' => 'Y', 'useSearch' => 'Y', 'multiple' => 'Y']
						]
					);?>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_TEXT')?>
						<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_DESC"))?>"></span>
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
					<?=$arResult["KPI_MENU"]["kpiFurtherAnswer"]["currentTitle"]?>
				</div>
				<input type="hidden" name="CONFIG[KPI_FURTHER_ANSWER_TIME]" id="imol_kpi_further_answer_time" value="<?=$arResult["CONFIG"]["KPI_FURTHER_ANSWER_TIME"]?>">
			</div>
		</div>
		<div id="imol_kpi_further_answer_full_block" <? if ($arResult["CONFIG"]["KPI_FURTHER_ANSWER_TIME"] == 0) { ?>class="invisible"<? } ?>>
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_kpi_further_answer_alert"
						   type="checkbox"
						   class="imopenlines-control-checkbox"
						   name="CONFIG[KPI_FURTHER_ANSWER_ALERT]"
						   <?if($arResult['CONFIG']['KPI_FURTHER_ANSWER_ALERT'] == 'Y') {?>checked="checked"<?}?>
						   value="Y">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT')?>
				</label>
			</div>
			<div id="imol_kpi_further_answer_inner_block" <?if($arResult['CONFIG']['KPI_FURTHER_ANSWER_ALERT'] != 'Y') {?>class="invisible"<? } ?>>
				<div class="imopenlines-form-settings-inner">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_LIST")?>
						<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_KPI_FURTHER_ANSWER_DESC"))?>"></span>
					</div>
					<?$APPLICATION->IncludeComponent(
						'bitrix:main.user.selector',
						'',
						[
							'INPUT_NAME' => 'CONFIG[KPI_FURTHER_ANSWER_LIST][]',
							'LIST' => $arResult['CONFIG']['KPI_FURTHER_ANSWER_LIST'],
							'SELECTOR_OPTIONS' => ['enableDepartments' => 'Y', 'useSearch' => 'Y', 'multiple' => 'Y']
						]
					);?>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_TEXT')?>
						<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_KPI_ANSWER_ALERT_DESC"))?>"></span>
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

