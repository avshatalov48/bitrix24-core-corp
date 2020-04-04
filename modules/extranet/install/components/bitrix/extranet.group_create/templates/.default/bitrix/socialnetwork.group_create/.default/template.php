<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"])>0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if(strlen($arResult["ErrorMessage"])>0)
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}

	if ($arResult["ShowForm"] == "Input")
	{
		?>
		<form method="post" name="form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
			<input type="hidden" value="N" name="GROUP_VISIBLE">
			<input type="hidden" value="N" name="GROUP_OPENED">
			<input type="hidden" value="N" name="GROUP_CLOSED">
			<input type="hidden" value="K" name="GROUP_SPAM_PERMS">
			<table class="sonet-message-form" cellspacing="0" cellpadding="0">
				<tr>
					<th colspan="2"><?= GetMessage("SONET_C8_SUBTITLE") ?></th>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><span class="required-field">*</span><?= GetMessage("SONET_C8_NAME") ?>:</td>
					<td valign="top" width="50%">
						<input type="text" name="GROUP_NAME" style="width:98%" value="<?= $arResult["POST"]["NAME"]; ?>">
					</td>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><span class="required-field">*</span><?= GetMessage("SONET_C8_DESCR") ?>:</td>
					<td valign="top" width="50%"><textarea name="GROUP_DESCRIPTION" style="width:98%" rows="5"><?= $arResult["POST"]["DESCRIPTION"]; ?></textarea></td>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><?= GetMessage("SONET_C8_IMAGE") ?>:</td>
					<td valign="top" width="50%">
						<input name="GROUP_IMAGE_ID" type="file"/><br /><?
						if ($arResult["POST"]["IMAGE_ID_FILE"]):?>
							<input type="checkbox" name="GROUP_IMAGE_ID_DEL" id="GROUP_IMAGE_ID_DEL" value="Y"<?= ($arResult["POST"]["IMAGE_ID_DEL"] == "Y") ? " checked" : ""?>/>
							<label for="GROUP_IMAGE_ID_DEL"><?= GetMessage("SONET_C8_IMAGE_DEL") ?></label> <br /><?
							if (strlen($arResult["POST"]["IMAGE_ID_IMG"]) > 0):?>
								<?=$arResult["POST"]["IMAGE_ID_IMG"];?><br /><?
							endif;
						endif;?>
					</td>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><span class="required-field">*</span><?= GetMessage("SONET_C8_SUBJECT") ?>:</td>
					<td valign="top" width="50%">
						<select name="GROUP_SUBJECT_ID">
							<option value=""><?= GetMessage("SONET_C8_TO_SELECT") ?></option>
							<?foreach ($arResult["Subjects"] as $key => $value):?>
								<option value="<?= $key ?>"<?= ($key == $arResult["POST"]["SUBJECT_ID"]) ? " selected" : "" ?>><?= $value ?></option>
							<?endforeach;?>
						</select>
					</td>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><?= GetMessage("SONET_C8_KEYWORDS") ?>:</td>
					<td valign="top" width="50%">
						<?if (IsModuleInstalled("search")):?>
							<?
							$APPLICATION->IncludeComponent(
								"bitrix:search.tags.input",
								".default",
								Array(
									"NAME" => "GROUP_KEYWORDS",
									"VALUE" => $arResult["POST"]["KEYWORDS"],
									"arrFILTER" => "socialnetwork",
									"PAGE_ELEMENTS" => "10",
									"SORT_BY_CNT" => "Y",
								)
							);
							?>
						<?else:?>
							<input type="text" name="GROUP_KEYWORDS" style="width:98%" value="<?= $arResult["POST"]["KEYWORDS"]; ?>">
						<?endif;?>
					</td>
				</tr>
				<tr>
					<td valign="top" width="50%" align="right"><span class="required-field">*</span><?= GetMessage("SONET_C8_INVITE") ?>:</td>
					<td valign="top" width="50%">
						<select name="GROUP_INITIATE_PERMS">
							<option value=""><?= GetMessage("SONET_C8_TO_SELECT") ?>-</option>
							<?foreach ($arResult["InitiatePerms"] as $key => $value):?>
								<option value="<?= $key ?>"<?= ($key == $arResult["POST"]["INITIATE_PERMS"]) ? " selected" : "" ?>><?= $value ?></option>
							<?endforeach;?>
						</select>
					</td>
				</tr>
			</table>
			<input type="hidden" name="SONET_USER_ID" value="<?= $GLOBALS["USER"]->GetID() ?>">
			<input type="hidden" name="SONET_GROUP_ID" value="<?= $arParams["GROUP_ID"] ?>">
			<?=bitrix_sessid_post()?>
			<br />
			<input type="submit" name="save" value="<?= ($arParams["GROUP_ID"] > 0) ? GetMessage("SONET_C8_DO_EDIT") : GetMessage("SONET_C8_DO_CREATE") ?>">
			<input type="reset" name="cancel" value="<?= GetMessage("SONET_C8_T_CANCEL") ?>" OnClick="window.location='<?= ($arParams["GROUP_ID"] > 0) ? addslashes($arResult["Urls"]["Group"]) : addslashes($arResult["Urls"]["User"]) ?>'">
		</form>
		<?
	}
	else
	{
		?>
		<?if ($arParams["GROUP_ID"] > 0):?>
			<?= GetMessage("SONET_C8_SUCCESS_EDIT") ?>
		<?else:?>
			<?= GetMessage("SONET_C8_SUCCESS_CREATE") ?>
		<?endif;?>
		<br><br>
		<a href="<?= $arResult["Urls"]["NewGroup"] ?>"><?= $arResult["POST"]["NAME"]; ?></a>
		<?
	}
}
?>