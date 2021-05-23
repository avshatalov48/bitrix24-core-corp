<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult Component result. */
/** @var array $arParams Component parameters. */
/** @var CBitrixComponentTemplate $this */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Web\Json;

Extension::load(['core', 'ui.forms', 'ui.hint', 'ui.alerts', 'color_picker', 'ui.switcher']);

Loc::loadMessages(__FILE__);
$containerId = 'form-editor-v2';
?>
<?if($arParams['IS_SAVED']):?>
<script>
	top.BX.onCustomEvent('crm-webform-design-save', [<?=Json::encode(
		$arResult['DESIGN']
	)?>]);
</script>
<?endif;?>
<div id="<?=$containerId?>" class="crm-webform-editor-wrapper">
	<?
	if (!$arResult['FORM']['ID']):
		echo "</div>";
		return;
	endif;
	?>

	<div class="ui-alert ui-alert-icon-warning">
		<span class="ui-alert-message">
			<strong><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_ATTENTION')?></strong>
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_ATTENTION_TEXT')?>
		</span>
	</div>

	<form method="post" action="<?=htmlspecialcharsbx($arResult['PATH_TO_WEB_FORM_LIST'])?>">
	<input type="hidden" name="ID" value="<?=$arResult['FORM']['ID']?>">
	<?=bitrix_sessid_post();?>

	<div class="crm-webform-editor-content">
		<div class="crm-webform-editor-form">
			<div class="crm-webform-editor-form-inner">
				<?=Crm\UI\Webpack\Form::instance($arResult['FORM']['ID'])
					->setCacheTtl(1)
					->getEmbeddedScript()
				?>
			</div>
		</div>
		<div class="crm-webform-editor-setup">
			<div class="crm-webform-editor-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_THEME')?></div>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="design-virtual-theme" class="ui-ctl-element">
						<?foreach ($arResult['THEME_NAMES'] as $code => $name):?>
							<option
								value="<?=htmlspecialcharsbx($code)?>"
								<?=(mb_strpos($arResult['DESIGN']['theme'], $code) === 0 ? 'selected' : '')?>
							>
								<?=htmlspecialcharsbx($name)?>
							</option>
						<?endforeach;?>
					</select>
				</div>
				<input type="hidden"
					name="DESIGN[theme]"
					value="<?=htmlspecialcharsbx($arResult['DESIGN']['theme'])?>"
					>
			</div>

			<div class="crm-webform-editor-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_MODE')?></div>
				<?foreach ($arResult['MODES'] as $code => $name):?>
					<label class="ui-ctl ui-ctl-radio ui-ctl-inline">
						<input type="radio" name="design-virtual-mode" class="ui-ctl-element"
							value="<?=htmlspecialcharsbx($code)?>"
							<?=($arResult['DESIGN']['dark'] === $code ? 'checked' : '')?>
						>
						<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($name)?></div>
					</label>
				<?endforeach;?>
				<input type="hidden"
					name="DESIGN[dark]"
					value="<?=htmlspecialcharsbx($arResult['DESIGN']['dark'])?>"
				>
			</div>

			<div class="crm-webform-editor-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_STYLE')?></div>
				<label class="ui-ctl ui-ctl-radio ui-ctl-inline">
					<input type="radio" name="DESIGN[style]" class="ui-ctl-element"
						value=""
						<?=(!$arResult['DESIGN']['style'] ? 'checked' : '')?>
					>
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_STANDARD')?></div>
				</label>
				<?foreach ($arResult['STYLES'] as $code => $name):?>
					<label class="ui-ctl ui-ctl-radio ui-ctl-inline">
						<input type="radio" name="DESIGN[style]" class="ui-ctl-element"
							value="<?=htmlspecialcharsbx($code)?>"
							<?=($code === $arResult['DESIGN']['style'] ? 'checked' : '')?>
						>
						<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($name)?></div>
					</label>
				<?endforeach;?>
			</div>

			<div class="crm-webform-editor-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_BRIGHT')?></div>
				<div class="crm-webform-editor-field-content">
					<span class="crm-webform-editor-field-color-item">
						<input type="hidden" name="DESIGN[color][primary]"
							data-color="primary"
							value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['primary'])?>"
						>
						<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
					</span>
				</div>
			</div>

			<div class="crm-webform-editor-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_BRIGHT_TEXT')?></div>
				<div class="crm-webform-editor-field-content">
					<span class="crm-webform-editor-field-color-item">
						<input type="hidden" name="DESIGN[color][primaryText]"
							data-color="primaryText"
							value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['primaryText'])?>"
						>
						<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
					</span>
				</div>
			</div>

			<div class="crm-webform-editor-field">
				<div class="crm-webform-editor-field-content">
					<a id="design-more-fields-btn" class="crm-webform-editor-btn-more-fields">
						<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_MORE_FIELDS')?>
					</a>
				</div>
			</div>

			<div id="design-more-fields" style="display: none;">

				<div class="ui-ctl-label-text">
					<label class="ui-ctl ui-ctl-checkbox ui-ctl-inline">
						<input type="checkbox" class="ui-ctl-element"
							name="DESIGN[shadow]"
							value="Y"
							<?=($arResult['DESIGN']['shadow'] === 'Y' ? 'checked' : '')?>
						>
						<span class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SHADOW')?></span>
					</label>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_FORM')?></div>
					<span class="crm-webform-editor-field-color-item">
						<input type="hidden" name="DESIGN[color][background]"
							data-color="background"
							value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['background'])?>"
						>
						<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w50">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" data-color-opacity="" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_OPACITY')?>"></select>
						</div>
						<span class="ui-hint" data-hint="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_FORM_TRANSPARENT_HINT')?>"></span>
					</span>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_FORM_TEXT')?></div>
					<span class="crm-webform-editor-field-color-item">
						<input type="hidden" name="DESIGN[color][text]"
							data-color="text"
							value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['text'])?>"
						>
						<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w50">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" data-color-opacity="" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_OPACITY')?>"></select>
						</div>
					</span>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_FIELD')?></div>
					<div class="crm-webform-editor-field-content">
						<span class="crm-webform-editor-field-color-item">
							<input type="hidden" name="DESIGN[color][fieldBackground]"
								data-color="fieldBackground"
								value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['fieldBackground'])?>"
							>
							<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w50">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select data-color-opacity="" class="ui-ctl-element" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_OPACITY')?>"></select>
							</div>
							<span class="ui-hint" data-hint="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_FORM_TRANSPARENT_HINT')?>"></span>
						</span>
					</div>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_FIELD_FOCUS')?></div>
					<div class="crm-webform-editor-field-content">
						<span class="crm-webform-editor-field-color-item">
							<input type="hidden" name="DESIGN[color][fieldFocusBackground]"
								data-color="fieldFocusBackground"
								value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['fieldFocusBackground'])?>"
							>
							<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w50">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" data-color-opacity="" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_OPACITY')?>"></select>
							</div>
						</span>
					</div>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_COLOR_FIELD_BORDER')?></div>
					<div class="crm-webform-editor-field-content">
						<span class="crm-webform-editor-field-color-item">
							<input type="hidden" name="DESIGN[color][fieldBorder]"
								data-color="fieldBorder"
								value="<?=htmlspecialcharsbx($arResult['DESIGN']['color']['fieldBorder'])?>"
							>
							<span data-color-circle="" class="crm-webform-editor-field-color" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_COLOR')?>"></span>
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w50">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" data-color-opacity="" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_SETUP_OPACITY')?>"></select>
							</div>
						</span>
					</div>
				</div>

				<div class="crm-webform-editor-field">
					<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_FONT')?></div>
					<div class="crm-webform-editor-field-content">
						<span class="crm-webform-editor-field-color-item">
							<div class="ui-ctl ui-ctl-textbox">
								<input type="text" name="DESIGN[font][uri]" class="ui-ctl-element"
									value="<?=htmlspecialcharsbx($arResult['DESIGN']['font']['uri'])?>"
									placeholder="https://fonts.google.com/..."
								>
							</div>
							<span class="ui-hint" data-hint="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_FONT_URI_HINT')?>"></span>
						</span>
					</div>
				</div>

				<div class="crm-webform-editor-field">
					<div class="crm-webform-editor-field-content">
						<span class="crm-webform-editor-field-color-item">
							<div class="ui-ctl ui-ctl-textbox">
								<input type="text" name="DESIGN[font][family]" class="ui-ctl-element"
									value="<?=htmlspecialcharsbx($arResult['DESIGN']['font']['family'])?>"
									placeholder="Open Sans"
								>
							</div>
							<span class="ui-hint" data-hint="<?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_FONT_FAMILY_HINT')?>"></span>
						</div>
					</div>

					<div class="crm-webform-editor-field">
						<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_BORDER')?></div>
						<label class="ui-ctl ui-ctl-checkbox ui-ctl-inline">
							<input type="checkbox" class="ui-ctl-element"
								name="DESIGN[border][left]"
								value="Y"
								<?=($arResult['DESIGN']['border']['left'] === 'Y' ? 'checked' : '')?>
							>
							<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_BORDER_LEFT')?></div>
						</label>
						<label class="ui-ctl ui-ctl-checkbox ui-ctl-inline">
							<input type="checkbox" class="ui-ctl-element"
								name="DESIGN[border][top]"
								value="Y"
								<?=($arResult['DESIGN']['border']['top'] === 'Y' ? 'checked' : '')?>
							>
							<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_BORDER_TOP')?></div>
						</label>
						<label class="ui-ctl ui-ctl-checkbox ui-ctl-inline">
							<input type="checkbox" class="ui-ctl-element"
								name="DESIGN[border][bottom]"
								value="Y"
								<?=($arResult['DESIGN']['border']['bottom'] === 'Y' ? 'checked' : '')?>
							>
							<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_BORDER_BOTTOM')?></div>
						</label>
						<label class="ui-ctl ui-ctl-checkbox ui-ctl-inline">
							<input type="checkbox" class="ui-ctl-element"
								name="DESIGN[border][right]"
								value="Y"
								<?=($arResult['DESIGN']['border']['right'] === 'Y' ? 'checked' : '')?>
							>
							<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_V2_BORDER_RIGHT')?></div>
						</label>
					</div>
				</div>

			</div>
		</div>
	</div>

	<?$APPLICATION->IncludeComponent("bitrix:ui.button.panel", "", [
		'BUTTONS' => $arResult['PERM_CAN_EDIT']
			?
			['save','cancel' => $arResult['PATH_TO_WEB_FORM_LIST']]
			:
			['close' => $arResult['PATH_TO_WEB_FORM_LIST']]
	]);?>

	</form>
	<script>
		BX.ready(function () {
			BX.Crm.WebForm.Design.init({
				containerId: '<?=$containerId?>',
				themes: <?=Main\Web\Json::encode($arResult['THEMES'])?>
			});
		})
	</script>
</div>
