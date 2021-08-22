<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<?
$httpPrefix = "http";
if (defined('BX24_HOST_NAME') || \Bitrix\Main\Context::getCurrent()->getRequest()->isHttps())
{
	$httpPrefix = "https";
}

$logoPath = "";
if (file_exists($_SERVER["DOCUMENT_ROOT"].$this->getFolder()."/images/lang/".LANGUAGE_ID."/logo.png"))
{
	$logoPath = $this->getFolder()."/images/lang/".LANGUAGE_ID."/logo.png";
}
else
{
	$logoPath = $this->getFolder()."/images/lang/".\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID)."/logo.png";
}

if (
	$arParams["TEMPLATE_TYPE"] == "USER_INVITATION"
	|| $arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION"
	|| $arParams["TEMPLATE_TYPE"] == "USER_ADD"
)
{
	?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
		<tr>
			<td align="center" bgcolor="#edeef0" style="background-color: #edeef0; padding: 50px 15px; width: 100%;">

				<table align="center"  border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; max-width: 600px; width: 100%;">
					<tr>
						<td bgcolor="#fff" style="background-color: #fff; border: 1px solid #e1e1e1;">

							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
								<tr>
									<td style="padding: 25px 20px;">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="left" style="text-align: left; padding:0 0 20px; border-bottom:1px solid #e5e7e9;">

													<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; display: inline-block;">
														<tr>
															<?if (isset($arResult["USER_PHOTO"])):?>
																<td align="left" valign="middle" style="text-align: left; padding:0; height: 45px; vertical-align: middle;">
																	<img src="<?=$arResult["USER_PHOTO"]?>" width="40" height="40" alt="">
																</td>
															<?endif?>

															<?if (isset($arResult["USER_NAME"])):?>
																<td align="left" valign="middle" style="text-align: left; padding:0 0 0 7px; height: 45px; vertical-align: middle;">
																	<span style="color: #525c69; font-size: 16px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
																		<?=GetMessage("INTRANET_INVITE_TEXT", array(
																			"#NAME#" => "<b style=\"color: #525c69; font-size: 16px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;\">".htmlspecialcharsbx($arResult["USER_NAME"])."</b>",
																			"#BLOCK_START#" => "<b style=\"font-size:22px;\"><span style=\"color: #2fc7f7\">",
																			"#BLOCK_MIDDLE#" => "</span><span style=\"color: #215f98\">",
																			"#BLOCK_END#" => "</span></b>"
																		))?>
																	</span>
																	<?if (\Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24")):?>
																		<br/>
																		<a href="<?=$httpPrefix?>://<?=$arResult["HOST_NAME"]?>" style="color: #525c69; font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; text-decoration: none;">
																			<?=$arResult["HOST_NAME"]?>
																		</a>
																	<?endif;?>
																</td>
															<?endif?>
														</tr>
													</table>

													<!--<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; display: inline-block;">
														<tr>
															<td align="left" valign="middle" style="text-align: left; padding:0 0 0 5px; height: 45px; vertical-align: middle;">
																<img src="<?=$logoPath?>" width="146" height="28" alt="<?=GetMessage("INTRANET_BITRIX24")?>">
															</td>
														</tr>
													</table>-->

												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0 0">
													<span style="display: block;color: #333;font-size: 21px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=$arParams["USER_TEXT"]?></span>
												</td>
											</tr>
											<tr>
												<td align="center" style="text-align: center; padding: 30px 0 35px; border-bottom:1px solid #e5e7e9;">
													<a href="<?=$arParams["LINK"]?>" target="_blank" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #9dcf00;">
														<b style="line-height: 47px;font-size: 18px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;color: #fff;"><?=GetMessage("INTRANET_INVITE_ACCEPT")?></b>
													</a>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center; padding: 25px 0 10px;">
													<b style="display: block;color: #333;font-size: 27px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_INVITE_INFO_TEXT3")?></b>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%; border-bottom:1px solid #e5e7e9;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0;">
													<img style="max-width: 100%;height: auto;" src="<?=GetMessage("INTRANET_INVITE_IMG_LINK")?>" alt=""/>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0 35px;">
													<span style="display: block;color: #333;font-size: 16px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=GetMessage("INTRANET_INVITE_INFO_TEXT", array(
															"#SPAN_START#" => "<span style=\"font-size: 21px; display: block; padding-bottom: 5px;\">",
															"#SPAN_END#" => "</span>")
														);?>
													</span>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="right" style="padding: 20px 0 0; text-align: right;">
													<a href="<?=GetMessage("INTRANET_SITE_LINK")?>" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_INVITE_MORE")?></a>
												</td>
											</tr>
										</table>

									</td>
								</tr>
							</table>

						</td>
					</tr>
				</table>


				<table align="center"  border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; max-width: 600px; width: 100%;">
					<tr>
						<td align="center" style="color: #9FA4AC; font-family: Helvetica, Arial, sans-serif; font-size: 12px; padding-top:11px; text-align: center;"><?=GetMessage("INTRANET_INVITE_FOOTER")?></td>
					</tr>
					<tr>
						<td align="center" style="color: #9FA4AC; font-family: Helvetica, Arial, sans-serif; font-size: 12px; padding-top:11px; text-align: center;">
							<a style="color: #9FA4AC;" href="<?=$arParams['FIELDS']['MAIL_EVENTS_UNSUBSCRIBE_LINK']?>">
								<?=GetMessage("INTRANET_MAIL_EVENTS_UNSUBSCRIBE")?>
							</a>
						</td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
	<?
}
?>

<?
if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_NOTIFY" || $arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE")
{
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
		<tr>
			<td align="center" bgcolor="#edeef0" style="background-color: #edeef0; padding: 50px 15px; width: 100%;">

				<table align="center"  border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; max-width: 600px; width: 100%;">
					<tr>
						<td bgcolor="#fff" style="background-color: #fff; border: 1px solid #e1e1e1;">

							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
								<tr>
									<td style="padding: 25px 30px 0;">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="left" style="text-align: left;">
													<img src="<?=$logoPath?>" width="183" height="35" alt="<?=GetMessage("INTRANET_BITRIX24")?>">
												</td>
											</tr>
											<tr>
												<td align="left" style="text-align: left; padding: 5px 0;">
													<span style="color: #2066b0; font-size: 19px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=GetMessage("INTRANET_MAIL_TITLE_".$arParams["TEMPLATE_TYPE"], array("#NAME#" => "<span style=\"font-weight: bold;\">".htmlspecialcharsbx($arParams["FROM_USER"])."</span>"))?>
													</span>
												</td>
											</tr>
											<tr>
												<td align="left" style="text-align: left; padding:0;">
													<span style="color: #828b95;font-size: 15px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=$arParams["DATE_CREATE"]?></span>
												</td>
											</tr>
										</table>

									</td>
								</tr>
							</table>

							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
								<tr>
									<td style="padding: 20px 15px; ">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td style="background: #eef2f4 url('<?=$this->getFolder()."/images/bg_im.png"?>') repeat top center;padding: 30px 25px;">

													<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;min-height: 350px">
														<tr>
															<?if (isset($arResult["USER_PHOTO"]) && !empty($arResult["USER_PHOTO"])):?>
															<td valign="top" align="left" width="55" style="width: 55px;vertical-align: top;">
																<img src="<?=$arResult["USER_PHOTO"]?>" width="40" height="40" alt="">
															</td>
															<?endif?>
															<td valign="top" style="vertical-align: top;width: 464px; max-width: 100%;" <?if (!isset($arResult["USER_PHOTO"]) || empty($arResult["USER_PHOTO"])):?>colspan="2" <?endif?>>
																<span style="display: block; border-radius: 14px; padding: 13px 16px; background-color: #fbfcfc;text-align: left;">
																	<span style="display: block; font-size: 16px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif; color: #525c69;text-align: left;">
																		<?=htmlspecialcharsback($arParams["MESSAGE"])?>
																	</span>
																</span>
															</td>
														</tr>
														<tr>
															<td height="100%" valign="top"></td>
															<td valign="top" align="left" style="padding-top: 20px;">
																<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/?IM_NOTIFY=Y" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #2fc6f6;">
																	<b style="line-height: 47px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;color: #fff;">
																		<?=($arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE" ? GetMessage("INTRANET_OPEN") : GetMessage("INTRANET_OPEN_NOTIFY"))?>
																	</b>
																</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="right" style="padding: 20px 0 0; text-align: right;">
													<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/?IM_SETTINGS=NOTIFY" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_CHANGE_NOTIFY_SETTINGS")?></a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?
}

if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE_GROUP")
{
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
		<tr>
			<td align="center" bgcolor="#edeef0" style="background-color: #edeef0; padding: 50px 15px; width: 100%;">

				<table align="center"  border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; max-width: 600px; width: 100%;">
					<tr>
						<td bgcolor="#fff" style="background-color: #fff; border: 1px solid #e1e1e1;">

							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
								<tr>
									<td style="padding: 25px 30px 0;">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="left" style="text-align: left;">
													<img src="<?=$logoPath?>" width="183" height="35" alt="<?=GetMessage("INTRANET_BITRIX24")?>">
												</td>
											</tr>
											<tr>
												<td align="left" style="text-align: left; padding: 5px 0;">
													<span style="color: #2066b0; font-size: 19px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=GetMessage("INTRANET_MAIL_TITLE_".$arParams["TEMPLATE_TYPE"], array("#NAME#" => "<span style=\"font-weight: bold;\">".$arParams["FROM_USER"]."</span>"))?>
													</span>
												</td>
											</tr>
											<tr>
												<td align="left" style="text-align: left; padding:0;">
													<span style="color: #828b95;font-size: 15px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=$arParams["DATE_CREATE"]?></span>
												</td>
											</tr>
										</table>

									</td>
								</tr>
							</table>

							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
								<tr>
									<td style="padding: 20px 15px; ">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td style="background: #eef2f4 url('<?=$this->getFolder()."/images/bg_im.png"?>') repeat top center;padding: 30px 25px;">

													<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;min-height: 350px;border-spacing:0 10px;">
														<?
														foreach ($arResult["MESSAGES_FROM_USERS"] as $userId => $data)
														{
														?>
															<tr>
																<?if (isset($data["USER_PHOTO"]) && !empty($data["USER_PHOTO"])):?>
																	<td valign="top" align="left" width="55" style="width: 55px;vertical-align: top;">
																		<img src="<?=$data["USER_PHOTO"]?>" width="40" height="40" alt="">
																	</td>
																<?endif?>
																<td valign="top" style="vertical-align: top;width: 464px; max-width: 100%;" <?if (!isset($data["USER_PHOTO"]) || empty($data["USER_PHOTO"])):?>colspan="2" <?endif?>>
																	<span style="display: block; border-radius: 14px; padding: 13px 16px; background-color: #fbfcfc;text-align: left;">
																		<span style="display: block; font-size: 16px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif; color: #525c69;text-align: left;">
																			<?=($data["MESSAGE"])?>
																		</span>
																	</span>
																</td>
															</tr>
														<?
														}
														?>
														<tr>
															<td height="100%" valign="top"></td>
															<td valign="top" align="left" style="padding-top: 20px;">
																<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/?IM_NOTIFY=Y" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #2fc6f6;">
																	<b style="line-height: 47px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;color: #fff;">
																		<?=(in_array($arParams["TEMPLATE_TYPE"], array("IM_NEW_MESSAGE", "IM_NEW_MESSAGE_GROUP")) ? GetMessage("INTRANET_OPEN") : GetMessage("INTRANET_OPEN_NOTIFY"))?>
																	</b>
																</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="right" style="padding: 20px 0 0; text-align: right;">
													<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/?IM_SETTINGS=NOTIFY" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_CHANGE_NOTIFY_SETTINGS")?></a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?
}
?>
</body>
</html>
