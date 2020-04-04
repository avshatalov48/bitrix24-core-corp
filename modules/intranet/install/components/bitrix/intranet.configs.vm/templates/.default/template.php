<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?/*if (!$arResult["IS_SCALE_AVAILABLE"]):?>

	<?return;?>
<?endif*/?>

<?if(isset($_GET['success'])): ?>
	<div class="content-edit-form-notice-successfully">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('CONFIG_VM_SAVE_SUCCESSFULLY')?></span>
	</div>
<?endif;?>
<div class="content-edit-form-notice-error" <?if (!$arResult["ERROR"]):?>style="display: none;"<?endif?> id="CONFIG_VM_error_block">
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=$arResult["ERROR"]?></span>
</div>

<form name="configPostForm" id="configPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<input type="hidden" name="save_settings" value="true" >
	<?=bitrix_sessid_post();?>

	<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
		<?
	if ($arResult["IS_SCALE_AVAILABLE"])
	{
		?>
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_VM_SMTP_TITLE')?></div>
			</td>
		</tr>
		<? if (!$arResult["IS_BXENV_CORRECT_VERSION"]):?>
		<tr>
			<td class="content-edit-form-field-input" colspan="3">
				<div class="CONFIG_VM_notify_message" style="margin: 10px 0 10px 20px;"><?=GetMessage("CONFIG_VM_BXENV_UPDATE")?>
				<a href="javascript:void(0)" onclick="BX.Helper.show('redirect=detail&HD_ID=<?=GetMessage("CONFIG_VM_BXENV_UPDATE_ARTICLE_ID")?>');"><?=GetMessage("CONFIG_VM_MORE")?></a></div>
			</td>
		</tr>
		<?else:?>

		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_HOST')?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="smtp_host" value="<?=isset($arResult["SCALE_SMTP_INFO"]["SMTP_HOST"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["SMTP_HOST"]) : ""?>"  class="content-edit-form-field-input-text" placeholder="smtp.example.com"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_PORT')?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="smtp_port" value="<?=isset($arResult["SCALE_SMTP_INFO"]["SMTP_PORT"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["SMTP_PORT"]) : ""?>"  class="content-edit-form-field-input-text" placeholder="25"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_EMAIL')?></td>
			<td class="content-edit-form-field-input">
				<input type="test" name="smtp_email" value="<?=isset($arResult["SCALE_SMTP_INFO"]["EMAIL"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["EMAIL"]) : ""?>"  class="content-edit-form-field-input-text" placeholder="info@example.com"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><label for="smtp_tls"><?=GetMessage('CONFIG_VM_SMTP_TLS')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" name="smtp_tls" id="smtp_tls" value="Y" <?if ($arResult["SCALE_SMTP_INFO"]["SMTPTLS"] == "Y"):?>checked<?endif;?> />
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><label for="smtp_use_auth"><?=GetMessage('CONFIG_VM_SMTP_AUTH')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" name="smtp_use_auth" id="smtp_use_auth" value="Y" <?if ($arResult["SCALE_SMTP_INFO"]["SMTP_USE_AUTH"] == "Y"):?>checked<?endif;?> />
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-role="smtp-auth" <?if ($arResult["SCALE_SMTP_INFO"]["SMTP_USE_AUTH"] != "Y"):?>style="display: none"<?endif?>>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_USER')?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="smtp_user" value="<?=isset($arResult["SCALE_SMTP_INFO"]["SMTP_USER"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["SMTP_USER"]) : ""?>"  class="content-edit-form-field-input-text"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-role="smtp-auth" <?if ($arResult["SCALE_SMTP_INFO"]["SMTP_USE_AUTH"] != "Y"):?>style="display: none"<?endif?>>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_PASSWORD')?></td>
			<td class="content-edit-form-field-input">
				<input type="password" name="smtp_password" value="<?=isset($arResult["SCALE_SMTP_INFO"]["SMTP_PASSWORD"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["SMTP_PASSWORD"]) : ""?>"  class="content-edit-form-field-input-text"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-role="smtp-auth" <?if ($arResult["SCALE_SMTP_INFO"]["SMTP_USE_AUTH"] != "Y"):?>style="display: none"<?endif?>>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_VM_SMTP_REPEAT_PASSWORD')?></td>
			<td class="content-edit-form-field-input">
				<input type="password" name="smtp_repeat_password" value="<?=isset($arResult["SCALE_SMTP_INFO"]["SMTP_PASSWORD"]) ? htmlspecialcharsbx($arResult["SCALE_SMTP_INFO"]["SMTP_PASSWORD"]) : ""?>"  class="content-edit-form-field-input-text"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center" colspan="3">
			<span onclick="BX.Bitrix24.Configs.Vm.submitForm(this)" class="webform-button webform-button-create">
				<?=GetMessage("CONFIG_VM_SAVE")?>
			</span>
			</td>
		</tr>

		<!-- CERTIFICATE -->
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_VM_CERTIFICATE_TITLE')?></div>
			</td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input">
				<a href="javascript:void(0)" id="certificate_lets_encrypt"><?=GetMessage("CONFIG_VM_CERTIFICATE_LETS_ENCRYPT_CONF")?></a>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input">
				<a href="javascript:void(0)" id="certificate_self"><?=GetMessage("CONFIG_VM_CERTIFICATE_SELF_CONF")?></a>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<?	endif;
	}
	?>
	</table>
</form>

<div style="display: none" id="lets_encrypt_popup_content">
	<?=GetMessage("CONFIG_VM_CERTIFICATE_LETS_ENCRYPT_EMAIL")?>
	<br/>
	<input type="text"
		name="certificate_lets_email"
		id="certificate_lets_email"
		value="<?=$arResult["SCALE_CERTIFICATE_INFO"]["EMAIL"]?>"
		class="config-vm-popup-input"
	/>
	<br/><br/>
	<?=GetMessage("CONFIG_VM_CERTIFICATE_LETS_ENCRYPT_DNS")?>
	<br/>
	<input type="text"
		name="certificate_lets_dns"
		id="certificate_lets_dns"
		value="<?=$arResult["SCALE_CERTIFICATE_INFO"]["DNS"]?>"
		class="config-vm-popup-input"
	/>
</div>

<div style="display: none" id="self_certificate_popup_content">
	<form id="self_certificate_form" method="POST" action="<?=POST_FORM_ACTION_URI/*$this->GetFolder()."/ajax.php"*/?>" enctype="multipart/form-data">
		<?=bitrix_sessid_post();?>
		<input type="hidden" name="action" value="upload_files">

		<?=GetMessage("CONFIG_VM_CERTIFICATE_SELF_KEY_PATH")?>
		<br/>
		<input type="text"
			   name="certificate_self_key_path"
			   id="certificate_self_key_path"
			   value="<?=$arResult["SCALE_CERTIFICATE_INFO"]["PRIVATE_KEY_PATH"]?>"
			   class="config-vm-popup-input"
			   style="width: 67%;"
		/>
		<label for="certificate_self_key_path_file" class="config-webform-field-upload">
			<span class="webform-small-button"><?=GetMessage('CONFIG_VM_UPLOAD')?></span>
			<input type="file" name="certificate_self_key_path_file" id="certificate_self_key_path_file" value=""/>
		</label>

		<br/>
		<?=GetMessage("CONFIG_VM_CERTIFICATE_SELF_PATH")?>
		<br/>
		<input type="text"
			   name="certificate_self_path"
			   id="certificate_self_path"
			   value="<?=$arResult["SCALE_CERTIFICATE_INFO"]["CERTIFICATE_PATH"]?>"
			   class="config-vm-popup-input"
			   style="width: 67%;"
		/>
		<label for="certificate_self_path_file" class="config-webform-field-upload">
			<span class="webform-small-button"><?=GetMessage('CONFIG_VM_UPLOAD')?></span>
			<input type="file" name="certificate_self_path_file" id="certificate_self_path_file" value=""/>
		</label>
		<br/>
		<?=GetMessage("CONFIG_VM_CERTIFICATE_SELF_PATH_CHAIN")?>
		<br/>
		<input type="text"
			   name="certificate_self_path_chain"
			   id="certificate_self_path_chain"
			   value="<?=$arResult["SCALE_CERTIFICATE_INFO"]["CERTIFICATE_PATH_CHAIN"]?>"
			   class="config-vm-popup-input"
			   style="width: 67%;"
		/>
		<label for="certificate_self_path_chain_file" class="config-webform-field-upload">
			<span class="webform-small-button"><?=GetMessage('CONFIG_VM_UPLOAD')?></span>
			<input type="file" name="certificate_self_path_chain_file" id="certificate_self_path_chain_file" value=""/>
		</label>
	</form>
</div>

<?
$arJSParams = array(
	"ajaxPath" => $this->GetFolder()."/ajax.php",
	"siteNameConf" => $arResult["SITE_NAME_CONF"]
);
?>
<script>
	BX.message({
		CONFIG_VM_CERTIFICATE_TITLE: "<?=GetMessageJS("CONFIG_VM_CERTIFICATE_TITLE")?>",
		CONFIG_VM_START: "<?=GetMessageJS("CONFIG_VM_START")?>",
		CONFIG_VM_CERTIFICATE_PROCESS: "<?=GetMessageJS("CONFIG_VM_CERTIFICATE_PROCESS")?>"
	});

	BX.ready(function(){
		BX.Bitrix24.Configs.Vm.init(<?=CUtil::PhpToJSObject($arJSParams)?>);
	});
</script>