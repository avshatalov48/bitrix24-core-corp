<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.notification',
]);
?>
<script>
	BX.ready(function(){
		BX.message({
			'IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_BUTTON': '<?=GetMessageJS('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_BUTTON')?>',
			'IMOL_CONFIG_EDIT_DELETE_NOTIFICATION_SUCCESS': '<?=GetMessageJS('IMOL_CONFIG_EDIT_DELETE_NOTIFICATION_SUCCESS')?>',
			'IMOL_CONFIG_EDIT_DELETE_FAIL': '<?=GetMessageJS('IMOL_CONFIG_EDIT_DELETE_FAIL')?>',
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
					   id="imol_active_checkbox"
					   name="CONFIG[ACTIVE]"
					   <?if($arResult['CONFIG']['ACTIVE'] === 'Y'):?>checked="checked"<?endif;?>
					   value="Y"
					   class="imopenlines-control-checkbox">
				<?=Loc::getMessage('IMOL_CONFIG_ACTIVE')?>
			</label>
		</div>
	</div>
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
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_RECORDING_DESC'))?>"></span>
			</label>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_history_checkbox"
					   name="CONFIG[CONFIRM_CLOSE]"
					   <?php if($arResult['CONFIG']['CONFIRM_CLOSE'] === 'Y'): ?>checked="checked"<?php endif; ?>
					   value="Y"
					   class="imopenlines-control-checkbox">
				<?= Loc::getMessage('IMOL_CONFIG_CONFIRM_CLOSE') ?>
				<span data-hint-html data-hint="<?= htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_CONFIRM_CLOSE_DESC')) ?>"></span>
			</label>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_LANG_NEW')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_LANG_EMAIL_TIP_NEW'))?>"></span>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-inner">
					<select name="CONFIG[LANGUAGE_ID]" id="imol_lang_select" class="imopenlines-control-input">
						<?
						foreach ($arResult['LANGUAGE_LIST'] as $lang => $langText)
						{
							?>
							<option value="<?=$lang?>" <?if($arResult['CONFIG']['LANGUAGE_ID'] === $lang) { ?>selected<? }?>>
								<?=is_array($langText) ? $langText['NAME'] : $langText?>
							</option>
							<?
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-delete">
			<h5 class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE')?></h5>
			<button class="ui-btn ui-btn-light-border" id="imol_delete_openline" type="button"><?=Loc::getMessage('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_BUTTON')?></button>
		</div>
	</div>
</div>
<div class="imopenlines-control-alert-popup" id="imol_delete_openline_popup" style="display: none">
	<div class="imopenlines-control-alert-popup-inner">
		<h6 class="imopenlines-control-alert-popup-title"><?=Loc::getMessage('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_POPUP_TITLE')?></h6>
		<p class="imopenlines-control-alert-popup-text" id="imol-alert-popup-text"><?=Loc::getMessage('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_POPUP_MESSAGE')?></p>
	</div>
</div>