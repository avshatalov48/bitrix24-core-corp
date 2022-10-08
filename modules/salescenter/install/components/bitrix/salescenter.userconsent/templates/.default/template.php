<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_USERCONSENT_TITLE'));

\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->addFeedbackButtonToToolbar();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.alerts',
	'ui.sidepanel-content',
]);
?>

<form method="get" action="" id="salescenter-userconsent-form">
	<div class="ui-slider-section">
		<div class="salescenter-userconsent-form-settings-inner">
			<div class="salescenter-userconsent-control-checkbox-container salescenter-userconsent-agreement-container">
				<label class="salescenter-userconsent-control-checkbox-label">
					<input type="checkbox"
						class="salescenter-userconsent-control-checkbox"
						name="USERCONSENT[ACTIVE]"
						id="salescenter_agreement_message"
						value="Y"
						<?=($arResult['ACTIVE'] == 'Y' ? 'checked' : '')?>
					>
					<?=Loc::getMessage("SALESCENTER_USERCONSENT_ACTIVE")?>
				</label>
			</div>
		</div>
		<div id="salescenter_agreement_message_block" class="<?=($arResult['ACTIVE'] != 'Y' ? 'invisible' : '')?>">
			<div class="salescenter-userconsent-control-container">
				<?php
				$APPLICATION->IncludeComponent(
					"bitrix:intranet.userconsent.selector",
					"",
					array(
						'ID' => $arResult['ID'],
						'INPUT_NAME' => 'USERCONSENT[AGREEMENT_ID]'
					)
				);?>
			</div>
			<div class="salescenter-userconsent-form-settings-inner">
				<div class="salescenter-userconsent-control-checkbox-container salescenter-userconsent-agreement-container">
					<label class="salescenter-userconsent-control-checkbox-label">
						<input type="checkbox"
							class="salescenter-userconsent-control-checkbox"
							name="USERCONSENT[CHECK]"
							value="Y"
							<?=($arResult['CHECK'] == 'Y' ? 'checked' : '')?>
						>
						<?=Loc::getMessage("SALESCENTER_USERCONSENT_CONFIG_EDIT_AGREEMENT_MESSAGE")?>
					</label>
				</div>
			</div>
		</div>

		<div id="salescenter-paysystem-buttons">
			<?php
			$APPLICATION->IncludeComponent(
				'bitrix:ui.button.panel',
				"",
				array(
					'BUTTONS' => ['save', 'cancel' => $arParams['SALESCENTER_DIR']],
					'ALIGN' => "center"
				),
				false
			);
			?>
		</div>
	</div>
</form>

<script>
	BX.Salescenter.UserConsent.init({
		formId: "salescenter-userconsent-form",
		buttonId: "ui-button-panel-save",
		activeButtonId: 'salescenter_agreement_message',
		agreementBlockId: 'salescenter_agreement_message_block',
	});
</script>
