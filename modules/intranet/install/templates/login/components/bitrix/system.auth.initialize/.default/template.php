<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetTitle(GetMessage("CT_MAIN_REG_INIT_TITLE"));?>

<?if($arResult["SHOW_FORM"]):?>
	<form method="post" action="<?echo $arResult["FORM_ACTION"]?>" name="form_auth" enctype="multipart/form-data">
		<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>
		<hr class="b_line_gray">
		<?ShowMessage($arResult["MESSAGE_TEXT"]);?>
		<?=bitrix_sessid_post()?>

		<div class="">
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_LOGIN_TITLE")?>:&nbsp;</span><span class="login-email-text"><?echo htmlspecialcharsbx($arResult["USER"]["LOGIN"])?></span>
			</div>
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_NAME_TITLE")?>:</span><input class="login-inp"  type="text" name="NAME"  maxlength="50" value="<?echo htmlspecialcharsbx($arResult["USER"]["NAME"])?>" size="17"/>
			</div>
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_LAST_NAME_TITLE")?>:</span><input class="login-inp" type="text" name="LAST_NAME" maxlength="50" value="<?echo htmlspecialcharsbx($arResult["USER"]["LAST_NAME"])?>" size="17"/>
			</div>
			<div class="login-item">
				<div style="display: none;"><input type="text" name="LOGIN_PSEUDO" value="<?echo htmlspecialcharsbx($arResult["USER"]["LOGIN"])?>" size="1" readonly /></div>
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_PASSWORD_TITLE")?>:</span><input class="login-inp" type="password" name="PASSWORD"  maxlength="50" value="<?echo $arResult["PASSWORD"]?>" size="12"/>
			</div>
			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_CONFIRM_PASSWORD_TITLE")?>:</span><input class="login-inp" type="password" name="CONFIRM_PASSWORD" maxlength="50" value="<?echo $arResult["CONFIRM_PASSWORD"]?>" size="12"/>
			</div>

			<div class="login-item">
				<span class="login-item-alignment"></span><span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_PERSONAL_PHOTO_TITLE")?>:&nbsp;</span><span
					class="login-file-block">
						<span class="login-file-btn-wrap">
							<input type="file" name="PERSONAL_PHOTO" id="PERSONAL_PHOTO" size="10" class="login-file-inp" onchange="
							<?$browser = IsIE(); if ($browser && $browser < 10):?>
								BX('PERSONAL_PHOTO_NAME').innerHTML = this.value;
							<?else:?>
								files = this.files;
								len = files.length;
								for (var i=0; i < len; i++)
									BX('PERSONAL_PHOTO_NAME').innerHTML = files[i].name;
							<?endif?>
							">
							<span class="login-file-btn" id="PERSONAL_PHOTO_NAME"><?=GetMessage("CT_MAIN_REG_INIT_PERSONAL_PHOTO_DOWNLOAD")?></span>
						</span>
					</span>
			</div>

			<?if (strlen(trim($arResult["CHECKWORD"])) <= 0):?>
				<div class="login-item">
					<span class="login-label"><?echo GetMessage("CT_MAIN_REG_INIT_CHECKWORD_TITLE")?>:</span>
					<input class="login-inp" type="text" name="CHECKWORD" class="auth-input" maxlength="50" value="<?echo $arResult["CHECKWORD"]?>" size="17" />
				</div>
			<?else:?>
				<input type="hidden" name="CHECKWORD" value="<?echo $arResult["CHECKWORD"]?>" />
			<?endif;?>

		</div>

		<div class="login-text login-item">
			<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" checked="checked"/><label class="login-item-checkbox-label" for="USER_REMEMBER"><?=GetMessage("CT_MAIN_REG_INIT_REMEMBER_TITLE")?></label>
			<br/><br/><?=GetMessage("CT_MAIN_REG_INIT_FUTURE_COMMENT")?>
		</div>

		<div class="log-popup-footer">
			<button class="login-btn" value="<?=GetMessage("CT_MAIN_REG_INIT_CONFIRM")?>" onclick="BX.addClass(this, 'wait');"><?=GetMessage("CT_MAIN_REG_INIT_CONFIRM")?></button>
		</div>
		<input type="hidden" name="<?echo $arParams["USER_ID"]?>" value="<?echo $arResult["USER_ID"]?>" />
		<input type="hidden" name="confirm" value="Y" />
	</form>
	<script type="text/javascript">
		BX.ready(function() {
			BX.focus(document.forms["form_auth"]["NAME"]);
			//BX.bind(BX("submit-button"), "click", function() { document.forms["form_auth"].submit(); });
			//BX.bind(document.forms["form_auth"], "keypress", fireEnterKey);
		});
	</script>
<?elseif(!$USER->IsAuthorized()):?>
	<br/>
	<div class="login-text">
	<?echo str_replace("#LINK#", "/", GetMessage("CT_MAIN_REG_INIT_AUTH_LINK"));?>
	</div>
<?endif?>



