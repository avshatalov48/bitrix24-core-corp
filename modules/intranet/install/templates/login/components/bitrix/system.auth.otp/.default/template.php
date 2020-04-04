<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

if ($arResult['REQUIRED_BY_MANDATORY'] === true) {
	$APPLICATION->IncludeComponent(
		"bitrix:security.auth.otp.mandatory",
		"",
		array(
			"AUTH_LOGIN_URL" => $arResult["~AUTH_LOGIN_URL"],
			"NOT_SHOW_LINKS" => $arParams["NOT_SHOW_LINKS"]
		)
	);
}
elseif (isset($_GET["help"]) && $_GET["help"] == "Y")
{
?>
	<div class="log-popup-header" style="text-align: left; display: inline-block; width:75%"><?=GetMessage("AUTH_OTP_HELP_TITLE")?></div>
	<div class="log-header-additional-wrap" style="width:24%;"><a href="<?=htmlspecialcharsbx($arResult["AUTH_OTP_LINK"])?>" class="log-header-additional-text"><?=GetMessage("AUTH_OTP_BACK")?></a></div>

	<hr class="b_line_gray">
	<div class="login-text">
		<?=GetMessage("AUTH_OTP_HELP_TEXT", array("#PATH#" => $this->GetFolder()))?>
	<div class="log-popup-footer">
		<a href="<?=htmlspecialcharsbx($arResult["AUTH_OTP_LINK"])?>" class="login-tr-btn"><?=GetMessage("AUTH_OTP_BACK")?></a>
	</div>
</div>
<?
}
else
{
?>
<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
	<input type="hidden" name="AUTH_FORM" value="Y" />
	<input type="hidden" name="TYPE" value="OTP" />

	<div class="log-popup-header" style="text-align: left; display: inline-block; width:49%"><?=GetMessage("AUTH_OTP_PLEASE_AUTH")?></div>
	<?if ($arParams["NOT_SHOW_LINKS"] != "Y"):?>
		<div class="log-header-additional-wrap" style="width:50%;">
			<noindex>
				<?if (!IsModuleInstalled("bitrix24")):?><a href="<?=$arResult["AUTH_LOGIN_URL"]?>" rel="nofollow" class="log-header-additional-text"><?echo GetMessage("AUTH_OTP_AUTH_BACK")?></a><?endif?>
			</noindex>
		</div>
	<?endif?>

	<hr class="b_line_gray">
	<?
	ShowMessage($arParams["~AUTH_RESULT"]);
	?>
	<div class="">
		<div class="login-item">
			<span class="login-item-alignment"></span>
			<span class="login-label"><?=GetMessage("AUTH_OTP_OTP")?>:</span>
			<input type="text" name="USER_OTP" class="login-inp" maxlength="50" value="" autocomplete="off" />
		</div>

		<?if($arResult["CAPTCHA_CODE"]):?>
		<div class="login-item">
			<span class="login-label"><?echo GetMessage("AUTH_OTP_CAPTCHA_PROMT")?>:</span><br/>
			<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
			<input class="login-inp" style="width:40%; display:inline-block" type="text" name="captcha_word" maxlength="50" value="" size="15" />
			<div style="display:inline-block; vertical-align: middle;margin-left:10px" ><img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" /></div>
		</div>
		<?endif;?>
	</div>

	<div class="login-text login-item">
		<?if($arResult["REMEMBER_OTP"]):?>
			<input type="checkbox" id="OTP_REMEMBER" name="OTP_REMEMBER" value="Y" /><label for="OTP_REMEMBER">&nbsp;<?=GetMessage("AUTH_OTP_REMEMBER_ME")?></label>
		<?endif?>
	</div>
	<div class="log-popup-footer">
		<input type="submit" name="Otp" class="login-btn" value="<?=GetMessage("AUTH_OTP_AUTHORIZE")?>" onclick="BX.addClass(this, 'wait');"/>
		<a class="login-link-forgot-pass" href="<?=htmlspecialcharsbx($arResult["AUTH_OTP_HELP_LINK"])?>"><?=GetMessage("AUTH_OTP_HELP_LINK")?></a>
	</div>
</form>

<script type="text/javascript">
	try{document.form_auth.USER_OTP.focus();}catch(e){}
</script>
<?
}
?>


