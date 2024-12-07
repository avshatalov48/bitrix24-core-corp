<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SalesCenterPaySystemComponent $component
 * @var string $templateFolder
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);
$messages = Loc::loadLanguageFile(__FILE__);

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'admin_interface',
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.forms',
	'ui.alerts',
	'ui.switcher',
	'ui.hint',
	'salescenter.manager',
	'ui.sidepanel-content',
	'ui.notification',
	'main.popup',
	'ui.dialogs.messagebox',
	'ui.buttons.icons',
]);
?>

<div class="salescenter-paysystem-wrapper" id="salescenter-paysystem-wrapper">
	<form id="salescenter-main-settings-form" oninput="BX.SalecenterPaySystem.change();">
		<input type="hidden" name="ID" id="ID" value="<?=htmlspecialcharsbx($arResult['PAYSYSTEM_ID'])?>">
		<input type="hidden" name="SORT" id="SORT" value="<?=htmlspecialcharsbx($arResult['PAYSYSTEM']['SORT'])?>">
		<input type="hidden" name="XML_ID" value="<?=htmlspecialcharsbx($arResult['PAYSYSTEM']['XML_ID'])?>">
		<input type="hidden" name="ACTION_FILE" id="ACTION_FILE" value="<?=htmlspecialcharsbx($arResult['PAYSYSTEM_HANDLER'])?>">
		<input type="hidden" name="PS_MODE" id="PS_MODE" value="<?=htmlspecialcharsbx($arResult['PAYSYSTEM_PS_MODE'])?>">
		<input id="salescenter-form-is-saved" type="hidden" value="n">

		<div class="ui-slider-section ui-slider-section-icon">
			<span class="ui-icon ui-slider-icon">
				<?php
				$imageName = $imagePsModeName = $arResult['PAYSYSTEM_HANDLER'];
				if ($arResult['PAYSYSTEM_PS_MODE'])
				{
					$imagePsModeName = $imageName.'_'.$arResult['PAYSYSTEM_PS_MODE'];
				}

				if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$this->GetFolder().'/images/'.$imagePsModeName.'.svg')): ?>
					<div class="salescenter-<?=$arResult['PAYSYSTEM_HANDLER_STYLE'];?>-icon ui-icon"><i></i></div>
				<?php elseif (Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$this->GetFolder().'/images/'.$imageName.'.svg')): ?>
					<div class="salescenter-<?=$arResult['PAYSYSTEM_HANDLER'];?>-icon ui-icon"><i></i></div>
				<?php else: ?>
					<div class="salescenter-default-icon ui-icon"><i></i></div>
				<?php endif; ?>
			</span>
			<div class="ui-slider-content-box">
				<?php
				$title =
					$arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['FULL_NAME']
					?: $arResult['PAYSYSTEM_HANDLER_FULL']
					?: $arResult['PAYSYSTEM_HANDLER'];
				?>
				<div style="display: flex; align-items: center" class="ui-slider-heading-4">
					<?=htmlspecialcharsbx($title)?>
					<div class="salescenter-main-header-feedback-container">
						<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
					</div>
					<div
						id="settings-menu"
						onclick="BX.SalecenterPaySystem.showExpertSettingsMenu(event)"
						class="ui-toolbar-right-buttons salescenter-settings-menu"
					>
						<button class="ui-btn ui-btn-light-border ui-btn-icon-setting ui-btn-themes"></button>
					</div>
				</div>
				<div class="ui-slider-inner-box">
					<?php
					$description = $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['DESCRIPTION'];

					$sanitizer = new CBXSanitizer();
					$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
					$description = $sanitizer->SanitizeHtml($description);
					?>
					<p class="ui-slider-paragraph-2"><?=$description?></p>
					<div class="salescenter-button-container" id="salescenter-button-container-top">
						<?php if (!empty($arResult['ADDITIONAL_LINK_FOR_DESCRIPTION'])):?>
							<div>
								<a class="ui-link ui-link-dashed" href="<?= $arResult['ADDITIONAL_LINK_FOR_DESCRIPTION']['HREF'] ?>" target="_blank">
									<?= $arResult['ADDITIONAL_LINK_FOR_DESCRIPTION']['TITLE'] ?>
								</a>
							</div>
						<?php endif;?>
						<?php
						$docCode = $arResult['HELPDESK_DOCUMENTATION_CODE'];
						if (mb_strtolower($arResult['PAYSYSTEM_HANDLER_CLASS_NAME']) === mb_strtolower(\Sale\Handlers\PaySystem\SkbHandler::class)):?>
							<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigPaySystem(event, <?=$docCode?>);">
								<?=Loc::getMessage('SALESCENTER_SP_LINK_SBP_CONNECT')?>
							</a>
						<?php else:?>
							<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigPaySystem(event, <?=$docCode?>);">
								<?=Loc::getMessage('SALESCENTER_SP_LINK_CONNECT')?>
							</a>
						<?php endif;?>
					</div>
					<div data-bx-salescenter-block="profile" style="display: none;">
						<div class="salescenter-auth-popup-settings">
							<div class="salescenter-auth-popup-social salescenter-auth-popup-social-yandex">
								<div class="salescenter-auth-popup-social-delimiter"></div>
								<div class="salescenter-auth-popup-social-user">
									<a target="_top" data-bx-salescenter-auth-link="" data-bx-salescenter-auth-name="" class="salescenter-auth-popup-social-user-link" title=""></a>
								</div>
								<div class="salescenter-auth-popup-social-shutoff">
									<span data-bx-salescenter-auth-logout="" class="salescenter-auth-popup-social-shutoff-link"><?=Loc::getMessage('SALESCENTER_SP_YANDEX_LOGOUT_MSGVER_1')?></span>
								</div>
							</div>
						</div>
					</div>
					<div data-bx-salescenter-block="auth" style="display: none;" class="salescenter-button-container">
						<div class="ui-text-2"><?=Loc::getMessage('SALESCENTER_SP_CONNECT_HINT')?></div>
						<a id="bx-salescenter-create-button" href="https://yookassa.ru/joinups/?source=bitrix24" target="_blank" class="ui-btn ui-btn-md ui-btn-primary">
							<?=Loc::getMessage('SALESCENTER_SP_CREATE_YOOKASSA_PAYMENT_BUTTON')?>
						</a>
						<a id="bx-salescenter-connect-button" href="javascript: void(0);" class="ui-btn ui-btn-md ui-btn-light-border">
							<?=Loc::getMessage('SALESCENTER_SP_CONNECT_YOOKASSA_PAYMENT_BUTTON')?>
						</a>
					</div>
					<div id="salescenter-settings-block" class="salescenter-button-container">
						<?php if ($arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SUPPORT_SETTINGS']): ?>
							<?php if (!$arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SETTINGS_EXISTS']): ?>
								<span id="salescenter-settings-paysystem-register-component">
									<?php
									$APPLICATION->IncludeComponent(
										'bitrix:sale.paysystem.registration.robokassa',
										'.default'
									);
									?>
								</span>
							<?php endif;?>
							<?php if (
									!$arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SETTINGS_EXISTS']
									|| $arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_ONLY_COMMON_SETTINGS_EXISTS']
								): ?>
								<a
									href="javascript:void(0);"
									onclick="BX.SalecenterPaySystem.openSettingsForm('<?= $arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['FORM_LINK'] ?>')"
									id="salescenter-settings-paysystem-form-link"
									class="ui-btn ui-btn-md ui-btn-light-border ui-btn-width"
								>
									<?= $arResult['SETTINGS_FORM_LINK_NAME'] ?>
								</a>
							<?php endif; ?>
						<?php elseif ($arResult['IS_PS_INNER_SETUP']): ?>
							<a id="bx-salescenter-add-button" class="ui-btn ui-btn-md ui-btn-light-border ui-btn-width">
								<?=Loc::getMessage('SALESCENTER_SP_ADD_PAYMENT_BUTTON')?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="ui-alert ui-alert-danger" style="display: none;">
			<span class="ui-alert-message" id="salescenter-paysystem-error"></span>
		</div>
		<div data-bx-salescenter-block="form" style="display: none;" class="salescenter-main-settings">
			<?php
			$name = '';
			if (isset($arResult['PAYSYSTEM']['NAME']))
			{
				$name = $arResult['PAYSYSTEM']['NAME'];
			}
			else
			{
				$name = $arResult['PAYSYSTEM_PS_MODE']
					? $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['MODE_NAME']
					: $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME'];
			}

			$description = $arResult['PAYSYSTEM']['DESCRIPTION'] ?? $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['PUBLIC_DESCRIPTION'];

			$sanitizer = new CBXSanitizer();
			$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$description = $sanitizer->SanitizeHtml($description);
			?>
			<div class="ui-slider-section">
				<div class="ui-slider-heading-4"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_TITLE')?></div>
				<div class="salescenter-editor-section-content">
					<?php // is active ?>
					<?php if ($arResult['PAYSYSTEM_ID'] > 0): ?>
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
							<input
								type="checkbox"
								name="ACTIVE"
								id="ACTIVE"
								class="ui-ctl-element salescenter-paysystem-checkbox-input"
								value="Y"
								<?= ($arResult['PAYSYSTEM']['ACTIVE'] === 'Y') ? ' checked' : '' ?>
							>
							<div class="ui-ctl-block ui-ctl-column">
								<label for="ACTIVE" class="ui-ctl-label-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_ACTIVE')?></label>
								<label for="ACTIVE" class="ui-ctl-label-text salescenter-paysystem-checkbox-desc"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_ACTIVE_DESC')?></label>
							</div>
						</div>
					<?php endif ?>

					<?php // name ?>
					<div class="salescenter-editor-content-block">
						<div class="ui-ctl-label-text">
							<label for="NAME"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_NAME')?></label>
						</div>
						<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
							<input type="text" name="NAME" id="NAME" class="ui-ctl-element" value="<?=htmlspecialcharsbx($name)?>" autocomplete="no">
						</div>
						<div style="margin-top: 5px;" class="ui-ctl-label-text">
							<span class="salescenter-editor-content-logo-hint"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_NAME_HINT_V2')?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="ui-slider-section">
				<?php
				if ($arResult['IS_CASHBOX_ENABLED'])
				{
					$paySystemId = (int)$arResult['PAYSYSTEM_ID'];
					$isCanPrintCheckSelf = $arResult['IS_CAN_PRINT_CHECK_SELF'];
					?>
					<div class="ui-slider-heading-4"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_CASHBOX_TITLE')?></div>
					<div class="salescenter-editor-section-content">
						<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
							<input
								type="checkbox"
								name="CAN_PRINT_CHECK"
								id="CAN_PRINT_CHECK"
								class="ui-ctl-element"
								value="Y"
								<?= ($arResult['IS_FISCALIZATION_ENABLE'] || $arResult['PAYSYSTEM']['CAN_PRINT_CHECK'] === 'Y') ? ' checked' : '' ?>
							>
							<label for="CAN_PRINT_CHECK" class="ui-ctl-label-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_CAN_PRINT_CHECK')?></label>
						</div>

						<?php
						if ($isCanPrintCheckSelf)
						{
							$cashboxCode = $arResult['CASHBOX']['code'];
							$cashboxTitle = Loc::getMessage('SALESCENTER_SP_CASHBOX_PAYSYSTEM_'.$cashboxCode.'_TITLE');
							if (!$cashboxTitle)
							{
								$cashboxTitle = Loc::getMessage(
									'SALESCENTER_SP_CASHBOX_PAYSYSTEM_TITLE',
									[
										'#PAY_SYSTEM_NAME#' => $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME']
									]
								);
							}

							$cashboxDescription = Loc::getMessage('SALESCENTER_SP_CASHBOX_PAYSYSTEM_' . $cashboxCode . '_DESCRIPTION');
							$cashboxLinkText = Loc::getMessage('SALESCENTER_SP_CASHBOX_PAYSYSTEM_' . $cashboxCode . '_LINK_TEXT');

							$linkReplace = [
								'#CASHBOX_SETTINGS_LINK#' => 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&CHAPTER_ID=09179&LESSON_PATH=3912.4580.9179',
							];
							$cashboxHint =
								Loc::getMessage('SALESCENTER_SP_CASHBOX_HINT_' . $cashboxCode, $linkReplace)
								?? Loc::getMessage('SALESCENTER_SP_CASHBOX_HINT', $linkReplace);

							$cashboxDocCode = $arResult['CASHBOX']['documentationCode'];

							if ($arResult['SUPPORTED_KKM_MODELS'])
							{
								if (\count($arResult['SUPPORTED_KKM_MODELS']) === 1)
								{
									?><input type="hidden" name="CASHBOX[KKM_ID]" id="KKM_ID" value="<?= current($arResult['SUPPORTED_KKM_MODELS']) ?>"><?php
								}
								else
								{
									?>
									<div class="salescenter-editor-content-block">
										<div class="ui-ctl-label-text">
											<label for="KKM_ID"><?= $arResult['PAY_SYSTEM_CODE_NAME'] ?></label>
										</div>
										<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
											<div class="ui-ctl-after ui-ctl-icon-angle"></div>
											<select name="CASHBOX[KKM_ID]" id="KKM_ID" class="ui-ctl-element" onchange="BX.SalecenterPaySystemCashbox.reloadCashboxSettings(this)">
												<?php
												foreach ($arResult['SUPPORTED_KKM_MODELS'] as $supportedKkm)
												{
													echo '<option value="' . $supportedKkm . '">' . htmlspecialcharsbx($supportedKkm) . '</option>';
												}
												?>
											</select>
										</div>
									</div>
									<?php
								}
							}
						?>

						<?php if ($arResult['SHOW_CASHBOX_HINT']): ?>
							<div class="ui-alert ui-alert-close-animate ui-alert-warning" id="salescenter-cashbox-warning">
								<span class="ui-alert-message"><?= $cashboxHint ?></span>
							</div>
						<?php endif; ?>

							<div id="salescenter-paysystem-cashbox-block" class="salescenter-paysystem-cashbox-block <?= ($arResult['IS_FISCALIZATION_ENABLE']) ? '' : ' salescenter-paysystem-cashbox-block--disabled' ?>">
								<div class="salescenter-paysystem-cashbox-block-inner">
									<div class="salescenter-paysystem-cashbox-block-title"><?= $cashboxTitle ?></div>
									<div class="salescenter-main-cashbox-switcher-container">
									<span data-switcher="<?=htmlspecialcharsbx(Main\Web\Json::encode([
										'id' => 'salescenter-paysystem-cashbox',
										'checked' => $arResult['IS_FISCALIZATION_ENABLE'],
										'inputName' => 'CAN_PRINT_CHECK_SELF',
										'color' => 'green',
									]))?>" class="js-cashbox-ui-switcher"></span>
									</div>
								</div>
								<?php if ($cashboxDescription): ?>
								<div class="salescenter-paysystem-cashbox-block-description"><?= $cashboxDescription ?></div>
								<?php endif; ?>
								<?php if ($cashboxLinkText && $cashboxDocCode): ?>
								<a class="ui-link ui-link-solid" onclick="BX.Salescenter.Manager.openHowToConfigCashboxPaySystem(event, <?= $cashboxDocCode ?>);"><?= $cashboxLinkText ?></a>
								<?php endif; ?>
							</div>
						<?php } ?>

						<?php if ($isCanPrintCheckSelf): ?>
							<div id="salescenter-paysystem-cashbox" <?=(($arResult['IS_FISCALIZATION_ENABLE']) ? '' : "style='display:none;'")?>>
								<div id="salescenter-paysystem-cashbox-settings"></div>
								<div id="salescenter-paysystem-cashbox-settings-cashbox"></div>
							</div>
						<?php endif ?>
					</div>
					<?php
				}
				?>
			</div>
			<div hidden><?=$arResult["BUS_VAL"];?></div>

			<div class="ui-slider-section">
				<div class="ui-slider-heading-4"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_ADDITIONAL_TITLE')?></div>

				<?php // description ?>
				<div class="salescenter-editor-content-block">
					<div class="ui-ctl-label-text">
						<label for="DESCRIPTION"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_DESCRIPTION')?></label>
					</div>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize ui-ctl-w100">
						<textarea name="DESCRIPTION" id="DESCRIPTION" class="ui-ctl-element"><?=$description?></textarea>
					</div>
				</div>

				<?php // logo ?>
				<div class="salescenter-editor-content-block">
					<div class="ui-ctl-label-text">
						<label for="LOGOTIP"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_LOGOTIP')?></label>
					</div>
					<div class="salescneter-editor-img-wrapper">
						<?php
						if (!empty($arResult['PAYSYSTEM']["LOGOTIP"]))
						{
							$logo = \CFile::ResizeImageGet(
								$arResult['PAYSYSTEM']["LOGOTIP"],
								[
									"width" => 100,
									"height" => 100
								],
								BX_RESIZE_IMAGE_PROPORTIONAL,
								false
							);
							?>
							<div class="salescneter-editor-img-container">
								<img
									src="<?=$logo['src']?>"
									alt="<?=htmlspecialcharsbx($arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME'])?>"
									class="salescneter-editor-content-img"
									id="salescenter-img-preload"
								>
							</div>
							<?php
						}
						else
						{
							if (!empty($arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['LOGO']))
							{
								?>
								<div class="salescneter-editor-img-container">
									<img
										src="<?=$arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['LOGO']?>"
										alt="<?=htmlspecialcharsbx($arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME'])?>"
										class="salescneter-editor-content-img"
										id="salescenter-img-preload">
								</div>
								<?php
							}
							else
							{
								?>
								<div class="salescneter-editor-img-container">
									<img src="" alt="" class="salescneter-editor-content-img" id="salescenter-img-preload">
								</div>
								<?php
							}
						}
						?>
						<div class="salescneter-editor-img-load">
							<label class="ui-ctl ui-ctl-file-btn">
								<input type="file" name="LOGOTIP" id="LOGOTIP" class="ui-ctl-element">
								<div class="ui-ctl-label-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_LOGOTIP_LOAD_BUTTON')?></div>
							</label>
						</div>
					</div>
					<div style="margin-top: 15px;" class="ui-ctl-label-text">
						<span class="salescenter-editor-content-logo-hint"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_LOGOTIP_HINT')?></span>
					</div>
				</div>

				<?php // payment type ?>
				<div class="salescenter-editor-content-block">
					<div class="ui-ctl-label-text">
						<label for="IS_CASH"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_IS_CASH')?></label>
					</div>
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<?php
						$isCash = $arResult['PAYSYSTEM']['IS_CASH'];
						?>
						<select name="IS_CASH" id="IS_CASH" class="ui-ctl-element">
							<option value="N" <?=($isCash == 'N') ? ' selected' : '';?>><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_IS_CASH_NO_CASH')?></option>
							<option value="Y" <?=($isCash == 'Y') ? ' selected' : '';?>><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_IS_CASH_CASH')?></option>
							<option value="A" <?=($isCash == 'A') ? ' selected' : '';?>><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_IS_CASH_ACQUIRING')?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php

		$saveButton = [
			'TYPE' => 'save',
		];

		if ($arResult['PAYSYSTEM_ID'] <= 0)
		{
			$saveButton['CAPTION'] = Loc::getMessage('SALESCENTER_SP_SAVE_NEW_BUTTON_CAPTION');
		}

		$buttons = [
			$saveButton,
			[
				'TYPE' => 'cancel',
				'URL' => $arParams['SALESCENTER_DIR'],
			],
		];

		$hideButtonPanel = $arResult['PAYSYSTEM_ID'] > 0 || ($arResult['AUTH']['CAN_AUTH'] && !$arResult['AUTH']['HAS_AUTH']);

		$buttonPanelId = 'salesenter-paysystem-button-panel';
		$APPLICATION->IncludeComponent(
			'bitrix:ui.button.panel',
			"",
			[
				'BUTTONS' => $buttons,
				'ALIGN' => 'center',
				'ID' => $buttonPanelId,
				'HIDE' => $hideButtonPanel,
			],
			false
		);
		?>
	</form>
</div>

<script>
	BX.message(<?=CUtil::PhpToJSObject($messages)?>);
	BX.ready(function(){
		BX.SalecenterPaySystem.init({
			paySystemHandler: '<?=CUtil::JSEscape($arResult['PAYSYSTEM_HANDLER'])?>',
			paySystemMode: '<?=CUtil::JSEscape($arResult['PAYSYSTEM_PS_MODE'])?>',
			paySystemId: '<?=CUtil::JSEscape($arResult['PAYSYSTEM_ID'])?>',
			containerId: 'salescenter-paysystem-wrapper',
			formId: 'salescenter-main-settings-form',
			buttonSaveId: 'ui-button-panel-save',
			auth: <?=CUtil::PhpToJSObject($arResult['AUTH'])?>,
			errorMessageId: 'salescenter-paysystem-error',
			settingsFormLinkNameCodeMap: <?=CUtil::PhpToJSObject($arResult['SETTINGS_FORM_LINK_NAME_CODE_MAP'] ?? [])?>,
			handlerClassName: '<?=CUtil::JSEscape($arResult['PAYSYSTEM_HANDLER_CLASS_NAME'])?>',
			isExistsSettings: <?=CUtil::PhpToJSObject($arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SETTINGS_EXISTS'] ?? false)?>,
			isExistsOnlyCommonSettings: <?=CUtil::PhpToJSObject($arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_ONLY_COMMON_SETTINGS_EXISTS'] ?? false)?>,
			settingsMenuId: 'settings-menu',
			buttonPanelId: '<?=CUtil::JSEscape($buttonPanelId)?>',
		})

		BX.SalecenterPaySystemCashbox.init({
			paySystemId: '<?=CUtil::JSEscape($arResult['PAYSYSTEM_ID'])?>',
			formId: 'salescenter-main-settings-form',
			cashboxContainerInfoId: 'salescenter-paysystem-cashbox-block',
			cashboxContainerId: 'salescenter-paysystem-cashbox',
			cashboxWarningContainerId: 'salescenter-cashbox-warning',
			canPrintCheckId: 'CAN_PRINT_CHECK',
			section: <?=CUtil::PhpToJSObject($arResult['CASHBOX']['section'] ?? [])?>,
			fields: <?=CUtil::PhpToJSObject($arResult['CASHBOX']['fields'] ?? [])?>,
			containerList: {
				settings: 'salescenter-paysystem-cashbox-settings',
				cashboxSettings: 'salescenter-paysystem-cashbox-settings-cashbox',
			},
		});

		BX.UI.Switcher.initByClassName();
	});
</script>
