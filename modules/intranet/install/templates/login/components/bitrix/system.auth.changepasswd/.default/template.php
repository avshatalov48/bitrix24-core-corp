<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<form method="post" action="<?=$arResult["AUTH_FORM"]?>" name="form_auth">
	<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>
	<hr class="b_line_gray">
	<?
	ShowMessage($arParams["~AUTH_RESULT"]);
	?>
	<?if (strlen($arResult["BACKURL"]) > 0): ?>
	<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
	<? endif ?>
	<input type="hidden" name="AUTH_FORM" value="Y">
	<input type="hidden" name="TYPE" value="CHANGE_PWD">
	<div class="">
		<div class="login-item">
			<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_LOGIN")?></span><input class="login-inp" type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["LAST_LOGIN"]?>"/>
		</div>
		<div class="login-item">
			<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_CHECKWORD")?></span><input class="login-inp" type="text"  name="USER_CHECKWORD" maxlength="50" value="<?=$arResult["USER_CHECKWORD"]?>"/>
		</div>
		<div class="login-item">
			<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_NEW_PASSWORD_REQ")?></span><input class="login-inp" type="password" name="USER_PASSWORD" maxlength="50" value="<?=$arResult["USER_PASSWORD"]?>"/>
		</div>
		<div class="login-item">
			<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_NEW_PASSWORD_CONFIRM")?></span><input class="login-inp" type="password"  name="USER_CONFIRM_PASSWORD" maxlength="50" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>"/>
		</div>
<?if ($arResult["USE_CAPTCHA"]):?>
		<div class="login-item">
			<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
			<img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
		</div>
		<div class="login-item">
			<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("AUTH_CAPTCHA_PROMT")?></span>
			<input class="login-inp" type="text" name="captcha_word" maxlength="50" value="" size="15" />
		</div>
<?endif?>
	</div>
	<div class="login-text login-item">
		<?echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?>
		<div class="login-links"><a href="<?=$arResult["AUTH_AUTH_URL"]?>"><?=GetMessage("AUTH_AUTH")?></a></div>
	</div>

	<div class="log-popup-footer">
		<button class="login-btn" value="<?=GetMessage("AUTH_CHANGE")?>" onclick="BX.addClass(this, 'wait');"><?=GetMessage("AUTH_CHANGE")?></button>
	</div>
</form>

<script type="text/javascript">

BX.ready(function() {

	BX.focus(document.forms["form_auth"]["USER_LOGIN"]);
	//BX.bind(BX("submit-button"), "click", function() { document.forms["form_auth"].submit(); });
	//BX.bind(document.forms["form_auth"], "keypress", fireEnterKey);
});
</script>