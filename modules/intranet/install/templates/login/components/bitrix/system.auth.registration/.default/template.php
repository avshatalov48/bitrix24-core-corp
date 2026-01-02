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

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.icon-set.outline', 'ui.hint', 'ui.vue3']);

$isConfirmRegistrationBlockVisible = (
	$arResult['USE_EMAIL_CONFIRMATION'] === 'Y'
	&& is_array($arParams['AUTH_RESULT'])
	&&  $arParams['AUTH_RESULT']['TYPE'] === 'OK'
);
?>

<div class="intranet-island" data-role="register-container">
	<template v-if="isConfirmRegistrationBlockVisible">
		<div class="intranet-notification">
			<h2 class="intranet-island-title">
				<span><?=Loc::getMessage('INTRANET_AUTH_CHECK_EMAIL')?></span>
			</h2>
			<div class="intranet-big-icon intranet-big-icon--email intranet-notification__icon"></div>
			<div class="intranet-notification__content">
				<?=Loc::getMessage('INTRANET_AUTH_EMAIL_SENT')?>
			</div>
		</div>
	</template>

	<template v-else>
		<noindex>
		<form method="post" action="<?=$arResult['AUTH_URL']?>" name="bform">
			<?php if ($arResult['BACKURL'] <> ''): ?>
			<input type="hidden" name="backurl" value="<?=$arResult['BACKURL']?>" />
			<?php endif ?>
			<input type="hidden" name="AUTH_FORM" value="Y" />
			<input type="hidden" name="TYPE" value="REGISTRATION" />
			<input type="hidden" name="Register" value="Y" />

			<div v-show="isBackButtonVisible" class="intranet-back-button">
				<div @click="onBackButtonClick" class="intranet-back-button__icon"></div>
			</div>

			<div class="intranet-login-enter-form intranet-logging-in__login-form">
				<h3 class="intranet-reg-title" v-show="!isCaptchaBlockVisible">
					<?=Loc::getMessage('INTRANET_AUTH_JOIN_TEXT')?>
					<b><?=($arParams['HOST_NAME'] ?? '')?></b>
				</h3>
				<?php ShowMessage($arParams['~AUTH_RESULT']); ?> <!-- errors -->
				<div class="intranet-login-enter-form__login-wrapper">
					<div v-show="isNameBlockVisible">
						<h4 class="intranet-form-add-block__title">
							<?=Loc::getMessage('INTRANET_AUTH_USER_DATA_TITLE')?>
						</h4>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<label for="USER_EMAIL" class="intranet-text-input__label">
								<?=Loc::getMessage('INTRANET_AUTH_EMAIL')?>
								<?php if($arResult['EMAIL_REQUIRED']): ?><?=Loc::getMessage('INTRANET_AUTH_REQUIRED_FIELD')?><?php endif ?>
							</label>
							<input
								type="text"
								name="USER_EMAIL"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_EMAIL_PLACEHOLDER')?>"
								maxlength="255"
								value="<?=$arResult['USER_EMAIL']?>"
								class="ui-ctl-element intranet-text-input__field"
								@input="onEnterUserEmail($event.target.value)"
								ref="modalInput"
								data-testid="user-email"
							/>
						</div>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<label for="USER_LOGIN" class="intranet-text-input__label">
								<?=Loc::getMessage('INTRANET_AUTH_LOGIN')?>
								<?=Loc::getMessage('INTRANET_AUTH_REQUIRED_FIELD')?>
							</label>
							<input
								type="text"
								name="USER_LOGIN"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_LOGIN_PLACEHOLDER')?>"
								maxlength="50"
								value="<?=$arResult['USER_LOGIN']?>"
								class="ui-ctl-element intranet-text-input__field"
								@input="onEnterUserLogin($event.target.value)"
								data-testid="user-login"
							/>
							<i
								class="ui-icon-set --o-info-circle intranet-text-input__info-icon"
								data-hint="<?=Loc::getMessage('INTRANET_AUTH_LOGIN_HINT')?>"
								data-hint-html
								data-hint-no-icon="true"
							></i>
						</div>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<label for="USER_NAME" class="intranet-text-input__label">
								<?=Loc::getMessage('INTRANET_AUTH_NAME')?>
							</label>
							<input
								type="text"
								name="USER_NAME"
								maxlength="50"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_NAME_PLACEHOLDER')?>"
								value="<?=$arResult['USER_NAME']?>"
								class="ui-ctl-element intranet-text-input__field"
								@input="onEnterUserName($event.target.value)"
								data-testid="user-name"
							/>
						</div>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<label for="USER_LAST_NAME" class="intranet-text-input__label">
								<?=Loc::getMessage('INTRANET_AUTH_LAST_NAME')?>
							</label>
							<input
								type="text"
								name="USER_LAST_NAME"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_LAST_NAME_PLACEHOLDER')?>"
								maxlength="50"
								value="<?=$arResult['USER_LAST_NAME']?>"
								class="ui-ctl-element intranet-text-input__field"
								@input="onEnterUserLastName($event.target.value)"
								data-testid="user-last-name"
							/>
						</div>
					</div>

					<div v-show="isPasswordBlockVisible">
						<div class="intranet-account-card">
							<div class="intranet-account-card__user-wrapper">
								<div class="intranet-account-card__user">
									<span class="intranet-account-card__avatar intranet-account-card__avatar--email"></span>
									<div class="intranet-account-card__details-wrapper">
										<div v-if="fullName" class="intranet-account-card__full-name">{{ fullName }}</div>
										<div class="intranet-account-card__login">{{ email }}</div>
									</div>
								</div>
							</div>
						</div>

						<h4 class="intranet-form-add-block__title intranet-form-add-block__title--margin">
							<?=Loc::getMessage('INTRANET_AUTH_PASSWORD_TITLE')?>
						</h4>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<i class="ui-icon-set --lock-l intranet-text-input__lock-icon"></i>
							<input
								:type="inputPasswordType"
								name="USER_PASSWORD"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_PASSWORD')?>"
								maxlength="255"
								value="<?=$arResult['USER_PASSWORD']?>"
								class="ui-ctl-element intranet-text-input__field intranet-text-input__field--pass"
								data-testid="user-password"
							/>
							<i
								class="ui-icon-set --opened-eye intranet-text-input__eye-icon"
								@mousedown="onEyeMouseDown('PASSWORD')"
								@mouseup="onEyeMouseUp('PASSWORD')"
							></i>
						</div>
						<div class="intranet-text-input intranet-login-enter-form__login">
							<i class="ui-icon-set --lock-l intranet-text-input__lock-icon"></i>
							<input
								:type="inputConfirmPasswordType"
								name="USER_CONFIRM_PASSWORD"
								placeholder="<?=Loc::getMessage('INTRANET_AUTH_CONFIRM')?>"
								maxlength="255"
								value="<?=$arResult['USER_CONFIRM_PASSWORD']?>"
								class="ui-ctl-element intranet-text-input__field intranet-text-input__field--pass"
								data-testid="user-confirm-password"
							/>
							<i
								class="ui-icon-set --opened-eye intranet-text-input__eye-icon"
								@mousedown="onEyeMouseDown('CONFIRM_PASSWORD')"
								@mouseup="onEyeMouseUp('CONFIRM_PASSWORD')"
							></i>
						</div>
						<div class="intranet-password-edit-form__indicators-wrapper">
							<div class="intranet-password-indicators__indicator">
								<div class="intranet-password-indicators__icon"></div>
								<?=$arResult['GROUP_POLICY']['PASSWORD_REQUIREMENTS'];?>
							</div>
						</div>

						<?php if($arResult['USER_PROPERTIES']['SHOW'] === 'Y'): ?>
							<h4 class="intranet-form-add-block__title intranet-form-add-block__title--margin">
								<?=trim($arParams['USER_PROPERTY_NAME']) ?: Loc::getMessage('INTRANET_USER_PROPERTIES_TITLE')?>
							</h4>
							<?php foreach ($arResult['USER_PROPERTIES']['DATA'] as $FIELD_NAME => $arUserField): ?>
								<div class="intranet-text-input intranet-login-enter-form__login">
									<?php if ($arUserField['MANDATORY'] === 'Y'):?>*<?php endif; ?>&nbsp;<?=$arUserField['EDIT_FORM_LABEL']?>:
									<?php $APPLICATION->IncludeComponent(
										'bitrix:system.field.edit',
										$arUserField['USER_TYPE']['USER_TYPE_ID'],
										[
											'bVarsFromForm' => $arResult['bVarsFromForm'],
											'arUserField' => $arUserField,
											'form_name' => 'bform'
										],
										null,
										[ 'HIDE_ICONS' => 'Y' ]
									);?>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<div v-show="isCaptchaBlockVisible">
						<?php if ($arResult['USE_CAPTCHA'] === 'Y'): ?>
							<h4 class="intranet-form-add-block__title">
								<?=Loc::getMessage('INTRANET_AUTH_CAPTCHA_PROMT')?>
							</h4>
							<div class="intranet-text-captcha_item">
								<input type="hidden" name="captcha_sid" value="<?=$arResult['CAPTCHA_CODE']?>" class="login-inp"/>
								<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult['CAPTCHA_CODE']?>" width="180" height="40" alt="CAPTCHA" />
							</div>
							<div class="intranet-text-input intranet-login-enter-form__login">
								<input
									type="text"
									name="captcha_word"
									placeholder="<?=Loc::getMessage('INTRANET_AUTH_CAPTCHA_PROMT')?>"
									maxlength="50"
									value=""
									class="ui-ctl-element intranet-text-input__field"
								/>
							</div>
						<?php endif ?>
					</div>
				</div>

				<button
					@click="onButtonClick($event)"
					class="intranet-text-btn intranet-text-btn__reg ui-btn ui-btn-lg"
					:class="activeOrDisabledButtonClass"
					type="submit"
				>
					<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_AUTH_CONTINUE_BUTTON')?></span>
					<div class="intranet-text-btn__spinner" v-show="isWaiting"></div>
				</button>
			</div>
		</form>
		</noindex>
	</template>

	<Teleport to=".intranet-body__header-right">
		<div class="intranet-text-btn intranet-text-btn--auth">
			<a class="intranet-text-btn-link" href="<?=$arResult['AUTH_AUTH_URL']?>" rel="nofollow"><?=Loc::getMessage('INTRANET_AUTH_LINK')?></a>
		</div>
	</Teleport>
</div>

<script>
	BX.ready(() => {
		const params = {
			containerNode: document.querySelector("[data-role='register-container']"),
			isEmailRequired: '<?=$arResult['EMAIL_REQUIRED'] ? 'Y' : 'N'?>',
			isConfirmRegistrationBlockVisible: '<?=$isConfirmRegistrationBlockVisible ? 'Y' : 'N'?>',
			isCaptchaAvailable: '<?=($arResult['USE_CAPTCHA'] === 'Y' ? 'Y' : 'N')?>',
			userName: '<?=($arResult['USER_NAME'] ?: '')?>',
			userLastName: '<?=($arResult['USER_LAST_NAME'] ?: '')?>',
			userLogin: '<?=($arResult['USER_LOGIN'] ?: '')?>',
			userEmail: '<?=($arResult['USER_EMAIL'] ?: '')?>',
		};
		new BX.Intranet.SystemAuthRegistration(params);
	});
</script>
