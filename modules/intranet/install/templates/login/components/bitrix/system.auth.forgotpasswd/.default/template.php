<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

$forgetLogin = isset($_REQUEST["forgot_login"]) && $_REQUEST["forgot_login"] == "yes" ? true : false;

if ($forgetLogin)
	$APPLICATION->IncludeComponent("bitrix:bitrix24.auth.forgotlogin", "", array());
else
{
?>
	<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
		<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>
		<hr class="b_line_gray">
		<?ShowMessage($arParams["~AUTH_RESULT"]);?>
		<?if (strlen($arResult["BACKURL"]) > 0):?>
			<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
		<?endif?>
		<input type="hidden" name="AUTH_FORM" value="Y">
		<input type="hidden" name="TYPE" value="SEND_PWD">

		<div class="">
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_LOGIN")?></span>
				<input class="login-inp" type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["LAST_LOGIN"]?>"/>&nbsp;<span class="login-label" style="margin-left: -50px"><?=GetMessage("AUTH_OR")?></span>
			</div>
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_EMAIL")?></span>
				<input class="login-inp" type="text" name="USER_EMAIL" maxlength="255" />
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
			<?=GetMessage("AUTH_FORGOT_PASSWORD_1")?>
			<div class="login-links"><a href="<?=$arResult["AUTH_AUTH_URL"]?>"><?=GetMessage("AUTH_AUTH")?></a></div>
		</div>

		<div class="log-popup-footer">
			<button class="login-btn" value="<?=GetMessage("AUTH_GET_CHECK_STRING")?>" onclick="BX.addClass(this, 'wait');"><?=GetMessage("AUTH_GET_CHECK_STRING")?></button>
		</div>
	</form>

	<script type="text/javascript">
		BX.ready(function() {
			BX.focus(document.forms["form_auth"]["USER_LOGIN"]);
		});
	</script>
<?
}
?>
