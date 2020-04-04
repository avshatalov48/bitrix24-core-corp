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
		<table>
		<tr>
			<td valign="top" width="75%">
				<form method="post" name="sonet_form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
					<table class="sonet-message-form data-table" cellspacing="0" cellpadding="0">
						<tr>
							<th colspan="3"><?= GetMessage("SONET_C11_SUBTITLE") ?></th>
						</tr>
						<tr>
							<td valign="top" width="10%" align="right" nowrap><span class="required-field">*</span><?= GetMessage("SONET_C11_USER") ?>:</td>
							<td valign="top">
							<?

							if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
								$bExtranet = true;

							if ($arResult["isCurrentUserIntranet"])
							{
								if ($bExtranet)
									echo "<p>".GetMessage("SONET_C11_USER_INTRANET")."<br>";

								$ControlID = $APPLICATION->IncludeComponent('bitrix:intranet.user.selector', '', array(
									'INPUT_NAME' => $arParams["IUS_INPUT_NAME"],
									'INPUT_NAME_STRING' => $arParams["IUS_INPUT_NAME_STRING"],
									'INPUT_NAME_SUSPICIOUS' => $arParams["IUS_INPUT_NAME_SUSPICIOUS"],
									'TEXTAREA_MIN_HEIGHT' => 50,
									'TEXTAREA_MAX_HEIGHT' => 150,
									'INPUT_VALUE_STRING' => $_REQUEST[$arParams["IUS_INPUT_NAME_STRING"]],
									'EXTERNAL' => 'I'
									)
								);
							}

							if ($bExtranet)
							{
								if ($arResult["isCurrentUserIntranet"])
								{
									$ExtranetUserFilter = "EA";
									echo "<p>".GetMessage("SONET_C11_USER_EXTRANET")."<br>";
								}
								else
									$ExtranetUserFilter = "E";

								$APPLICATION->IncludeComponent('bitrix:intranet.user.selector', '', array(
									'INPUT_NAME' => $arParams["IUS_INPUT_NAME_EXTRANET"],
									'INPUT_NAME_STRING' => $arParams["IUS_INPUT_NAME_STRING_EXTRANET"],										
									'INPUT_NAME_SUSPICIOUS' => $arParams["IUS_INPUT_NAME_SUSPICIOUS_EXTRANET"],
									'TEXTAREA_MIN_HEIGHT' => 50,
									'TEXTAREA_MAX_HEIGHT' => 150,
									'INPUT_VALUE_STRING' => $_REQUEST[$arParams["IUS_INPUT_NAME_STRING_EXTRANET"]],
									'EXTERNAL' => $ExtranetUserFilter
									)
								);
								echo GetMessage("SONET_C11_EMAIL");
							}
							?>
							</td>
							<td valign="top" width="2%"></td>								
						</tr>
						<tr>
							<td valign="top" align="right" width="10%" nowrap><?= GetMessage("SONET_C11_GROUP") ?>:</td>
							<td valign="top" colspan="2">
								<b><?
								echo "<a href=\"".$arResult["Urls"]["Group"]."\">";
								echo $arResult["Group"]["NAME"];
								echo "</a>";
								?></b>
							</td>
						</tr>
						<?

						// default invitation message
						$message = htmlspecialcharsex($_POST["MESSAGE"]);
						if (strlen($message) <= 0)
							$message = str_replace(
										array("#NAME#"), 
										array($arResult["Group"]["NAME"]), 
										GetMessage('SONET_C11_MESSAGE_DEFAULT')
									); 

						?>
						<tr>
							<td valign="top" align="right" nowrap><?= GetMessage("SONET_C11_MESSAGE") ?>:</td>
							<td valign="top"><textarea name="MESSAGE" style="width:100%" rows="5"><?= $message; ?></textarea></td>
							<td valign="top"></td>
						</tr>
					</table>
					<input type="hidden" name="SONET_USER_ID" value="<?= $arResult["User"]["ID"] ?>">
					<input type="hidden" name="SONET_GROUP_ID" value="<?= $arResult["Group"]["ID"] ?>">
					<?=bitrix_sessid_post()?>
					<br />
					<input type="submit" name="save" value="<?= GetMessage("SONET_C11_DO_ACT") ?>">
					<input type="submit" name="skip" value="<?= GetMessage("SONET_C11_DO_SKIP") ?>">
				</form>
			</td>
			<td valign="top" width="25%"></td>
		</tr>
		</table>
		<?
	}
	else
	{
		?>
		<?if ($arResult["SuccessUsers"]):?>
			<?= GetMessage("SONET_C11_SUCCESS") ?><br><br>
			<?= GetMessage("SONET_C33_T_SUCCESS_LIST") ?><br>
			<?foreach ($arResult["SuccessUsers"] as $user):?>
				<?if (StrLen($user[1]) > 0):?><a href="<?= $user[1] ?>"><?endif;?><?= $user[0] ?><?if (StrLen($user[1]) > 0):?></a><?endif;?><br />
			<?endforeach;?>
			<br />
		<?endif;?>
		<?if ($arResult["ErrorUsers"]):?>
			<?= GetMessage("SONET_C33_T_ERROR_LIST") ?><br>
			<?foreach ($arResult["ErrorUsers"] as $user):?>
				<?if (StrLen($user[1]) > 0):?><a href="<?= $user[1] ?>"><?endif;?><?= $user[0] ?><?if (StrLen($user[1]) > 0):?></a><?endif;?><br />
			<?endforeach;?>
			<br />
		<?endif;?>
		<?
		if(strlen($arResult["WarningMessage"])>0)
		{
			?>
			<br /><span class='errortext'><?=$arResult["WarningMessage"]?></span><br /><br />
			<?
		}
		?>
		<br /><a href="<? echo $arResult["Urls"]["Group"]; ?>"><? echo GetMessage("SONET_C11_MESSAGE_GROUP_LINK"); ?><? echo $arResult["Group"]["NAME"]; ?></a><br />
		<?
	}
}
?>