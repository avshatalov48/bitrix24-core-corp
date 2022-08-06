<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="login-inner --full-width">
	<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>
	<hr class="b_line_gray">
	<?
	ShowMessage($arParams["~AUTH_RESULT"]);
	?>

	<?if($arResult["SHOW_FORM"]):?>

		<form method="post" action="<?=$arResult["AUTH_URL"]?>" name="form_auth">
			<?if ($arResult["BACKURL"] <> ''): ?>
				<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
			<? endif ?>
			<input type="hidden" name="AUTH_FORM" value="Y">
			<input type="hidden" name="TYPE" value="CHANGE_PWD">
			<div class="">
				<div class="login-item">
					<span class="login-label"><?=GetMessage("AUTH_LOGIN")?></span><input class="login-inp" type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["LAST_LOGIN"]?>"/>
				</div>
				<?if($arResult["USE_PASSWORD"]):?>
					<div class="login-item">
						<span class="login-label"><?echo GetMessage("auth_change_pass_current_pass")?></span><input class="login-inp" type="password"  name="USER_CURRENT_PASSWORD" maxlength="255" value="<?=$arResult["USER_CURRENT_PASSWORD"]?>" autocomplete="new-password" />
					</div>
				<?else:?>
					<div class="login-item">
						<span class="login-label"><?=GetMessage("AUTH_CHECKWORD")?></span><input class="login-inp" type="text"  name="USER_CHECKWORD" maxlength="50" value="<?=$arResult["USER_CHECKWORD"]?>" autocomplete="off"/>
					</div>
				<?endif;?>
				<div class="login-item">
					<span class="login-label"><?=GetMessage("AUTH_NEW_PASSWORD_REQ")?></span><input class="login-inp" type="password" name="USER_PASSWORD" maxlength="255" value="<?=$arResult["USER_PASSWORD"]?>" autocomplete="new-password" />
				</div>
				<div class="login-item">
					<span class="login-label"><?=GetMessage("AUTH_NEW_PASSWORD_CONFIRM")?></span><input class="login-inp" type="password"  name="USER_CONFIRM_PASSWORD" maxlength="255" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" autocomplete="new-password" />
				</div>
				<?if ($arResult["USE_CAPTCHA"]):?>
					<div class="login-item">
						<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
					</div>
					<div class="login-item">
						<span class="login-label"><?echo GetMessage("AUTH_CAPTCHA_PROMT")?></span>
						<input class="login-inp" type="text" name="captcha_word" maxlength="50" value="" size="15" autocomplete="off" />
					</div>
				<?endif?>
			</div>
			<div class="login-text login-item">
				<?echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?>
			</div>

			<div class="log-popup-footer">
				<button class="login-btn" value="<?=GetMessage("AUTH_CHANGE")?>" onclick="BX.addClass(this, 'wait');"><?=GetMessage("AUTH_CHANGE")?></button>
			</div>
		</form>

		<script type="text/javascript">

			BX.ready(function() {

				BX.focus(document.forms["form_auth"]["USER_LOGIN"]);
			});
		</script>

	<?endif;?>

	<div class="login-links"><a href="<?=$arResult["AUTH_AUTH_URL"]?>"><?=GetMessage("AUTH_AUTH")?></a></div>
</div>
