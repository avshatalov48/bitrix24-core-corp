<?php
/**
* Bitrix vars
* @global CMain $APPLICATION
* @param array $arParams
* @param array $arResult
* @param CBitrixComponentTemplate $this
*/

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.icon-set.outline', 'ui.vue3']);

$APPLICATION->SetTitle(Loc::getMessage('INTRANET_AUTH_TITLE'));
?>

<?php if($arResult["SHOW_FORM"]) :?>
<div class="intranet-island" data-role="initialize-container">
	<form method="post" action="<?=$arResult['FORM_ACTION']?>" name="form_auth" enctype="multipart/form-data">
		<input type="hidden" name="<?=$arParams['USER_ID']?>" value="<?=$arResult['USER_ID']?>" />
		<input type="hidden" name="confirm" value="Y" />
		<input type="hidden" name="LOGIN_PSEUDO" value="<?=htmlspecialcharsbx($arResult['USER']['LOGIN'])?>" />
		<input type="hidden" name="CHECKWORD" value="<?=$arResult['CHECKWORD']?>" />
		<?=bitrix_sessid_post()?>

		<div class="intranet-login-enter-form intranet-logging-in__login-form">
			<h2 class="intranet-island-title">
				<?=Loc::getMessage('INTRANET_AUTH_TITLE')?>
			</h2>
			<?php ShowMessage($arResult['MESSAGE_TEXT'] ?? null); ?> <!-- errors -->

			<div class="intranet-login-enter-form__login-wrapper">
				<div class="intranet-text-input intranet-login-enter-form__login">
					<label for="NAME" class="intranet-text-input__label">
						<?=Loc::getMessage('INTRANET_AUTH_EMAIL')?>
					</label>
					<input
						class="ui-ctl-disabled ui-ctl-element  intranet-text-input__field"
						type="text"
						value="<?=htmlspecialcharsbx($arResult['USER']['~LOGIN'])?>"
						disabled
					/>
				</div>
				<div class="intranet-text-input intranet-login-enter-form__login">
					<label for="NAME" class="intranet-text-input__label">
						<?=Loc::getMessage('INTRANET_AUTH_NAME')?>
					</label>
					<input
						class="ui-ctl-element intranet-text-input__field"
						type="text"
						name="NAME"
						maxlength="50"
						value="<?=htmlspecialcharsbx($arResult['USER']['NAME'])?>"
						size="17"
						placeholder="<?=Loc::getMessage('INTRANET_AUTH_NAME')?>"
						ref="modalInput"
						data-testid="user-name"
					/>
				</div>
				<div class="intranet-text-input intranet-login-enter-form__login">
					<label for="LAST_NAME" class="intranet-text-input__label">
						<?=Loc::getMessage('INTRANET_AUTH_LAST_NAME')?>
					</label>
					<input
						class="ui-ctl-element intranet-text-input__field"
						type="text"
						name="LAST_NAME"
						maxlength="50"
						value="<?=htmlspecialcharsbx($arResult['USER']['LAST_NAME'])?>"
						placeholder="<?=Loc::getMessage('INTRANET_AUTH_LAST_NAME')?>"
						size="17"
						data-testid="user-last-name"
					/>
				</div>
				<div class="intranet-text-input intranet-login-enter-form__login">
					<label for="LAST_NAME" class="intranet-text-input__label">
						<?=Loc::getMessage('INTRANET_AUTH_PASSWORD')?>
					</label>
					<i class="ui-icon-set --lock-l intranet-text-input__lock-icon"></i>
					<input
						class="ui-ctl-element intranet-text-input__field intranet-text-input__field--pass"
						type="password"
						name="PASSWORD"
						maxlength="50"
						value="<?=$arResult['PASSWORD']?>"
						size="12"
						placeholder="<?=Loc::getMessage('INTRANET_AUTH_PASSWORD')?>"
						data-testid="user-password"
					/>
				</div>
				<div class="intranet-text-input intranet-login-enter-form__login">
					<label for="LAST_NAME" class="intranet-text-input__label">
						<?=Loc::getMessage('INTRANET_AUTH_CONFIRM_PASSWORD')?>
					</label>
					<i class="ui-icon-set --lock-l intranet-text-input__lock-icon"></i>
					<input
						class="ui-ctl-element intranet-text-input__field intranet-text-input__field--pass"
						type="password"
						name="CONFIRM_PASSWORD"
						maxlength="50"
						value="<?=$arResult['CONFIRM_PASSWORD']?>"
						size="12"
						placeholder="<?=Loc::getMessage('INTRANET_AUTH_CONFIRM_PASSWORD')?>"
						data-testid="user-confirm-password"
					/>
				</div>
			</div>

			<button
				@click="onButtonClick"
				class="intranet-text-btn intranet-text-btn__reg ui-btn ui-btn-lg ui-btn-success"
				type="submit"
			>
				<span class="intranet-text-btn__content-wrapper"><?=Loc::getMessage('INTRANET_AUTH_CONTINUE_BUTTON')?></span>
				<div class="intranet-text-btn__spinner" v-show="isWaiting"></div>
			</button>

			<div class="intranet-base-checkbox intranet-password-enter-form__remember-me">
				<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" checked="checked" class="login-checkbox-user-remember" />
				<label for="USER_REMEMBER" class="login-item-checkbox-label">&nbsp;<?=Loc::getMessage('INTRANET_AUTH_REMEMBER_ME')?></label>
			</div>
		</div>
	</form>
</div>
<script>
	BX.ready(() => {
		const params = {
			containerNode: document.querySelector("[data-role='initialize-container']"),
		};
		new BX.Intranet.SystemAuthInitialize(params);
	});
</script>
<?php elseif(!$USER->IsAuthorized()): ?>
	<?php
	if (in_array('E30', $arResult['MESSAGE_CODE'])):
		$GLOBALS["APPLICATION"]->AuthForm(Loc::getMessage('INTRANET_INIT_USER_ALREADY_EXISTS'));
	else:
	?>
		<div class="intranet-island">
			<?=(str_replace("#LINK#", "/", Loc::getMessage('INTRANET_AUTH_REG_INIT_ERROR')))?>
		</div>
	<?php endif;?>
<?php endif ?>


