<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SalesCenterPaySystemComponent $component
 * @var string $templateFolder
 */
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);
$messages = Loc::loadLanguageFile(__FILE__);

CJSCore::Init([
	"admin_interface",
]);

Extension::load([
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.forms',
	'ui.alerts',
	'ui.switcher',
	'salescenter.manager',
]);
?>

<div class="salescenter-paysystem-wrapper" id="salescenter-paysystem-wrapper">
	<form id="salescenter-main-settings-form">
		<input type="hidden" name="ID" id="ID" value="<?=$arResult['PAYSYSTEM_ID']?>">
		<input type="hidden" name="SORT" id="SORT" value="<?=$arResult['PAYSYSTEM']['SORT']?>">
		<input type="hidden" name="XML_ID" value="<?=$arResult['PAYSYSTEM']['XML_ID']?>">
		<input type="hidden" name="ACTION_FILE" id="ACTION_FILE" value="<?=$arResult['PAYSYSTEM_HANDLER']?>">
		<input type="hidden" name="PS_MODE" id="PS_MODE" value="<?=$arResult['PAYSYSTEM_PS_MODE']?>">
		<input id="salescenter-form-is-saved" type="hidden" value="n">

		<div class="salescenter-wrapper ui-bg-color-white">
			<div style="padding: 15px; margin-bottom: 15px;">
				<div class="salescenter-main-header">
					<div class="salescenter-main-header-left-block">
						<div class="salescenter-logo-container">
							<div class="salescenter-<?=$arResult['PAYSYSTEM_HANDLER_STYLE'];?>-icon ui-icon"><i></i></div>
						</div>
					</div>
					<div class="salescenter-main-header-right-block">
						<div class="salescenter-main-header-title-container">
							<?php
							$title = Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_'.$arResult['PAYSYSTEM_HANDLER_FULL'].'_TITLE');
							if (!$title)
							{
								$title = $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['FULL_NAME'];
							}
							?>
							<div class="ui-title-3" style="margin-bottom: 0;"><?=$title?></div>
							<div class="salescenter-main-header-feedback-container">
								<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
							</div>
							<div data-bx-salescenter-block="form" class="salescenter-main-header-switcher-container">
								<span data-switcher="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode([
									'id' => 'salescenter-paysystem-active',
									'checked' => ($arResult['PAYSYSTEM']['ACTIVE'] === 'Y'),
									'inputName' => "ACTIVE",
									'color' => "green"
								]))?>" class="ui-switcher"></span>
							</div>
						</div>
						<hr class="ui-hr" style="margin-bottom: 15px;">
						<?php
						$description = Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_'.$arResult['PAYSYSTEM_HANDLER_FULL'].'_DESCRIPTION');
						if (!$description)
						{
							$description = $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['DESCRIPTION'];
						}
						?>
						<div class="ui-text-2" style="margin-bottom: 15px;"><?=htmlspecialcharsbx($description);?></div>
						<div class="salescenter-button-container">
							<a class="salescenter-paysystem-link" onclick="BX.Salescenter.Manager.openHowToConfigPaySystem(event);"><?=Loc::getMessage('SALESCENTER_SP_LINK_CONNECT')?></a>
						</div>
						<div data-bx-salescenter-block="profile" style="display: none;">
							<div class="salescenter-auth-popup-settings">
								<div class="salescenter-auth-popup-social salescenter-auth-popup-social-yandex">
									<div class="salescenter-auth-popup-social-avatar">
										<div data-bx-salescenter-auth-avatar="" class="salescenter-auth-popup-social-avatar-icon"></div>
									</div>
									<div class="salescenter-auth-popup-social-user">
										<a target="_top" data-bx-salescenter-auth-link="" data-bx-salescenter-auth-name="" class="salescenter-auth-popup-social-user-link" title=""></a>
									</div>
									<div class="salescenter-auth-popup-social-shutoff">
										<span data-bx-salescenter-auth-logout="" class="salescenter-auth-popup-social-shutoff-link"><?=Loc::getMessage('SALESCENTER_SP_YANDEX_LOGOUT')?></span>
									</div>
								</div>
							</div>
						</div>
						<div data-bx-salescenter-block="auth" style="display: none;" class="salescenter-button-container">
							<div class="ui-text-2"><?=Loc::getMessage('SALESCENTER_SP_CONNECT_HINT')?></div>
							<a id="bx-salescenter-connect-button" href="javascript: void(0);" class="ui-btn ui-btn-md ui-btn-primary ui-btn-width">
								<?=Loc::getMessage('SALESCENTER_SP_CONNECT_PAYMENT_BUTTON')?>
							</a>
						</div>
						<div data-bx-salescenter-block="settings" style="display: none;" class="salescenter-button-container">
							<a id="bx-salescenter-add-button" href="javascript: void(0);" class="ui-btn ui-btn-md ui-btn-light-border ui-btn-width">
								<?=Loc::getMessage('SALESCENTER_SP_ADD_PAYMENT_BUTTON')?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div data-bx-salescenter-block="form" style="display: none;" class="salescenter-main-settings ui-bg-color-white">
				<div class="ui-alert ui-alert-danger" style="display: none;">
					<span class="ui-alert-message" id="salescenter-paysystem-error"></span>
				</div>
				<?php
				$name = '';
				if (isset($arResult['PAYSYSTEM']['NAME']))
				{
					$name = $arResult['PAYSYSTEM']['NAME'];
				}
				else
				{
					if ($arResult['PAYSYSTEM_PS_MODE'])
					{
						$name = $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['MODE_NAME'];
					}
					else
					{
						$name = $arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME'];
					}
				}

				$description = '';
				if (isset($arResult['PAYSYSTEM']['DESCRIPTION']))
				{
					$description = $arResult['PAYSYSTEM']['DESCRIPTION'];
				}
				?>
				<div class="salescenter-editor-section-edit">
					<div class="salescenter-editor-section-header">
						<span class="salescenter-editor-header-title-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_TITLE')?></span>
					</div>
					<div class="salescenter-editor-section-content">
						<div class="salescenter-editor-content-block">
							<div class="ui-ctl-label-text">
								<label for="NAME"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_NAME')?></label>
							</div>
							<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
								<input type="text" name="NAME" id="NAME" class="ui-ctl-element" value="<?=htmlspecialcharsbx($name)?>">
							</div>
							<div style="margin-top: 5px;" class="ui-ctl-label-text">
								<span class="salescenter-editor-content-logo-hint"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_NAME_HINT')?></span>
							</div>
						</div>
						<div class="salescenter-editor-content-block">
							<div class="ui-ctl-label-text">
								<label for="DESCRIPTION"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_DESCRIPTION')?></label>
							</div>
							<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize ui-ctl-w100">
								<textarea name="DESCRIPTION" id="DESCRIPTION" class="ui-ctl-element"><?=htmlspecialcharsbx($description)?></textarea>
							</div>
						</div>
						<div class="salescenter-editor-content-block">
							<div class="ui-ctl-label-text">
								<label for="LOGOTIP"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_LOGOTIP')?></label>
							</div>
							<div class="salescneter-editor-img-wrapper">
							<?php
								if ($arResult['PAYSYSTEM']["LOGOTIP"])
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
											alt="<?=$arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME']?>"
											class="salescneter-editor-content-img"
											id="salescenter-img-preload"
										>
									</div>
									<?php
								}
								else
								{
									if ($arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['LOGO'])
									{
										?>
										<div class="salescneter-editor-img-container">
											<img
												src="<?=$arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['LOGO']?>"
												alt="<?=$arResult['PAYSYSTEM']['HANDLER_DESCRIPTION']['NAME']?>"
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
					<?php
					if($arResult['isCashboxEnabled'])
					{
					?>
					<div class="salescenter-editor-section-header">
						<span class="salescenter-editor-header-title-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_CASHBOX_TITLE')?></span>
					</div>
					<div class="salescenter-editor-section-content">
						<div class="salescenter-editor-block-title"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_CASHBOX')?></div>
						<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
							<input type="checkbox" name="CAN_PRINT_CHECK" id="CAN_PRINT_CHECK" class="ui-ctl-element" value="Y" <?=($arResult['PAYSYSTEM']['CAN_PRINT_CHECK'] == 'Y') ? ' checked' : ''?>>
							<label for="CAN_PRINT_CHECK" class="ui-ctl-label-text"><?=Loc::getMessage('SALESCENTER_SP_PARAMS_FORM_CAN_PRINT_CHECK')?></label>
						</div>
						<?php
						if ($arResult['SHOW_CASHBOX_HINT'])
						{
							?>
							<div class="ui-alert ui-alert-close-animate ui-alert-warning" id="salescenter-cashbox-warning">
								<span class="ui-alert-message"><?=Loc::getMessage('SALESCENTER_SP_CAHSBOX_HINT', [
									'#CASHBOX_SETTINGS_LINK#' => 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&CHAPTER_ID=09179&LESSON_PATH=3912.4580.9179'
								])?></span>
								<span class="ui-alert-close-btn" onclick="BX.hide(BX('salescenter-cashbox-warning'));"></span>
							</div>
							<?php
						}
						?>
					</div>
					<?php
					}
					?>
				</div>
				<div hidden><?=$arResult["BUS_VAL"];?></div>
				<?php
				$buttons = [
					'save',
					'cancel' => $arParams['SALESCENTER_DIR']
				];
				if($arResult['PAYSYSTEM_ID'] > 0)
				{
					$buttons[] = [
						'TYPE' => 'remove',
						'ONCLICK' => 'BX.SalecenterPaySystem.remove(event);',
					];
				}
				$APPLICATION->IncludeComponent(
					'bitrix:ui.button.panel',
					"",
					[
						'BUTTONS' => $buttons,
						'ALIGN' => "center"
					],
					false
				);
				?>
		</div>
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
			formId: "salescenter-main-settings-form",
			buttonSaveId: "ui-button-panel-save",
			auth: <?=CUtil::PhpToJSObject($arResult['AUTH'])?>,
			errorMessageId: 'salescenter-paysystem-error',
		});

		BX.UI.Switcher.initByClassName();
	});
</script>