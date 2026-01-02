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
?>
<div class="intranet-island" data-role="changepassword-container">
	<div class="intranet-login-enter-form intranet-logging-in__login-form">
		<h2 class="intranet-island-title" v-show="!isCaptchaBlockVisible">
			<?=Loc::getMessage('INTRANET_CHANGE_PASS_TITLE')?>
		</h2>

		<template v-if="isFormVisible">
			<?php ShowMessage($arParams['~AUTH_RESULT']); ?> <!-- errors -->

			<form method="post" action="<?=$arResult['AUTH_URL']?>" name="form_auth">
				<?php if ($arResult['BACKURL'] <> ''): ?>
					<input type="hidden" name="backurl" value="<?=$arResult['BACKURL']?>" />
				<?php endif ?>
				<input type="hidden" name="AUTH_FORM" value="Y">
				<input type="hidden" name="TYPE" value="CHANGE_PWD">
				<input type="hidden" name="USER_LOGIN" maxlength="50" value="<?=$arResult['LAST_LOGIN']?>"/>

				<div class="intranet-login-enter-form__login-wrapper">
					<div v-show="isPasswordBlockVisible">
						<div class="intranet-account-card">
							<div class="intranet-account-card__user-wrapper">
								<div class="intranet-account-card__user">
									<span class="intranet-account-card__avatar intranet-account-card__avatar--email"></span>
									<div class="intranet-account-card__details-wrapper">
										<div class="intranet-account-card__login"><?=$arResult['LAST_LOGIN']?></div>
									</div>
								</div>
							</div>
						</div>
						<h4 class="intranet-form-add-block__title intranet-form-add-block__title--margin">
							<?=Loc::getMessage('INTRANET_CHANGE_PASS_CREATE_TITLE')?>
						</h4>

						<?php if ($arResult['USE_PASSWORD']): ?>
							<div class="intranet-text-input intranet-login-enter-form__login">
								<input
									class="ui-ctl-element intranet-text-input__field"
									type="password"
									name="USER_CURRENT_PASSWORD"
									maxlength="255"
									value="<?=$arResult['USER_CURRENT_PASSWORD']?>"
									autocomplete="new-password"
									placeholder="<?=Loc::getMessage('INTRANET_CHANGE_PASS_CURRENT_PASS')?>"
								/>
							</div>
						<?php else: ?>
							<input type="hidden"  name="USER_CHECKWORD" maxlength="50" value="<?=$arResult['USER_CHECKWORD']?>" autocomplete="off"/>
						<?php endif; ?>

						<div class="intranet-text-input intranet-login-enter-form__login">
							<input
								class="ui-ctl-element intranet-text-input__field"
								type="password"
								name="USER_PASSWORD"
								maxlength="255"
								value="<?=$arResult['USER_PASSWORD']?>"
								autocomplete="new-password"
								placeholder="<?=Loc::getMessage('INTRANET_CHANGE_PASS_NEW_PASS')?>"
							/>
						</div>

						<div class="intranet-text-input intranet-login-enter-form__login">
							<input
								class="ui-ctl-element intranet-text-input__field"
								type="password"
								name="USER_CONFIRM_PASSWORD"
								maxlength="255"
								value="<?=$arResult['USER_CONFIRM_PASSWORD']?>"
								autocomplete="new-password"
								placeholder="<?=Loc::getMessage('INTRANET_CHANGE_PASS_CONFIRM')?>"
							/>
						</div>

						<div class="intranet-password-edit-form__indicators-wrapper">
							<div class="intranet-password-indicators__indicator">
								<div class="intranet-password-indicators__icon"></div>
								<?=$arResult['GROUP_POLICY']['PASSWORD_REQUIREMENTS'];?>
							</div>
						</div>
					</div>

					<div v-show="isCaptchaBlockVisible">
					<?php if ($arResult['USE_CAPTCHA']): ?>
						<h4 class="intranet-form-add-block__title">
							<?=Loc::getMessage('INTRANET_CHANGE_PASS_CAPTCHA_PROMT')?>
						</h4>
						<div class="intranet-text-captcha_item">
							<input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE']?>"/>
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
								placeholder="<?=Loc::getMessage('INTRANET_CHANGE_PASS_CAPTCHA_PROMT')?>"
							/>
						</div>
					<?php endif ?>
					</div>
				</div>

				<button
					class="intranet-text-btn ui-btn ui-btn-lg ui-btn-success intranet-text-btn__width"
					type="submit"
					@click="onButtonClick($event)"
				>
					<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_CHANGE_PASS_BUTTON')?></span>
					<div class="intranet-text-btn__spinner" v-show="isWaiting"></div>
				</button>
			</form>
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
	<Teleport to=".intranet-body__header-right">
		<div class="intranet-text-btn intranet-text-btn--auth">
			<a class="intranet-text-btn-link" href="<?=$arResult['AUTH_AUTH_URL']?>" rel="nofollow">
				<?=Loc::getMessage('INTRANET_CHANGE_PASS_AUTH_LINK')?>
			</a>
		</div>
	</Teleport>
</div>

<script>
	BX.ready(() => {
		const params = {
			containerNode: document.querySelector("[data-role='changepassword-container']"),
			isFormVisible: '<?=($arResult['SHOW_FORM'] ? 'Y' : 'N')?>',
			isCaptchaAvailable: '<?=($arResult['USE_CAPTCHA'] ? 'Y' : 'N')?>'
		};
		new BX.Intranet.SystemAuthChangePassword(params);
	});
</script>

