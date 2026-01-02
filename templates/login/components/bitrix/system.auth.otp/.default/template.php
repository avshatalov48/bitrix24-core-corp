<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.vue3']);
?>

<?php
if ($arResult['REQUIRED_BY_MANDATORY'] === true)
{
?>
	<div class="intranet-island__otp">
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:security.auth.otp.mandatory',
		'',
		array(
			'AUTH_LOGIN_URL' => $arResult['~AUTH_LOGIN_URL'],
			'NOT_SHOW_LINKS' => $arParams['NOT_SHOW_LINKS']
		)
	);
	?>
	</div>
<?php
}
elseif (isset($_GET['help']) && $_GET['help'] === 'Y')
{
?>
	<div class="intranet-island__otp">
		<div>
			<div class="intranet-otp-help-header"><?=Loc::getMessage('INTRANET_AUTH_OTP_HELP_TITLE')?></div>
			<div class="intranet-otp-help-additional-wrap">
				<a href="<?=htmlspecialcharsbx($arResult['AUTH_OTP_LINK'])?>" class="intranet-otp-help-additional-text"><?=Loc::getMessage('INTRANET_AUTH_OTP_BACK')?></a>
			</div>
		</div>
		<hr class="b_line_gray">
		<div class="intranet-otp-help-text">
			<?=Loc::getMessage('INTRANET_AUTH_OTP_HELP_TEXT_MSGVER_1', array('#PATH#' => $this->GetFolder()))?>
			<div class="intranet-otp-help-footer">
				<a href="<?=htmlspecialcharsbx($arResult['AUTH_OTP_LINK'])?>" class="intranet-otp-help-btn"><?=Loc::getMessage('INTRANET_AUTH_OTP_BACK')?></a>
			</div>
		</div>
	</div>
<?php
}
else
{
?>
<div class="intranet-island" data-role="otp-container">
	<form name="form_auth" method="post" target="_top" action="<?=$arResult['AUTH_URL']?>">
		<input type="hidden" name="AUTH_FORM" value="Y" />
		<input type="hidden" name="TYPE" value="OTP" />

		<h2 class="intranet-island-title">
			<?=Loc::getMessage('INTRANET_AUTH_OTP_TITLE')?>
		</h2>
		<?php ShowMessage($arParams['~AUTH_RESULT']); ?> <!-- errors -->

		<div class="intranet-login-enter-form intranet-logging-in__login-form">
			<div class="intranet-login-enter-form__login-wrapper">
				<div class="intranet-text-input intranet-login-enter-form__login">
					<input
						type="text"
						name="USER_OTP"
						class="ui-ctl-element intranet-text-input__field"
						maxlength="50"
						value=""
						autocomplete="off"
						placeholder="<?=Loc::getMessage('INTRANET_AUTH_OTP_PLACEHOLDER')?>"
						ref="modalInput"
					/>
				</div>

				<?php if($arResult['CAPTCHA_CODE']): ?>
					<h4 class="intranet-form-add-block__title intranet-form-add-block__title--margin">
						<?=Loc::getMessage('INTRANET_AUTH_OTP_CAPTCHA_PROMT')?>
					</h4>
					<div class="intranet-text-captcha_item">
						<input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE']?>" />
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE']?>" width="180" height="40" alt="CAPTCHA" />
					</div>
					<div class="intranet-text-input intranet-login-enter-form__login">
						<input
							type="text"
							name="captcha_word"
							placeholder="<?=Loc::getMessage('INTRANET_AUTH_OTP_CAPTCHA_PROMT')?>"
							maxlength="50"
							value=""
							autocomplete="off"
							class="ui-ctl-element intranet-text-input__field"
						/>
					</div>
				<?php endif; ?>
			</div>
			<button
				class="intranet-text-btn intranet-text-btn__reg ui-btn ui-btn-lg ui-btn-success"
				type="submit"
				@click="onSubmitForm"
			>
				<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_AUTH_OTP_CONTINUE_BUTTON')?></span>
				<div class="intranet-text-btn__spinner" v-show="isWaiting"></div>
			</button>
			<?php if($arResult['REMEMBER_OTP']): ?>
				<div class="intranet-base-checkbox intranet-password-enter-form__remember-me">
					<input type="checkbox" id="OTP_REMEMBER" name="OTP_REMEMBER" value="Y" class="login-checkbox-user-remember"/>
					<label for="OTP_REMEMBER" class="login-item-checkbox-label">&nbsp;<?=Loc::getMessage("INTRANET_AUTH_OTP_REMEMBER_ME")?></label>
				</div>
			<?php endif ?>
		</div>

		<Teleport to=".intranet-body__footer-right">
			<button class="intranet-help-widget intranet-page-base__help">
				<i class="ui-icon-set intranet-help-widget__icon"></i>
				<a class="intranet-help-widget__text" href="<?=htmlspecialcharsbx($arResult["AUTH_OTP_HELP_LINK"])?>">
					<?=Loc::getMessage('INTRANET_AUTH_OTP_HELP')?>
				</a>
			</button>
		</Teleport>

		<?php if ($arParams['NOT_SHOW_LINKS'] !== 'Y' && !IsModuleInstalled('bitrix24')): ?>
			<Teleport to=".intranet-body__header-right">
				<div class="intranet-text-btn intranet-text-btn--auth">
					<a class="intranet-text-btn-link" href="<?=htmlspecialcharsbx($arResult["AUTH_LOGIN_URL"])?>" rel="nofollow"><?=Loc::getMessage('INTRANET_AUTH_OTP_LINK')?></a>
				</div>
			</Teleport>
		<?php endif ?>
	</form>
</div>

<script>
	BX.ready(() => {
		const params = {
			containerNode: document.querySelector("[data-role='otp-container']"),
		};
		new BX.Intranet.SystemAuthOtp(params);
	});
</script>
<?php
}
?>


