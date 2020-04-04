<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if(isset($_GET['coupon'])): ?>
	<div class="content-edit-form-notice-successfully">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('UPDATES_COUPON_SUCCESS')?></span>
	</div>
<?endif;?>
<?if(isset($_GET['activate'])): ?>
	<div class="content-edit-form-notice-successfully">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('UPDATES_ACTIVATE_SUCCESS')?></span>
	</div>
<?endif;?>

<div class="content-edit-form-notice-error" <?if (!$arResult["ERROR"]):?>style="display: none;"<?endif?> id="config_error_block">
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=$arResult["ERROR"]?></span>
</div>

<form name="updatesLicenseForm" id="updatesLicenseForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<?=bitrix_sessid_post();?>

	<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
		<tr>
			<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('UPDATES_LICENSE_TITLE')?></div>
			</td>
		</tr>

		<?if (is_array($arResult["UPDATE_LIST"]) && array_key_exists("CLIENT", $arResult["UPDATE_LIST"])):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_REGISTERED")?></td>
				<td class="content-edit-form-field-input"><?echo $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["NAME"]?></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif;?>

		<?if (is_array($arResult["UPDATE_LIST"]) && array_key_exists("CLIENT", $arResult["UPDATE_LIST"])):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_EDITION")?></td>
				<td class="content-edit-form-field-input"><?echo $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["LICENSE"]?></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SITES")?></td>
				<td class="content-edit-form-field-input">
					<?echo ($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["MAX_SITES"] > 0? $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["MAX_SITES"] : GetMessage("SUP_CHECK_PROMT_2")); ?>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr valign="top">
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_USERS")?></td>
				<td class="content-edit-form-field-input">
					<?
					if ($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["MAX_USERS"] > 0)
					{
						echo $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["MAX_USERS"];
						echo str_replace("#NUM#", CUpdateClient::GetCurrentNumberOfUsers(), GetMessage("SUP_CURRENT_NUMBER_OF_USERS"));
					}
					else
					{
						echo GetMessage("SUP_USERS_IS_NOT_LIMITED");
						echo " ";
						echo str_replace("#NUM#", CUpdateClient::GetCurrentNumberOfUsers(), GetMessage("SUP_CURRENT_NUMBER_OF_USERS1"));
					}
					?>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_ACTIVE_TITLE")?></td>
				<td class="content-edit-form-field-input"><?echo GetMessage("SUP_ACTIVE_PERIOD_TO", array("#DATE_TO#"=>((strlen($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"]) > 0) ? $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"] : "<i>N/A</i>")));?></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif;?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('UPDATES_LICENSE_KEY')?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="SET_LICENSE_KEY" value="<?=$arResult["LICENSE_KEY"]?>" class="content-edit-form-field-input-text"/>
				<br/><br/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center" colspan="3">
				<span class="webform-small-button webform-small-button-accept" onclick="BX.addClass(this, 'webform-small-button-wait webform-small-button-active'); ">
					<input type="submit" name="LICENSE_BUTTON" class="webform-small-button-text" value="<?=GetMessage("UPDATES_LICENSE_SAVE")?>">
				</span>
			</td>
		</tr>
	</table>
</form>
<br/>
<?if ($arResult["NEED_ACTIVATE"]):?>
	<form name="activateLicenseForm" id="activateLicenseForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
		<input type="hidden" name="save_settings" value="true" >
		<?=bitrix_sessid_post();?>

		<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
			<tr>
				<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
					<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('UPDATES_ACTIVATE_LICENSE_TITLE')?></div>
				</td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_NAME')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="NAME" value="<?=(isset($_POST["NAME"]) ? htmlspecialcharsbx($_POST["NAME"]) : "")?>" class="content-edit-form-field-input-text"/>
					<br/><br/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_SITE_URL_NEW')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="SITE_URL" value="<?=(isset($_POST["SITE_URL"]) ? htmlspecialcharsbx($_POST["SITE_URL"]) : "")?>" class="content-edit-form-field-input-text"/>
					<br/><br/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_PHONE')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="PHONE" value="<?=(isset($_POST["PHONE"]) ? htmlspecialcharsbx($_POST["PHONE"]) : "")?>" class="content-edit-form-field-input-text"/>
					<br/><br/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_EMAIL')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="EMAIL" value="<?=(isset($_POST["EMAIL"]) ? htmlspecialcharsbx($_POST["EMAIL"]) : "")?>" class="content-edit-form-field-input-text"/>
					<br/><br/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_CONTACT_PERSON')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="CONTACT_PERSON" value="<?=(isset($_POST["CONTACT_PERSON"]) ? htmlspecialcharsbx($_POST["CONTACT_PERSON"]) : "")?>" class="content-edit-form-field-input-text"/>
					<br/><br/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_CONTACT_EMAIL')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="CONTACT_EMAIL" value="<?=(isset($_POST["CONTACT_EMAIL"]) ? htmlspecialcharsbx($_POST["CONTACT_EMAIL"]) : "")?>" class="content-edit-form-field-input-text"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_CONTACT_PHONE')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="CONTACT_PHONE" value="<?=(isset($_POST["CONTACT_PHONE"]) ? htmlspecialcharsbx($_POST["CONTACT_PHONE"]) : "")?>" class="content-edit-form-field-input-text"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('UPDATES_ACTIVATE_CONTACT_INFO')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="CONTACT_INFO" value="<?=(isset($_POST["CONTACT_INFO"]) ? htmlspecialcharsbx($_POST["CONTACT_INFO"]) : "")?>" class="content-edit-form-field-input-text"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('UPDATES_ACTIVATE_SITE_TEXT')?></td>
				<td class="content-edit-form-field-input">
					<input type="radio" id="GENERATE_USER" name="GENERATE_USER" value="Y" checked/>
					<label for="GENERATE_USER"><?=GetMessage("UPDATES_ACTIVATE_GENERATE_USER")?></label>
					<br/>
					<input type="radio" id="GENERATE_USER_NO" name="GENERATE_USER" value="N"/>
					<label for="GENERATE_USER_NO"><?=GetMessage("UPDATES_ACTIVATE_GENERATE_USER_NO")?></label>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr id="update-act-new">
				<td colspan="3">
					<table>
						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_NAME')?></td>
							<td class="content-edit-form-field-input">
								<input type="text" name="USER_NAME" value="<?=(isset($_POST["USER_NAME"]) ? htmlspecialcharsbx($_POST["USER_NAME"]) : "")?>" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>
						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_LAST_NAME')?></td>
							<td class="content-edit-form-field-input">
								<input type="text" name="USER_LAST_NAME" value="<?=(isset($_POST["USER_LAST_NAME"]) ? htmlspecialcharsbx($_POST["USER_LAST_NAME"]) : "")?>" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>

						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_LOGIN_A')?></td>
							<td class="content-edit-form-field-input">
								<input type="text" name="USER_LOGIN_A" value="<?=(isset($_POST["USER_LOGIN_A"]) ? htmlspecialcharsbx($_POST["USER_LOGIN_A"]) : "")?>" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>

						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_PASSWORD')?></td>
							<td class="content-edit-form-field-input">
								<input type="password" name="USER_PASSWORD" value="" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>

						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_PASSWORD_CONFIRM')?></td>
							<td class="content-edit-form-field-input">
								<input type="password" name="USER_PASSWORD_CONFIRM" value="" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>

						<tr>
							<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_EMAIL')?></td>
							<td class="content-edit-form-field-input">
								<input type="text" name="USER_EMAIL" value="<?=(isset($_POST["USER_EMAIL"]) ? htmlspecialcharsbx($_POST["USER_EMAIL"]) : "")?>" class="content-edit-form-field-input-text"/>
							</td>
							<td class="content-edit-form-field-error"></td>
						</tr>
					</table>
				</td>
			</tr>

			<tr id="update-act-registred" style="display: none">
				<td class="content-edit-form-field-name content-edit-form-field-name-left content-edit-form-field-star"><?=GetMessage('UPDATES_ACTIVATE_USER_LOGIN_A')?></td>
				<td class="content-edit-form-field-input">
					<input type="text" name="USER_LOGIN" value="<?=(isset($_POST["USER_LOGIN"]) ? htmlspecialcharsbx($_POST["USER_LOGIN"]) : "")?>" class="content-edit-form-field-input-text"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center" colspan="3">
					<span class="webform-small-button webform-small-button-accept" onclick="BX.addClass(this, 'webform-small-button-wait webform-small-button-active'); ">
						<input type="submit" name="ACTIVATE_BUTTON" class="webform-small-button-text" value="<?=GetMessage("UPDATES_LICENSE_ACTIVATE")?>">
					</span>
				</td>
			</tr>
		</table>
	</form>
	<br/>
<?endif?>


<form name="updatesCouponForm" id="updatesCouponForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<input type="hidden" name="save_settings" value="true" >
	<?=bitrix_sessid_post();?>

	<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
		<tr>
			<td class="content-edit-form-header" colspan="3" >
				<div class="content-edit-form-header-wrap"><?=GetMessage('UPDATES_COUPON_TITLE')?></div>
			</td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('UPDATES_COUPON_KEY')?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="COUPON" value="" class="content-edit-form-field-input-text"/>
				<br/><br/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center" colspan="3">
				<span class="webform-small-button webform-small-button-accept" onclick="BX.addClass(this, 'webform-small-button-wait webform-small-button-active'); ">
					<input type="submit" name="ACTIVATE_COUPON_BUTTON" class="webform-small-button-text" value="<?=GetMessage("UPDATES_LICENSE_ACTIVATE")?>">
				</span>
			</td>
		</tr>
	</table>
</form>

<script>
	BX.ready(function ()
	{
		BX.Intranet.UpdatesLicense.init();
	});
</script>

