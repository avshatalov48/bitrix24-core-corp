<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage("AUTH_TITLE"));
?>
<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>
<hr class="b_line_gray">
<?
ShowMessage($arParams["~AUTH_RESULT"]);
ShowMessage($arResult['ERROR_MESSAGE']);
?>

<?if($arResult["AUTH_SERVICES"]):?>
<div style="margin:30px 0 30px 83px;">
	<?$APPLICATION->IncludeComponent("bitrix:socserv.auth.form", "",
		array(
			"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
			"CURRENT_SERVICE" => $arResult["CURRENT_SERVICE"],
			"AUTH_URL" => $arResult["AUTH_URL"],
			"POST" => $arResult["POST"],
			"SHOW_TITLES" => 'N',
			"FOR_SPLIT" => 'Y',
			"AUTH_LINE" => 'N',
		),
		$component,
		array("HIDE_ICONS"=>"Y")
	);?>
</div>
<?endif?>

<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
	<input type="hidden" name="AUTH_FORM" value="Y" />
	<input type="hidden" name="TYPE" value="AUTH" />
	<?if (strlen($arResult["BACKURL"]) > 0):?>
	<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
	<?endif?>
	<?foreach ($arResult["POST"] as $key => $value):?>
	<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
	<?endforeach?>

	<div class="">
		<div class="login-item">
			<!--[if IE]><span class="login-label"><?=GetMessage("AUTH_LOGIN")?></span><![endif]-->
			<input class="login-inp" type="text" name="USER_LOGIN" placeholder="<?=GetMessage("AUTH_LOGIN")?>" value="<?=$arResult["LAST_LOGIN"]?>" maxlength="255"/>
		</div>
		<div class="login-item">
			<!--[if IE]><span class="login-label"><?=GetMessage("AUTH_PASSWORD")?></span><![endif]-->
			<input class="login-inp" type="password" name="USER_PASSWORD" placeholder="<?=GetMessage("AUTH_PASSWORD")?>" maxlength="255"/>
		</div>
		<?if($arResult["CAPTCHA_CODE"]):?>
			<div class="login-item">
				<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
				<span class="login-label"></span>
				<img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
			</div>
			<div class="login-item">
				<span class="login-label"><?echo GetMessage("AUTH_CAPTCHA_PROMT")?></span>
				<input class="login-inp" type="text" name="captcha_word" maxlength="50" value="" size="15" />
			</div>
		<?endif;?>

		<div class="login-text login-item">
			<?if ($arResult["STORE_PASSWORD"] == "Y"):?>
			<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" /><label class="login-item-checkbox-label" for="USER_REMEMBER"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
			<?endif?>
			<?if($arParams["NOT_SHOW_LINKS"] != "Y" && $arResult["NEW_USER_REGISTRATION"] == "Y" && $arParams["AUTHORIZE_REGISTRATION"] != "Y"):?>
				<noindex>
					<div class="login-links"><a  href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow"><?=GetMessage("AUTH_REGISTER")?></a></div>
				</noindex>
			<?endif?>
		</div>
	</div>
	<div class="log-popup-footer">
		<input type="submit" value="<?=GetMessage("AUTH_AUTHORIZE")?>" class="login-btn" onclick="BX.addClass(this, 'wait');"/>
		<a class="login-link-forgot-pass" href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a>
	</div>
</form>

<script type="text/javascript">
	function fireEnterKey(event)
	{
		event = event || window.event;
		if (event.keyCode != 13)
			return true;

		var src = event.srcElement || event.target;
		if (!src || (src.tagName.toLowerCase() != "textarea"))
		{
			var form = document.forms["form_auth"];
			if (form)
			{
				var password = form.elements["USER_PASSWORD"];
				if (!password || BX.type.isNotEmptyString(password.value))
					form.submit();
			}

			BX.PreventDefault(event);
		}

		return true;
	}

	BX.ready(function() {
		BX.focus(document.forms["form_auth"]["<?=(strlen($arResult["LAST_LOGIN"]) > 0 ? "USER_PASSWORD" : "USER_LOGIN" )?>"]);
		//BX.bind(BX("submit-button"), "click", function(event) {document.forms["form_auth"].submit(); });
		BX.bind(document.forms["form_auth"], "keypress", fireEnterKey);
	});
</script>
