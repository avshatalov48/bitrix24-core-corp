<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc; ?>

<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-inner">
		<div class="imopenlines-control-checkbox-container imopenlines-agreement-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   class="imopenlines-control-checkbox"
					   id="imol_agreement_message"
					   name="CONFIG[AGREEMENT_MESSAGE]"
					   value="Y"
					   <? if ($arResult['CONFIG']['AGREEMENT_MESSAGE'] == "Y") { ?>checked<? } ?>>
				<?=Loc::getMessage("IMOL_CONFIG_EDIT_AGREEMENT_MESSAGE")?>
			</label>
		</div>
	</div>
	<div id="imol_agreement_message_block" <? if ($arResult['CONFIG']['AGREEMENT_MESSAGE'] != "Y") { ?>class="invisible" <? } ?>>
		<div class="imopenlines-control-container">
			<?$APPLICATION->IncludeComponent(
				"bitrix:intranet.userconsent.selector",
				"",
				array(
					'ID' => $arResult['CONFIG']['AGREEMENT_ID'],
					'INPUT_NAME' => 'CONFIG[AGREEMENT_ID]'
				)
			);?>
		</div>
	</div>
</div>
