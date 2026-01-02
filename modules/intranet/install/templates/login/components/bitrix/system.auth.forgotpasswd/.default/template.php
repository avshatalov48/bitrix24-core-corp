<?php
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.vue3']);

$forgotLogin = isset($_REQUEST['forgot_login']) && $_REQUEST['forgot_login'] === 'yes';
?>

<div class="intranet-island" data-role="forgotpassword-container">
<?php
if ($forgotLogin)
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.auth.forgotlogin', '', array());
}
else
{
?>
	<form name="form_auth" method="post" target="_top" action="<?=$arResult['AUTH_URL']?>">
		<?php if ($arResult['BACKURL'] <> ''): ?>
			<input type="hidden" name="backurl" value="<?=$arResult['BACKURL']?>" />
		<?php endif ?>
		<input type="hidden" name="AUTH_FORM" value="Y">
		<input type="hidden" name="TYPE" value="SEND_PWD">

		<div class="intranet-login-enter-form intranet-logging-in__login-form">
			<h2 class="intranet-island-title">
				<?=Loc::getMessage('INTRANET_FORGOT_PASS_TITLE')?>
			</h2>

			<template v-if="isFormVisible">
				<?php ShowMessage($arParams['~AUTH_RESULT']); ?> <!-- errors -->
				<p class="intranet-island-title-info"><?=Loc::getMessage('INTRANET_FORGOT_PASS_INFO')?></p>
				<div class="intranet-login-enter-form__login-wrapper">
					<div class="intranet-text-input intranet-login-enter-form__login">
						<input
							type="text"
							:name="loginOrEmail"
							maxlength="255"
							placeholder="<?=Loc::getMessage('INTRANET_FORGOT_PASS_LOGIN_OR_EMAIL')?>"
							class="ui-ctl-element intranet-text-input__field"
							ref="modalInput"
							@input="onEnterLoginOrEmail($event.target.value)"
						/>
					</div>

					<?php if ($arResult['USE_CAPTCHA']): ?>
					<h4 class="intranet-form-add-block__title intranet-form-add-block__title--margin">
						<?=Loc::getMessage('INTRANET_FORGOT_PASS_CAPTCHA_PROMT')?>
					</h4>
					<div class="intranet-text-captcha_item">
						<input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE']?>" />
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE']?>" width="180" height="40" alt="CAPTCHA" />
					</div>
					<div class="intranet-text-input intranet-login-enter-form__login">
						<input
							class="ui-ctl-element intranet-text-input__field"
							type="text"
							name="captcha_word"
							maxlength="50"
							value=""
							size="15"
							autocomplete="off"
							placeholder="<?=Loc::getMessage('INTRANET_FORGOT_PASS_CAPTCHA_PROMT')?>"
						/>
					</div>
					<?php endif ?>
				</div>

				<button
					class="intranet-text-btn ui-btn ui-btn-lg ui-btn-success"
					type="submit"
					@click="onSubmitForm"
				>
					<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_FORGOT_PASS_BUTTON')?></span>
					<div class="intranet-text-btn__spinner" v-show="isWaiting"></div>
				</button>
			</template>
			<template v-else>
				<div class="intranet-notification">
					<div class="intranet-big-icon intranet-big-icon--email intranet-notification__icon"></div>
					<div class="intranet-notification__content">
						<?=$arParams['~AUTH_RESULT']['MESSAGE']?>
					</div>
				</div>
			</template>
		</div>
	</form>

	<Teleport to=".intranet-body__header-right">
		<div class="intranet-text-btn intranet-text-btn--auth">
			<a class="intranet-text-btn-link" href="<?=$arResult['AUTH_AUTH_URL']?>" rel="nofollow">
				<?=Loc::getMessage('INTRANET_FORGOT_PASS_AUTH_LINK')?>
			</a>
		</div>
	</Teleport>

	<script>
		BX.ready(() => {
			const params = {
				containerNode: document.querySelector("[data-role='forgotpassword-container']"),
				isFormVisible: '<?=($arResult['SHOW_FORM'] ? 'Y' : 'N')?>',
			};
			new BX.Intranet.SystemAuthForgotPassword(params);
		});
	</script>
<?php
}
?>
</div>

