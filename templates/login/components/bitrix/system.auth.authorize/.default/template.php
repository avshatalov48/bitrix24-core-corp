<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

$APPLICATION->SetTitle(Loc::getMessage('INTRANET_AUTH_TITLE'));

$extensions = ['ui.forms', 'ui.hint'];

if ($arResult['ALLOW_QRCODE_AUTH'])
{
	$extensions = array_merge($extensions, ['qrcode', 'pull.client', 'loader']);
}

\Bitrix\Main\UI\Extension::load($extensions);
?>
<div data-role="auth-container">
	<form name="form_auth" method="post" target="_top" action="<?=$arResult['AUTH_URL']?>">
		<input type="hidden" name="AUTH_FORM" value="Y" />
		<input type="hidden" name="TYPE" value="AUTH" />

		<?if ($arResult['BACKURL'] <> ''):?>
			<input type="hidden" name="backurl" value="<?=$arResult['BACKURL']?>" />
		<?endif?>
		<?foreach ($arResult['POST'] as $key => $value):?>
			<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
		<?endforeach?>

		<div class="intranet-logging-in">
			<div class="intranet-island-with-sidebar">
				<div class="intranet-island-with-sidebar__island">
					<div class="intranet-logging-in__island-wrapper intranet-island">
						<h2 class="intranet-island-title"><?=Loc::getMessage('INTRANET_AUTH_TITLE')?></h2>
						<?php ShowMessage($arParams['~AUTH_RESULT']); ?> <!-- errors -->
						<div class="intranet-login-enter-form intranet-logging-in__login-form">
							<div class="intranet-login-enter-form__login-wrapper">
								<div class="intranet-text-input intranet-login-enter-form__login">
									<input
										class="ui-ctl-element intranet-text-input__field"
										type="text"
										name="USER_LOGIN"
										placeholder="<?=Loc::getMessage('INTRANET_AUTH_LOGIN')?>"
										value="<?=$arResult['LAST_LOGIN']?>"
										maxlength="255"
										autofocus
										data-testid="user-login"
									/>
								</div>
								<div class="intranet-text-input intranet-login-enter-form__login">
									<input
										class="ui-ctl-element intranet-text-input__field"
										type="password"
										name="USER_PASSWORD"
										placeholder="<?=Loc::getMessage('INTRANET_AUTH_PASSWORD')?>"
										maxlength="255"
										autocomplete="current-password"
										data-testid="user-password"
									/>
								</div>
							</div>

							<?php if($arResult['CAPTCHA_CODE']):?>
								<div class="intranet-text-captcha_item">
									<input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE']?>" />
									<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE']?>" width="180" height="40" alt="CAPTCHA" />
								</div>
								<div class="intranet-text-input intranet-login-enter-form__login">
									<input
										class="ui-ctl-element intranet-text-input__field"
										type="text"
										name="captcha_word"
										placeholder="<?=Loc::getMessage('INTRANET_AUTH_CAPTCHA_PROMT')?>"
										maxlength="50"
										value=""
										size="15"
										autocomplete="off"
									/>
								</div>
							<?php endif; ?>

							<button class="intranet-text-btn ui-btn ui-btn-lg ui-btn-success" data-role="auth-form-button">
								<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_AUTH_BUTTON')?></span>
								<div class="intranet-text-btn__spinner" style="display: none;"></div>
							</button>

							<div class="intranet-password-enter-form__controls-row">
								<?php if ($arResult['STORE_PASSWORD'] === 'Y'): ?>
									<div class="intranet-password-enter-form__remember-me">
										<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y"/>
										<label for="USER_REMEMBER"><?=Loc::getMessage('INTRANET_AUTH_REMEMBER_ME')?></label>
									</div>
								<?php endif ?>
								<a class="intranet-password-enter-form__forgot-pass" href="<?=$arResult['AUTH_FORGOT_PASSWORD_URL']?>"><?=Loc::getMessage("INTRANET_AUTH_FORGOT_PASSWORD")?></a>
							</div>
							<?php if($arParams['NOT_SHOW_LINKS'] !== 'Y' && $arResult['NEW_USER_REGISTRATION'] === 'Y'):?>
								<noindex>
									<div class="login-links">
										<a  class="intranet-password-enter-form__forgot-pass" href="<?=$arResult['AUTH_REGISTER_URL']?>" rel="nofollow"><?=Loc::getMessage('INTRANET_AUTH_REGISTER')?></a>
									</div>
								</noindex>
							<?php endif ?>

							<?php if ($arResult['AUTH_SERVICES']):?>
								<div class="intranet-login-enter-form__social-wrapper">
									<span class="intranet-login-enter-form__social-title"><?=Loc::getMessage('INTRANET_AUTH_SOCSERV_TITLE')?></span>
									<div class="intranet-login-enter-form__social-buttons">
										<?php
										$APPLICATION->IncludeComponent('bitrix:socserv.auth.form', 'flat',
											[
												'AUTH_SERVICES' => $arResult['AUTH_SERVICES'],
												'CURRENT_SERVICE' => $arResult['CURRENT_SERVICE'],
												'AUTH_URL' => $arResult['AUTH_URL'],
												'POST' => $arResult['POST'],
												'SHOW_TITLES' => 'N',
												'FOR_SPLIT' => 'Y',
												'AUTH_LINE' => 'N',
											],
											$component,
											array('HIDE_ICONS' => 'Y')
										);
										?>
									</div>
								</div>
							<?php endif ?>
						</div>
					</div>
				</div>

				<?php if ($arResult['ALLOW_QRCODE_AUTH']): ?>
				<div class="intranet-island-with-sidebar__sidebar">
					<div class="intranet-qr-scan-form --hidden intranet-logging-in__qr-code" style="" data-role="log-popup-form-qr-icon">
						<div class="log-popup-form-qr-icon-status --loading" data-role="log-popup-form-qr-icon-loader"></div>
						<div class="intranet-qr-scan-form__title"><?=Loc::getMessage('INTRANET_AUTH_QR_TITLE', ['[br]' => '<br>'])?></div>
						<div class="intranet-qr-scan-form__instructions"><?=Loc::getMessage('INTRANET_AUTH_QR_DESC', ['[br]' => '<br>'])?></div>
						<div class="intranet-qr-scan-form__code-overlay"></div>
						<div class="intranet-qr-scan-form__code-wrapper" id="bx_auth_qr_code"></div>
						<div class="intranet-qr-scan-form__mobiles-links">
							<a href="#"
							   class="intranet-qr-scan-form__ios-app-link"
							   data-role="login-ios-app"
							   data-hint="<?=Loc::getMessage('INTRANET_INSTALL_APP_WITH_QR', ['[br]' => '<br>'])?>"
							   data-hint-html
							   data-hint-no-icon="true"
							></a>
							<a href="#"
							   class="intranet-qr-scan-form__android-app-link"
							   data-role="login-android-app"
							   data-hint="<?=Loc::getMessage('INTRANET_INSTALL_APP_WITH_QR', ['[br]' => '<br>'])?>"
							   data-hint-html
							   data-hint-no-icon="true"
							></a>
						</div>
					</div>
				</div>
				<?php endif ?>
			</div>
		</div>
	</form>
</div>

<script>
	BX.ready(() => {
		let params = {
			authContainerNode: document.querySelector("[data-role='auth-container']"),
			isQrAvailable: '<?=($arResult['ALLOW_QRCODE_AUTH'] ? 'Y' : 'N')?>',
		};

		<?php if ($arResult['ALLOW_QRCODE_AUTH']): ?>
			params['qrText'] = '<?=$arResult['QRCODE_TEXT']?>';
			params['qrConfig'] = <?= \Bitrix\Main\Web\Json::encode($arResult['QRCODE_CONFIG']) ?>;
		<?php endif; ?>

		new BX.Intranet.SystemAuthAuthorize(params);
	});
</script>
