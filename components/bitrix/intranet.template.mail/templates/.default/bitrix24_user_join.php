<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24\Analytics;
use Bitrix\Main\Web\Uri;
?>

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
	$arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_CONFIRM"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_REJECT"
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
									<td style="padding: 25px 30px;">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:10px 0 20px 0; padding:0; width: 100%;">
											<tr>
												<td align="left" style="text-align: left; padding:0 0 20px; border-bottom:1px solid #e5e7e9;">

													<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
														<tr>
															<td align="center" style="text-align: center;">
																<img src="<?=$logoPath?>" width="183" height="35" alt="<?=Loc::getMessage("INTRANET_BITRIX24")?>">
															</td>
														</tr>
														<tr>
															<td align="left" style="text-align: center; padding: 50px 0 0 0;">
																<span style="color: #2066b0; font-size: 19px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
																	<?=Loc::getMessage("INTRANET_USER_JOIN_TITLE_".$arParams["TEMPLATE_TYPE"], array("#URL#" => "<font style=\"font-size: 20px;font-weight: bold;\"><a style=\"color: #2067b0; text-decoration: none;\" href=\"https://".BX24_HOST_NAME."\">".BX24_HOST_NAME."</a></font>"))?>
																</span>
															</td>
														</tr>
													</table>

												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0 0">
													<span style="display: block;color: #333;font-size: 21px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?
														if ($arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_REJECT")
														{
															echo Loc::getMessage("INTRANET_USER_JOIN_REQUEST_REJECT_MESSAGE");
														}
														elseif ($arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST")
														{
															echo Loc::getMessage("INTRANET_USER_JOIN_MESSAGE_REQUEST_MESSAGE");
														}
														else
														{
															echo Loc::getMessage("INTRANET_USER_JOIN_MESSAGE");
														}
														?>
													</span>
												</td>
											</tr>
											<?if (
												$arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_CONFIRM"
												|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN"
											):?>
											<tr>
												<td align="center" style="text-align: center; padding: 30px 0 5px;">
													<a href="<?= Analytics\Event::addAnalyticLabelToUrl(new Uri($httpPrefix . '://' . BX24_HOST_NAME), Analytics\Event::LINK_INVITE_ADMIN_AGREE)->getUri() ?>" target="_blank" style="display: inline-block; border-radius: 23px; padding: 0 30px; vertical-align: middle; text-decoration: none; height: 47px; background-color: #9dcf00;">
														<b style="line-height: 47px;font-size: 18px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;color: #fff;"><?=Loc::getMessage("INTRANET_USER_JOIN_ACCEPT")?></b>
													</a>
												</td>
											</tr>
											<?endif?>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:40px 0 0 0; padding:0; width: 100%; border-bottom:1px solid #e5e7e9;">
											<tr>
												<td align="center" style="text-align: center; padding: 40px 0; border-top:1px solid #e5e7e9;display: block;color: #333;font-size: 16px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
													<font style="font-size: 20px;font-weight: bold; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=Loc::getMessage("INTRANET_USER_JOIN_QUESTIONS")?></font>
													<br><br>
													<?=Loc::getMessage("INTRANET_USER_JOIN_HELP1")?>
													<br/>
													<?=Loc::getMessage("INTRANET_USER_JOIN_HELP2", [
														"#LINK_START#" => "<a style=\"color: #2067b0; text-decoration: none;\" href=\"".$arResult["HELPDESK_URL"]."/?utm_source=mailb24&utm_medium=auto&utm_campaign=EMAILB24HELP\">",
														"#LINK_END#" => "</a>"
													])?>
													<br/>
													<?=Loc::getMessage("INTRANET_USER_JOIN_HELP3", [
														"#LINK_START#" => "<a style=\"color: #2067b0; text-decoration: none;\" href=\"".Loc::getMessage("INTRANET_USER_JOIN_WEBINARS_LINK")."\">",
														"#LINK_END#" => "</a>"
													])?>
													<?if (!in_array($this->arResult["LICENSE_PREFIX"], ["ru", "by", "kz", "ua"]) && !in_array(LANGUAGE_ID, ["ru", "ua"])):?>
														<?=Loc::getMessage("INTRANET_USER_JOIN_HELP4", [
															"#LINK_START#" => "<a style=\"color: #2067b0; text-decoration: none;\" href=\"".Loc::getMessage("INTRANET_USER_JOIN_PARTNERS_LINK")."\">",
															"#LINK_END#" => "</a>"
														])?>
													<?endif?>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:10px 0 0 0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center; padding: 25px 0 10px;">
													<b style="display: block;color: #333;font-size: 27px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_USER_JOIN_INFO_TEXT3")?></b>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%; border-bottom:1px solid #e5e7e9;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0;">
													<img style="max-width: 100%;height: auto;" src="<?=Loc::getMessage("INTRANET_USER_JOIN_IMG_LINK")?>" alt=""/>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:20px 0 0 0; padding:0; width: 100%; border-bottom:1px solid #e5e7e9;">
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0 35px;">
													<span style="display: block;color: #333;font-size: 16px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=Loc::getMessage("INTRANET_USER_JOIN_INFO_TEXT", array(
																									 "#SPAN_START#" => "<span style=\"font-size: 21px; display: block; padding-bottom: 5px;\">",
																									 "#SPAN_END#" => "</span>")
														);?>
													</span>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:30px 0 0 0; padding:0; width: 100%;">
											<tr>
												<td align="center" style="text-align: center;">
													<b style="display: block;color: #333;font-size: 12px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=Loc::getMessage("INTRANET_USER_JOIN_MOBILE_TEXT1")?>
													</b>
													<span style="display: block;color: #333;font-size: 12px;font-family: Helvetica Neue, Helvetica, Arial, sans-serif;">
														<?=Loc::getMessage("INTRANET_USER_JOIN_MOBILE_TEXT2")?>
													</span>
												</td>
											</tr>
											<tr>
												<td align="center" style="text-align: center; padding: 20px 0;">
													<a href="<?=Loc::getMessage("INTRANET_USER_JOIN_APPLE_APP_LINK")?>" style="text-decoration: none;">
														<img style="max-width: 18%;height: auto;" src="<?=Loc::getMessage("INTRANET_USER_JOIN_APPLE_IMG_LINK")?>" alt=""/>
													</a>
													<a href="<?=Loc::getMessage("INTRANET_USER_JOIN_GOOGLEPLAY_APP_LINK")?>" style="text-decoration: none;">
														<img style="max-width: 20%;height: auto;" src="<?=Loc::getMessage("INTRANET_USER_JOIN_GOOGLEPLAY_IMG_LINK")?>" alt=""/>
													</a>
												</td>
											</tr>
										</table>

										<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;">
											<tr>
												<td align="right" style="padding: 20px 0 0; text-align: right;">
													<a href="<?=Loc::getMessage("INTRANET_SITE_LINK")?>" style="color: #a9adb3;font-size: 13px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><?=GetMessage("INTRANET_USER_JOIN_MORE")?></a>
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
						<td align="center" style="color: #9FA4AC; font-family: Helvetica, Arial, sans-serif; font-size: 12px; padding-top:11px; text-align: center;"><?=Loc::getMessage("INTRANET_USER_JOIN_FOOTER")?></td>
					</tr>
					<tr>
						<td align="center" style="color: #9FA4AC; font-family: Helvetica, Arial, sans-serif; font-size: 12px; padding-top:11px; text-align: center;">
							<a style="color: #9FA4AC;" href="<?=$arParams['FIELDS']['MAIL_EVENTS_UNSUBSCRIBE_LINK']?>">
								<?=Loc::getMessage("INTRANET_MAIL_EVENTS_UNSUBSCRIBE")?>
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
</body>
</html>
