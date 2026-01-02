<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<?if (
		$arParams["TEMPLATE_TYPE"] == "USER_INVITATION"
		|| $arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION"
		|| $arParams["TEMPLATE_TYPE"] == "USER_ADD"
	):?>
	<meta name="x-apple-disable-message-reformatting" />
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		/* ======================================= DESKTOP STYLES */
		* { -webkit-text-size-adjust: none; }
		body { margin: 0 !important; padding: 0 !important; }
		body,table,td,p,a { -ms-text-size-adjust: 100% !important; -webkit-text-size-adjust: 100% !important; }
		table, tr, td { border-spacing: 0 !important; mso-table-lspace: 0px !important; mso-table-rspace: 0pt !important; border-collapse: collapse !important; mso-line-height-rule:exactly !important;}
		.ExternalClass * { line-height: 100% }
		.mobile-link a, .mobile-link span { text-decoration:none !important; color: inherit !important; border: none !important; }
		pre {margin-top:0;margin-bottom:0;}
		/* ======================================= CUSTOM DESKTOP STYLES */

		/* ======================================= MOBILE STYLES */
		@media only screen and (max-width: 640px) {
			body { min-width: 320px; margin: 0; }
			.hide-m { display: none !important; }
			.show-for-small { display: block !important; overflow: visible !important; width: auto !important; max-height: inherit !important; }
			.no-float { float: none !important; }
			.block { display: block !important; }
			.resize-image { width: 100%; height: auto; }
			.center-image { display: block; margin: 0 auto; }

			.text-center { text-align: center !important; }
			.font-14 { font-size: 14px !important; line-height: 16px !important; }
			.font-16 { font-size: 16px !important; line-height: 18px !important; }
			.font-18 { font-size: 18px !important; line-height: 20px !important; }
			.font-20 { font-size: 20px !important; line-height: 22px !important; }
			.font-22 { font-size: 22px !important; line-height: 24px !important; }

			.pad-t-0 { padding-top: 0px !important; }
			.pad-r-0 { padding-right: 0px !important; }
			.pad-b-0 { padding-bottom: 0px !important; }
			.pad-l-0 { padding-left: 0px !important; }
			.pad-t-20 { padding-top: 20px !important; }
			.pad-r-20 { padding-right: 20px !important; }
			.pad-b-20 { padding-bottom: 20px !important; }
			.pad-l-20 { padding-left: 20px !important; }
			.pad-0 { padding-top: 0px !important; padding-right: 0px !important; padding-bottom: 0px !important; padding-left: 0px !important; }
			.pad-10 { padding-top: 10px !important; padding-right: 10px !important; padding-bottom: 10px !important; padding-left: 10px !important; }
			.pad-20 { padding-top: 20px !important; padding-right: 20px !important; padding-bottom: 20px !important; padding-left: 20px !important; }
			.pad-sides-0 { padding-right: 0px !important; padding-left: 0px !important; }
			.pad-sides-10 { padding-right: 10px !important; padding-left: 10px !important; }
			.pad-sides-20 { padding-right: 20px !important; padding-left: 20px !important; }
			.pad-sides-30 { padding-right: 30px !important; padding-left: 30px !important; }

			.w100 { width: 100% !important; min-width: initial !important; }
			.w90 { width: 90% !important; min-width: initial !important; }
			.w50 { width: 50% !important; min-width: initial !important; }
			/* ======================================= CUSTOM MOBILE STYLES */

		}
	</style>
	<?endif;?>
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

	<!-- WRAPPER -->
	<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%; background-color: #e5f9ff; background-image: url(<?= $arParams['BACKGROUND']?>); background-repeat: no-repeat; background-position: center; background-size: cover;">
		<tbody>
		<tr>
			<td align="center" class="pad-sides-20" style="padding-top: 60px; padding-bottom: 14px;">
				<!-- CONTAINER -->
				<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="410" style="width: 410px; min-width: 410px; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto;">
					<tbody>
					<!-- HEADER -->
					<tr>
						<td style="padding: 0 10px 25px;">
							<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%;">
								<tbody>
								<tr>
									<td align="left" style="">
										<img src="<?=$logoPath?>" style="display:block;width: 114px;height: 22px;" />
									</td>
									<td align="right" style="">
										<span style="font-size: 13px;font-weight: 500;color: #0065a3;text-align: right;"><?=Loc::getMessage('INTRANET_BITRIX24_PROMO')?></span>
									</td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<!-- /HEADER -->
					<tr>
						<td align="center">
							<!-- CONTAINER -->
							<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" bgcolor="#ffffff" style="width: 100%; border-top-left-radius: 10px; border-top-right-radius: 10px; background-color: #ffffff; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto;">
								<tbody>
								<tr>
									<td class="pad-sides-10" align="center" style="padding-top: 34px; padding-left: 43px; padding-right: 43px; padding-bottom: 25px;">

										<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%; margin: 0 auto;">
											<tbody>
											<?if ($arResult['IS_LICENSE_PAID']):?>
											<tr>
												<td>
													<div style="padding-bottom: 30px;margin-bottom: 24px;border-bottom: 1px solid #dfe0e3;">
														<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%; margin: 0 auto;">
															<tbody>
															<tr>
																<?php
																$arResult["USER_PHOTO"] = $arResult['USER_PHOTO'] ?: $this->getFolder()."/images/avatar.png";
																?>
																<td align="right" style="padding-right: 16px;">
																	<div class="icon" style="width: 58px;height: 58px;border-radius: 600px;background-color:#a8adb4;background-size: cover;background-image: url('<?=$arResult["USER_PHOTO"]?>');"></div>
																</td>
																<td valign="center" align="left">
																	<div class="info">
																		<?if (isset($arResult['USER_NAME'])):?>
																			<div style="font-size: 16px;font-weight: 500;color:#333333;"><?=htmlspecialcharsbx($arResult['USER_NAME'])?></div>
																		<?endif?>
																		<span style="font-size: 14px;color:#6a737f;text-decoration: none;"><?=htmlspecialcharsbx($arResult["USER_EMAIL"])?></span>
																	</div>
																</td>
															</tr>
															</tbody>
														</table>
													</div>
												</td>
											</tr>
											<?endif;?>
											<tr>
												<td align="center" style="padding-bottom: 24px;">
													<div style="display:block;font-size: 18px;font-weight: 500;color:#151515;text-align: center;letter-spacing: 0.36px;"><?=Loc::getMessage('INTRANET_INVITATION_JOIN_TEXT')?></div>
												</td>
											</tr>
											<tr>
												<td align="center" style="padding-bottom: 16px;">
													<div><!--[if mso]>
														<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?=$arParams['LINK']?>" style="height:47px;v-text-anchor:middle;width:350px;text-transform: uppercase;" arcsize="18%" stroke="f" fillcolor="#bbed21">
															<w:anchorlock/>
															<center>
														<![endif]-->
														<a class="w100" href="<?=$arParams['LINK']?>"
														   style="background-color:#bbed21;border-radius:8px;color:#525C69;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:47px;text-align:center;text-decoration:none;width:350px;-webkit-text-size-adjust:none;text-transform: uppercase;cursor: pointer;"><?=Loc::getMessage('INTRANET_INVITATION_JOIN')?></a>
														<!--[if mso]>
														</center>
														</v:roundrect>
														<![endif]-->
													</div>
												</td>
											</tr>
											<tr>
												<td align="center">
													<div style="display:block;font-size: 13px;color:#959ca4;text-align: center;"><?=Loc::getMessage('INTRANET_EXPLANATION_TEXT')?></div>
												</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
								</tbody>
							</table>
							<!-- /CONTAINER -->
						</td>
					</tr>
					<!-- GRAY CONTAINER -->
					<tr>
						<td>
							<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto;">
								<tbody>
								<tr>
									<table class="w100" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width: 100%; background-color: #f5f7f8; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
										<tbody>
										<tr>
											<td align="left" style="padding-top: 24px; padding-left: 43px; padding-bottom: 25px;">
												<div style="font-size: 14px;color:#959ca4;"><?=Loc::getMessage('INTRANET_YOUR_BITRIX24')?></div>
											</td>
											<td align="left" style="padding-top: 24px; padding-right: 43px; padding-bottom: 25px;">
												<a href="<?=$arParams['LINK']?>" style="font-size: 14px;font-weight: 500;color:#2066b0;text-decoration: none;"><?=$arResult['HOST_NAME']?></a>
											</td>
										</tr>
										</tbody>
									</table>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<!-- /GRAY CONTAINER -->
					<!-- FOOTER -->
					<td align="center" style="padding-bottom: 60px;">
						<a href="<?=$arResult['PRIVACY_POLICY_URL']?>" style="padding-bottom: 2px;font-size: 13px;color:#a8adb4;text-align: center; text-decoration: none; border-bottom: 1px dashed #a8adb4;"><?=Loc::getMessage('INTRANET_PERSONAL_DATA_TEXT')?></a>
					</td>
					<!-- /FOOTER -->
					</tbody>
				</table>
				<!-- /CONTAINER -->
			</td>
		</tr>
		</tbody>
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
																<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/online/?IM_NOTIFY=Y" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #2fc6f6;">
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
													<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/online/?IM_SETTINGS=NOTIFY" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_CHANGE_NOTIFY_SETTINGS")?></a>
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
																<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/online/?IM_NOTIFY=Y" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #2fc6f6;">
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
													<a href="<?=$httpPrefix?>://<?=$arParams["SERVER_NAME"]?>/online/?IM_SETTINGS=NOTIFY" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_CHANGE_NOTIFY_SETTINGS")?></a>
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
