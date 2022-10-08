<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>

<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-block">
		<!--	Welcome block	-->
		<div class="imopenlines-form-settings-inner ui-form-border-bottom">
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input type="checkbox"
						   class="imopenlines-control-checkbox"
						   id="imol_form_welcome"
						   name="CONFIG[USE_WELCOME_FORM]"
						   value="Y"
						   <? if ($arResult['CONFIG']['USE_WELCOME_FORM'] == 'Y') { ?>checked<? } ?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_FORM_WELCOME_CHECKBOX')?>
				</label>
			</div>
		</div>
		<div id="imol_form_welcome_block" <? if ($arResult['CONFIG']['USE_WELCOME_FORM'] !== 'Y') { ?>class="invisible" <? } ?>>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_SELECT")?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_FORM_ID]" class="imopenlines-control-input">
						<?
						if (is_array($arResult["CRM_FORMS_LIST"]) && !empty($arResult["CRM_FORMS_LIST"]))
						{
							foreach($arResult["CRM_FORMS_LIST"] as $form)
							{
								?>
								<option value="<?=$form['ID']?>"<?=($arResult["CONFIG"]["WELCOME_FORM_ID"] == $form['ID']? ' selected="selected"' : '')?>>
									<?=htmlspecialcharsbx($form['NAME'])?>
								</option>
								<?
							}
						}
						?>
					</select>
<!--					<span>-->
<!--						<a href="javascript:;" id="imol_form_welcome_new_form">--><?//=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_SELECT")?><!--</a>-->
<!--					</span>-->
				</div>
			</div>
			<div class="imopenlines-control-container ui-form-border-bottom">
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT")?>
					</div>
					<div class="imopenlines-control-container">
						<div class="imopenlines-control-inner">
							<select name="CONFIG[WELCOME_FORM_DELAY]" id="imol_form_welcome_delay" class="imopenlines-control-input">
								<option value="N"<?=($arResult["CONFIG"]["WELCOME_FORM_DELAY"] === 'N'? ' selected="selected"' : '')?>>
									<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT_N")?>
								</option>
								<option value="Y"<?=($arResult["CONFIG"]["WELCOME_FORM_DELAY"] === 'Y'? ' selected="selected"' : '')?>>
									<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT_Y")?>
								</option>
							</select>
						</div>
						<div id="imol_form_welcome_delay_description">
							<div id="imol_form_no_delay_description" class="imopenlines-control-subtitle <? if ($arResult['CONFIG']['WELCOME_FORM_DELAY'] === 'Y') { ?>invisible<? } ?>">
								<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_DESCRIPTION_N")?>
							</div>
							<div id="imol_form_delay_description" class="imopenlines-control-subtitle <? if ($arResult['CONFIG']['WELCOME_FORM_DELAY'] === 'N') { ?>invisible<? } ?>">
								<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_DESCRIPTION_Y")?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>