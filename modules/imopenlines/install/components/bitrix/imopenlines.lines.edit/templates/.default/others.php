<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
?>
<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-title imopenlines-form-settings-title-other">
		<?=Loc::getMessage('IMOL_CONFIG_CHANGE_LINE_NAME')?>
	</div>
	<div class="imopenlines-control-container">
		<div class="imopenlines-control-subtitle">
			<?=Loc::getMessage('IMOL_CONFIG_EDIT_NAME')?>
		</div>
		<div class="imopenlines-control-inner">
			<input name="CONFIG[LINE_NAME]"
				   class="imopenlines-control-input"
				   value="<?=htmlspecialcharsbx($arResult['CONFIG']['LINE_NAME'])?>"
				   type="text">
		</div>
	</div>
</div>
<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_history_checkbox"
					   name="CONFIG[RECORDING]"
					   checked="checked"
					   disabled="disabled"
					   value="Y"
					   class="imopenlines-control-checkbox">
				<?=Loc::getMessage('IMOL_CONFIG_RECORDING')?>
				<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_RECORDING_DESC'))?>"></span>
			</label>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage("IMOL_CONFIG_EDIT_LANG_NEW")?>
				<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_LANG_EMAIL_TIP_NEW"))?>"></span>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-inner">
					<select name="CONFIG[LANGUAGE_ID]" id="imol_lang_select" class="imopenlines-control-input">
						<?
						foreach ($arResult['LANGUAGE_LIST'] as $lang => $langText)
						{
							?>
							<option value="<?=$lang?>" <?if($arResult["CONFIG"]["LANGUAGE_ID"] == $lang) { ?>selected<? }?>>
								<?=is_array($langText) ? $langText['NAME'] : $langText?>
							</option>
							<?
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>
