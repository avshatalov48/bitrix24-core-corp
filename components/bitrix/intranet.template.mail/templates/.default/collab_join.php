<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
use Bitrix\Main\Localization\Loc;

$canInsertUserData = !\Bitrix\Main\Loader::includeModule('bitrix24') || !\CBitrix24::isLicenseNeverPayed();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="ru">
<head>
	<meta charset="UTF-8">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<meta name="x-apple-disable-message-reformatting">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="telephone=no" name="format-detection">
	<meta name="color-scheme" content="light only">
	<meta name="supported-color-schemes" content="light only">
	<title>Empty template</title><!--[if (mso 16)]>
	<style type="text/css">
	a {text-decoration: none;}
	</style>
	<![endif]--><!--[if gte mso 9]><style>sup { font-size: 100% !important; }</style><![endif]--><!--[if gte mso 9]>
	<noscript>
	<xml>
		<o:OfficeDocumentSettings>
			<o:AllowPNG></o:AllowPNG>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
	</xml>
	</noscript>
	<![endif]--><!--[if mso]><xml>
	<w:WordDocument xmlns:w="urn:schemas-microsoft-com:office:word">
		<w:DontUseAdvancedTypographyReadingMail/>
	</w:WordDocument>
	</xml><![endif]-->
	<style type="text/css">
		.rollover:hover .rollover-first {
			max-height:0px!important;
			display:none!important;
		}
		.rollover:hover .rollover-second {
			max-height:none!important;
			display:block!important;
		}
		.rollover span {
			font-size:0px;
		}
		u + .body img ~ div div {
			display:none;
		}
		#outlook a {
			padding:0;
		}
		span.MsoHyperlink,
		span.MsoHyperlinkFollowed {
			color:inherit;
			mso-style-priority:99;
		}
		a.es-button {
			mso-style-priority:100!important;
			text-decoration:none!important;
		}
		a[x-apple-data-detectors],
		#MessageViewBody a {
			color:inherit!important;
			text-decoration:none!important;
			font-size:inherit!important;
			font-family:inherit!important;
			font-weight:inherit!important;
			line-height:inherit!important;
		}
		.es-desk-hidden {
			display:none;
			float:left;
			overflow:hidden;
			width:0;
			max-height:0;
			line-height:0;
			mso-hide:all;
		}
		@media only screen and (max-width:962px) {
			.email-collab-img-box {
				float: none!important;
			}
			.email-collab-img {
				width: 256px!important;
				height: 140px!important;
				background: url(<?=$this->getFolder()?>/images/collab/collab-mobile.jpg) center / cover no-repeat!important;
				background-size:cover!important;
			}
			.email-collab-content {
				padding-top:8px!important;
				padding-right:18px!important;
				padding-left:18px!important;
				padding-bottom:18px!important;
			}
			.email-collab-phone-inner {
				padding-top:18px!important;
				padding-right:18px!important;
				padding-left:18px!important;
				padding-bottom:18px!important;
			}
			.email-collab-phone-content {
				display: inline-block!important;
			}
			.email-collab-phone {
				display: inline-block!important;
				padding-right: 0!important;
			}
			.email-collab-title {
				margin-bottom: 18px!important;
				font-size: 17px!important;
			}
			.email-collab-subtitle {
				font-size: 15px!important;
			}
			.email-collab-phone-title {
				font-size: 17px!important;
			}
			.email-collab-phone-subtitle {
				margin-bottom: 18px!important;
				font-size: 15px!important;
			}
			.email-collab-footer-inner {
				padding-top:18px!important;
				padding-right:18px!important;
				padding-left:18px!important;
				padding-bottom:18px!important;
			}
			.intranet-email-footer-title {
				font-size: 17px!important;
			}
			.intranet-email-link {
				font-size: 15px!important;
			}
		}
		@media only screen and (max-width:599px) {
			/*.es-m-p5b { padding-bottom:5px!important } */
			.es-p-default { } *[class="gmail-fix"] { display:none!important } p, a { line-height:150%!important } h1, h1 a { line-height:120%!important } h2, h2 a { line-height:120%!important } h3, h3 a { line-height:120%!important } h4, h4 a { line-height:120%!important } h5, h5 a { line-height:120%!important } h6, h6 a { line-height:120%!important } .es-header-body p { } .es-content-body p { } .es-footer-body p { } .es-infoblock p { } h1 { font-size:40px!important; text-align:left } h2 { font-size:32px!important; text-align:left } h3 { font-size:28px!important; text-align:left } h4 { font-size:24px!important; text-align:left } h5 { font-size:20px!important; text-align:left } h6 { font-size:16px!important; text-align:left } .es-header-body h1 a, .es-content-body h1 a, .es-footer-body h1 a { font-size:40px!important } .es-header-body h2 a, .es-content-body h2 a, .es-footer-body h2 a { font-size:32px!important } .es-header-body h3 a, .es-content-body h3 a, .es-footer-body h3 a { font-size:28px!important } .es-header-body h4 a, .es-content-body h4 a, .es-footer-body h4 a { font-size:24px!important } .es-header-body h5 a, .es-content-body h5 a, .es-footer-body h5 a { font-size:20px!important } .es-header-body h6 a, .es-content-body h6 a, .es-footer-body h6 a { font-size:16px!important } .es-menu td a { font-size:14px!important } .es-header-body p, .es-header-body a { font-size:14px!important } .es-content-body p, .es-content-body a { font-size:14px!important } .es-footer-body p, .es-footer-body a { font-size:14px!important } .es-infoblock p, .es-infoblock a { font-size:12px!important } .es-m-txt-c, .es-m-txt-c h1, .es-m-txt-c h2, .es-m-txt-c h3, .es-m-txt-c h4, .es-m-txt-c h5, .es-m-txt-c h6 { text-align:center!important } .es-m-txt-r, .es-m-txt-r h1, .es-m-txt-r h2, .es-m-txt-r h3, .es-m-txt-r h4, .es-m-txt-r h5, .es-m-txt-r h6 { text-align:right!important } .es-m-txt-j, .es-m-txt-j h1, .es-m-txt-j h2, .es-m-txt-j h3, .es-m-txt-j h4, .es-m-txt-j h5, .es-m-txt-j h6 { text-align:justify!important } .es-m-txt-l, .es-m-txt-l h1, .es-m-txt-l h2, .es-m-txt-l h3, .es-m-txt-l h4, .es-m-txt-l h5, .es-m-txt-l h6 { text-align:left!important } .es-m-txt-r img, .es-m-txt-c img, .es-m-txt-l img { display:inline!important } .es-m-txt-r .rollover:hover .rollover-second, .es-m-txt-c .rollover:hover .rollover-second, .es-m-txt-l .rollover:hover .rollover-second { display:inline!important } .es-m-txt-r .rollover span, .es-m-txt-c .rollover span, .es-m-txt-l .rollover span { line-height:0!important; font-size:0!important; display:block } .es-spacer { display:inline-table } a.es-button, button.es-button { font-size:14px!important; padding:10px 20px 10px 20px!important; line-height:120%!important } a.es-button, button.es-button, .es-button-border { display:inline-block!important } .es-m-fw, .es-m-fw.es-fw, .es-m-fw .es-button { display:block!important } .es-m-il, .es-m-il .es-button, .es-social, .es-social td, .es-menu { display:inline-block!important } .es-adaptive table, .es-left, .es-right { width:100%!important }
			/*.es-content table, .es-header table, .es-footer table, .es-content, .es-footer, .es-header { width:100%!important; max-width:600px!important } */
			.adapt-img { width:100%!important; height:auto!important } .es-mobile-hidden, .es-hidden { display:none!important }
			.es-desk-hidden { width:auto!important; overflow:visible!important; float:none!important; max-height:inherit!important; line-height:inherit!important }
			tr.es-desk-hidden { display:table-row!important }
			table.es-desk-hidden { display:table!important }
			td.es-desk-menu-hidden { display:table-cell!important }
			.es-menu td { width:1%!important }
			table.es-table-not-adapt, .esd-block-html table { width:auto!important }
			.h-auto { height:auto!important }
			/*.img-9176 { height:140px!important }*/
		}
		@media screen and (max-width:384px) {
			.mail-message-content { width:414px!important }
		}
	</style>
</head>
<body class="body" style="width:100%;height:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0">
<div dir="ltr" class="es-wrapper-color" lang="ru" style="padding-top:27px;padding-right:20px;padding-left:20px;background-color:#0b57d0;background-image: url(<?=$this->getFolder()?>/images/collab/bg-orion.jpg); background-size: cover;"><!--[if gte mso 9]>
	<v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">
	<v:fill type="tile" color="#f6f6f6"></v:fill>
	</v:background>
	<![endif]-->
	<table width="100%" cellspacing="0" cellpadding="0" class="es-wrapper" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;padding:0;Margin:0;width:100%;height:100%;">
		<tr>
			<td valign="top" style="padding:0;Margin:0">
				<table cellspacing="0" cellpadding="0" align="center" class="" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;width:100%;table-layout:fixed !important;background-color:transparent">
					<tr>
						<td align="center" style="padding:0;Margin:0">
							<table cellspacing="0" cellpadding="0" bgcolor="" align="center" class="es-header-body" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;max-width:256px">
								<tr>
									<td align="left" style="padding:0;Margin:0;;"><!--[if mso]><table style="width:560px" cellpadding="0" cellspacing="0"><tr><td style="width:430px" valign="top"><![endif]-->
										<table width="100%" cellspacing="0" cellpadding="0" align="left" class="es-left" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;float:left">
											<tr>
												<td valign="top" align="center" class="es-m-p5b" style="padding:0;Margin:0;width:256px">

													<table width="100%" cellspacing="0" cellpadding="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;" role="presentation">
														<tr>
															<td align="center" style="padding:0;Margin:0;font-size:0">

																<div style="margin-bottom: 21px; text-align: center">
																	<img src="<?=$arResult["LOGO"]?>" alt="logo" style="display: block; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto; width: 100px; height: 20px;">
																</div>

															</td>
														</tr>
														<tr>
															<td align="left" style="padding:0;Margin:0;">
																<table width="100%" cellspacing="0" cellpadding="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;" role="presentation">
																	<tr>
																		<td align="center" style="padding:0;Margin:0;font-size:0;height:18px;"></td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>

												</td>
											</tr>
										</table><!--[if mso]></td><td style="width:30px" valign="top"><![endif]-->

								</tr>
							</table>
						</td>
					</tr>
				</table>

				<table cellspacing="0" cellpadding="0" align="center" class="es-header desktop-block" role="none" style="smso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;width:100%;table-layout:fixed !important;background-color:transparent">
						<tr>
							<td align="center" style="padding:0;Margin:0">
								<table cellspacing="0" cellpadding="0" bgcolor="" align="center" class="es-header-body" role="none" style="mso-hide: all;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;max-width:600px">
									<tr>
										<td align="left" style="padding:0;Margin:0;"><!--[if mso]><table style="width:560px" cellpadding="0" cellspacing="0"><tr><td style="width:430px" valign="top"><![endif]-->
											<table width="100%" cellspacing="0" cellpadding="0" align="left" class="es-left" role="none" style="mso-hide: all;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;float:left">
												<tr>
													<td valign="top" align="center" class="es-m-p5b" style="padding:0;Margin:0;width: 600px;">

														<table width="100%" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" style="mso-hide: all;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;border-radius:12px;overflow:hidden;" role="presentation">
															<tr>
																<td align="center" style="padding:0;Margin:0;font-size:0">
																	<div class="email-collab-img-box" style="display: inline-block;float:left;">
																		<div class="email-collab-img" style="width: 230px;height: 174px;background:url(<?=$this->getFolder()?>/images/collab/collab-desktop.jpg) center / cover no-repeat;background-size:cover;display:block;"></div>
																	</div>
																	<div class="email-collab-content" style="display:inline-block;padding-top:22px;padding-right:36px;padding-left:0;padding-bottom:22px;text-align:left;">
																		<p class="email-collab-subtitle" style="Margin:0;margin-bottom:10px;mso-line-height-rule:exactly;line-height:20px;letter-spacing:0;color:#333333;font-size:17px;font-weight:500;">
																			<?=Loc::getMessage("INTRANET_COLLAB_JOIN_JOINED_TO_COLLAB", ['[style]' => '<span style="color:#19CC45;">', '[/style]' => '</span>'])?>
																		</p>
																		<p class="email-collab-title" style="Margin:0;margin-bottom:24px;mso-line-height-rule:exactly;line-height:21px;letter-spacing:0;color:#333333;font-size:21px;font-weight:600;">
																			<?= $canInsertUserData ? htmlspecialcharsbx($arParams['FIELDS']['COLLAB_NAME']) : '' ?>
																		</p>
																		<span class="es-button-border" style="text-align:center;cursor:pointer;background:#19CC45;display:inline-block;border-radius:8px;width:100%">
																			<a href="<?=$arParams["LINK"]?>" target="_blank" class="es-button" style="cursor:pointer;mso-style-priority:100 !important;text-decoration:none !important;mso-line-height-rule:exactly;color:#FFFFFF;font-size:17px;font-weight:600;padding-top:13px;padding-right:46px;padding-bottom:13px;padding-left:46px;display:inline-block;background:#31CB4B;border-radius:8px;font-style:normal;line-height:16.8px;width:auto;text-align:center;letter-spacing:0;mso-padding-alt:0;"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_LOGIN_TO_COLLAB")?></a>
																		</span>
																	</div>
																</td>
															</tr>
														</table>

													</td>
												</tr>
												<tr>
													<td align="left" style="padding:0;Margin:0;font-size:0">
														<table width="100%" cellspacing="0" cellpadding="0" style="mso-hide: all;visibility:hidden;height:18px;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;" role="presentation"></table>
													</td>
												</tr>
											</table><!--[if mso]></td><td style="width:30px" valign="top"><![endif]-->

									</tr>
								</table>
							</td>
						</tr>
					</table>

				<table cellspacing="0" cellpadding="0" align="center" class="es-content desktop-block" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;width:100%;table-layout:fixed !important;background-color:transparent">
					<tr>
						<td align="center" style="padding:0;Margin:0">
							<table cellspacing="0" cellpadding="0" bgcolor="" align="center" class="es-content-body" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;max-width:600px;background-color: transparent;">
								<tr>
									<td align="left" style="padding:0;Margin:0;"><!--[if mso]><table style="width:560px" cellpadding="0" cellspacing="0"><tr><td style="width:480px" valign="top"><![endif]-->
										<table width="100%" cellspacing="0" cellpadding="0" align="left" class="es-left" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;float:left">
											<tr>
												<td valign="top" align="left" class="es-m-p5b" style="padding:0;Margin:0;width: 600px">

													<table width="100%" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" style="text-align:center;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;border-radius:12px;" role="presentation">
														<tr>
															<td class="email-collab-td email-collab-phone-content" align="left" style="padding:0;Margin:0;font-size:0">
																<div class="container email-collab-phone-inner" style="padding-top:28px;padding-right:10px;padding-left:36px;padding-bottom:24px;">
																	<p class="email-collab-phone-title" style="Margin:0;margin-bottom:13px;mso-line-height-rule:exactly;line-height:20px;letter-spacing:0;color:#333333;font-size:19px;font-weight:600;">
																		<?=Loc::getMessage("INTRANET_COLLAB_JOIN_IMPORTANT")?>
																	</p>
																	<p class="email-collab-phone-subtitle" style="Margin:0;margin-bottom:29px;mso-line-height-rule:exactly;line-height:20px;letter-spacing:0;color:#333333;font-size:16px">
																		<?=Loc::getMessage("INTRANET_COLLAB_JOIN_CAMERA_QR")?>
																	</p>
																	<?php
																		$iconLang = $arResult['USER_LANG'] === 'ru' ? 'ru' : 'en';
																	?>
																	<span>
																		<a href="<?=Loc::getMessage("INTRANET_COLLAB_JOIN_GOOGLEPLAY_APP_LINK")?>" target="_blank" class="es-button" style="cursor:pointer;margin-right: 8px;cursor:pointer;mso-style-priority:100 !important;text-decoration:none !important;mso-line-height-rule:exactly;display:inline-block;width:auto;text-align:center;mso-padding-alt:0;">
																			<img src="<?=$this->getFolder()?>/images/collab/google-icon-<?= $iconLang ?>.jpg" alt="logo" style="display: block;width: 101px;height: 30px;">
																		</a>
																		<a href="<?=Loc::getMessage("INTRANET_COLLAB_JOIN_APPLE_APP_LINK")?>" target="_blank" class="es-button" style="cursor:pointer;mso-style-priority:100 !important;text-decoration:none !important;mso-line-height-rule:exactly;display:inline-block;width:auto;text-align:center;mso-padding-alt:0;">
																			<img src="<?=$this->getFolder()?>/images/collab/apple-icon-<?= $iconLang ?>.jpg" alt="logo" style="cursor:pointer;display: block;width: 89px; height: 30px;">
																		</a>
																	</span>
																</div>
															</td>
															<td class="email-collab-td email-collab-phone" valign="bottom" align="left" style="padding:0;Margin:0;font-size:0;padding-right: 48px;">
																<a href="#">
																	<img alt="" width="168" height="164" src="<?=$this->getFolder()?>/images/collab/phone-desktop.jpg" class="img-9176" style="display:block;font-size:14px;border:0;outline:none;text-decoration:none">
																</a>
															</td>
														</tr>
													</table>

												</td>
											</tr>
											<tr>
												<td align="center" style="padding:0;Margin:0;font-size:0">
													<table width="100%" cellspacing="0" cellpadding="0" style="height:18px;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;" role="presentation"></table>
												</td>
											</tr>
										</table><!--[if mso]></td><td style="width:30px" valign="top"><![endif]-->
										<!--[if mso]></td></tr></table><![endif]--></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<table cellspacing="0" cellpadding="0" align="center" class="es-footer desktop-block" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;width:100%;table-layout:fixed !important;background-color:transparent">
					<tr>
						<td align="center" style="padding:0;Margin:0">
							<table cellspacing="0" cellpadding="0" bgcolor="" align="center" class="es-footer-body" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;max-width:600px;background-color: transparent;">
								<tr>
									<td align="left" style="padding:0;Margin:0;"><!--[if mso]><table style="width:560px" cellpadding="0" cellspacing="0"><tr><td style="width:480px" valign="top"><![endif]-->
										<table width="100%" cellspacing="0" cellpadding="0" align="left" class="es-left" role="none" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;float:left">
											<tr>
												<td align="left" class="es-m-p5b" style="padding:0;Margin:0;width: 600px;">

													<div class="container email-collab-footer-inner" style="padding-top:24px;padding-right:36px;padding-left:36px;padding-bottom:24px;border-radius:12px;background-color:#FFFFFF;">
														<table width="100%" cellspacing="0" cellpadding="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;border-radius:12px;background-color: #FFFFFF;" role="presentation">
															<tr>
																<td align="left" style="padding:0;Margin:0;">
																	<p class="intranet-email-footer-title" style="Margin:0;margin-bottom:19px;mso-line-height-rule:exactly;line-height:21px;letter-spacing:0;color:#333333;font-size:19px;font-weight:600;">
																		<?=Loc::getMessage("INTRANET_COLLAB_JOIN_FOOTER_TITLE")?>
																	</p>
																	<div>
																		<a href="<?=$arResult["FOOTER_LINK"]['COLLAB']?>" style="display: inline-block; margin-right: 5%; font-size: 14px; color:#333333; border-bottom-width: 1px;border-bottom-style:solid;border-bottom-color: #333333;text-decoration: none; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_LINK_COLLAB_NAME")?></a>
																		<a href="<?=$arResult["FOOTER_LINK"]['IM']?>" style="display: inline-block; margin-right: 5%; font-size: 14px; color:#333333; border-bottom-width: 1px;border-bottom-style:solid;border-bottom-color: #333333;text-decoration: none; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_LINK_IM_NAME")?></a>
																		<a href="<?=$arResult["FOOTER_LINK"]['TASKS']?>" style="display: inline-block; margin-right: 5%; font-size: 14px; color:#333333; border-bottom-width: 1px;border-bottom-style:solid;border-bottom-color: #333333;text-decoration: none; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_LINK_TASKS_NAME")?></a>
																		<a href="<?=$arResult["FOOTER_LINK"]['WF']?>" style="display: inline-block; margin-right: 5%; font-size: 15px; color:#333333; border-bottom-width: 1px;border-bottom-style:solid;border-bottom-color: #333333;text-decoration: none; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_FOOTER_LINK")?></a>
																		<a href="<?=$arResult["FOOTER_LINK"]['CRM']?>" style="display: inline-block; font-size: 14px; color:#333333; border-bottom-width: 1px;border-bottom-style:solid;border-bottom-color: #333333;text-decoration: none; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_COLLAB_JOIN_LINK_CRM_NAME")?></a>
																	</div>
																</td>
															</tr>
														</table>
													</div>

												</td>
											</tr>
											<tr>
												<td align="left" style="padding:0;Margin:0;">
													<table width="100%" cellspacing="0" cellpadding="0" style="height:18px;mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;" role="presentation"></table>
												</td>
											</tr>
										</table><!--[if mso]></td><td style="width:30px" valign="top"><![endif]-->
								</tr>
							</table></td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</div>
</body>
</html>
