<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<?$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "invite-user");?>
<script>
	var pullParams = {
		enable:true,
		pulltext:"<?=GetMessage("PULL_TEXT");?>",
		downtext:"<?=GetMessage("DOWN_TEXT");?>",
		loadtext:"<?=GetMessage("LOAD_TEXT");?>",
	};
	if(app.enableInVersion(2))
		pullParams.action = "RELOAD";
	else
		pullParams.callback = function(){document.location.reload();}
	app.pullDown(pullParams);
</script>

<?if ($arResult["ERRORS"]):?>
<script>
	alert("<?=$arResult["ERRORS"];?>");
</script>
<?endif?>

<h3 class="invite-uset-title"><?=GetMessage("BX24_INVITE_DIALOG_INVITE_TITLE")?></h3>
<hr class="invite-user-hr" />

<?if ($arResult["SUCCESS"] == "Y"):?>
<div class="invite-user-label" id="success_info">
	<?=GetMessage("BX24_INVITE_DIALOG_INVITED");?>
	<div class="invite-user-button-wrap">
		<a href="javascript:void(0)" class="button emp-info-button accept-button invite-user-button" onclick="BX('INVITE_DIALOG_FORM').style.display='block';BX('success_info').style.display='none'" ontouchstart="BX.toggleClass(this, 'accept-button-press');" ontouchend="BX.toggleClass(this, 'accept-button-press');"><?=GetMessage("BX24_INVITE_DIALOG_INVITE_MORE")?></a>
	</div>
</div>
<?endif?>

<form method="POST" action="<?=POST_FORM_ACTION_URI?>" id="INVITE_DIALOG_FORM" <?if ($arResult["SUCCESS"] == "Y"):?>style="display:none"<?endif?>>
	<table width="100%" cellpadding="5">
		<tr valign="bottom">
			<td colspan="2">
				<div><label for="EMAIL" class="invite-user-label"><?echo GetMessage("BX24_INVITE_DIALOG_EMAIL")?></label></div>
				<textarea rows="5" name="EMAIL" id="EMAIL" class="invite-user-textarea"><?if ($arResult["SUCCESS"] != "Y") echo htmlspecialcharsbx($_POST["EMAIL"])?></textarea>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="2">
				<div><label for="MESSAGE_TEXT" class="invite-user-label"><?echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TITLE")?></label></div>
				<textarea rows="5" name="MESSAGE_TEXT" id="MESSAGE_TEXT" class="invite-user-textarea"><?
					if (isset($_POST["MESSAGE_TEXT"]))
						echo htmlspecialcharsbx($_POST["MESSAGE_TEXT"]);
					elseif ($userMessage = CUserOptions::GetOption("bitrix24", "invite_message_text"))
						echo $userMessage;
					else
						echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
				?></textarea>
			</td>
		</tr>
	</table>
	<?echo bitrix_sessid_post()?>
	<div class="invite-user-button-wrap">
		<a href="javascript:void(0)" onclick="BX('INVITE_DIALOG_FORM').submit()" class="button emp-info-button accept-button invite-user-button" ontouchstart="BX.toggleClass(this, 'accept-button-press');" ontouchend="BX.toggleClass(this, 'accept-button-press');"><?=GetMessage("BX24_INVITE_DIALOG_INVITE")?></a>
	</div>
</form>

