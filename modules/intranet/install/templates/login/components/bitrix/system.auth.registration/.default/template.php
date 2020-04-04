<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="bx-auth">
<?
if($arResult["USE_EMAIL_CONFIRMATION"] === "Y" && is_array($arParams["AUTH_RESULT"]) &&  $arParams["AUTH_RESULT"]["TYPE"] === "OK")
{
?>
	<p><?echo GetMessage("AUTH_EMAIL_SENT")?></p>
<?
}
else
{
?>
	<?if($arResult["USE_EMAIL_CONFIRMATION"] === "Y"):?>
		<p><?echo GetMessage("AUTH_EMAIL_WILL_BE_SENT")?></p>
	<?endif?>
	<noindex>
	<form method="post" action="<?=$arResult["AUTH_URL"]?>" name="bform">
		<?if (strlen($arResult["BACKURL"]) > 0):?>
			<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
		<?endif?>
			<input type="hidden" name="AUTH_FORM" value="Y" />
			<input type="hidden" name="TYPE" value="REGISTRATION" />

			<div class="log-popup-header"><?=GetMessage("AUTH_REGISTER")?></div>
			<hr class="b_line_gray">
			<?ShowMessage($arParams["~AUTH_RESULT"]);?>

			<div class="">
				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_NAME")?></span>
					<input type="text" name="USER_NAME" maxlength="50" value="<?=$arResult["USER_NAME"]?>" class="login-inp" />
				</div>

				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("AUTH_LAST_NAME")?></span>
					<input type="text" name="USER_LAST_NAME" maxlength="50" value="<?=$arResult["USER_LAST_NAME"]?>" class="login-inp" />
				</div>

				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><span class="starrequired">*</span><?=GetMessage("AUTH_LOGIN_MIN")?></span>
					<input type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["USER_LOGIN"]?>" class="login-inp" />
				</div>

				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><span class="starrequired">*</span><?=GetMessage("AUTH_PASSWORD_REQ")?></span>
					<input type="password" name="USER_PASSWORD" maxlength="50" value="<?=$arResult["USER_PASSWORD"]?>" class="login-inp" />
					<?if($arResult["SECURE_AUTH"]):?>
						<span class="bx-auth-secure" id="bx_auth_secure" title="<?echo GetMessage("AUTH_SECURE_NOTE")?>" style="display:none">
							<div class="bx-auth-secure-icon"></div>
						</span>
						<noscript>
						<span class="bx-auth-secure" title="<?echo GetMessage("AUTH_NONSECURE_NOTE")?>">
							<div class="bx-auth-secure-icon bx-auth-secure-unlock"></div>
						</span>
						</noscript>
						<script type="text/javascript">
						document.getElementById('bx_auth_secure').style.display = 'inline-block';
						</script>
					<?endif?>
				</div>

				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><span class="starrequired">*</span><?=GetMessage("AUTH_CONFIRM")?></span>
					<input type="password" name="USER_CONFIRM_PASSWORD" maxlength="50" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" class="login-inp" />
				</div>

				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><?if($arResult["EMAIL_REQUIRED"]):?><span class="starrequired">*</span><?endif?><?=GetMessage("AUTH_EMAIL")?></span>
					<input type="text" name="USER_EMAIL" maxlength="255" value="<?=$arResult["USER_EMAIL"]?>" class="login-inp" />
				</div>

				<?// ********************* User properties ***************************************************?>
				<?if($arResult["USER_PROPERTIES"]["SHOW"] == "Y"):?>
					<div class="login-item">
						<?=strlen(trim($arParams["USER_PROPERTY_NAME"])) > 0 ? $arParams["USER_PROPERTY_NAME"] : GetMessage("USER_TYPE_EDIT_TAB")?>
					</div>
					<?foreach ($arResult["USER_PROPERTIES"]["DATA"] as $FIELD_NAME => $arUserField):?>
					<div class="login-item">
						<?if ($arUserField["MANDATORY"]=="Y"):?><span class="starrequired">*</span><?endif;
						?><?=$arUserField["EDIT_FORM_LABEL"]?>:</td><td>
							<?$APPLICATION->IncludeComponent(
								"bitrix:system.field.edit",
								$arUserField["USER_TYPE"]["USER_TYPE_ID"],
								array("bVarsFromForm" => $arResult["bVarsFromForm"], "arUserField" => $arUserField, "form_name" => "bform"), null, array("HIDE_ICONS"=>"Y"));?>
					</div>
					<?endforeach;?>
				<?endif;?>
		<?// ******************** /User properties ***************************************************

			/* CAPTCHA */
			if ($arResult["USE_CAPTCHA"] == "Y")
			{
				?>
				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><?=GetMessage("CAPTCHA_REGF_TITLE")?></span>
					<input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>" class="login-inp"/>
					<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
				</div>
				<div class="login-item">
					<span class="login-item-alignment"></span><span class="login-label"><span class="starrequired">*</span><?=GetMessage("CAPTCHA_REGF_PROMT")?>:</span>
					<input type="text" name="captcha_word" maxlength="50" value="" class="login-inp"/>
				</div>
				<?
			}
			/* CAPTCHA */
			?>
		</div>

		<div class="login-text login-item">
			<?echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?>
			<div class="login-links ">
				<a href="<?=$arResult["AUTH_AUTH_URL"]?>" rel="nofollow"><?=GetMessage("AUTH_AUTH")?></a>
			</div>
		</div>

		<div class="log-popup-footer">
			<input type="submit" name="Register" value="<?=GetMessage("AUTH_REGISTER")?>" class="login-btn" onclick="BX.addClass(this, 'wait');"/>
		</div>
	</form>
	</noindex>

	<script type="text/javascript">
	document.bform.USER_NAME.focus();
	</script>
<?
}
?>
</div>