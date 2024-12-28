<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
use Bitrix\Main\Localization\Loc;

$logoUrl = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDefaultLogo();
$canInsertUserData = !\Bitrix\Main\Loader::includeModule('bitrix24') || !\CBitrix24::isLicenseNeverPayed();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="color-scheme" content="light only">
	<meta name="supported-color-schemes" content="light only">
	<style>
		:root {
			color-scheme: light only;
		}
		body {
			margin: 0;
			padding: 0;
			min-height: 100%;
			height: 100%;
			width: 100%;
			background-color: #ffffff;
		}
		.email-container {
			width: 100%;
			max-width: 600px;
			margin: 0 auto;
		}
		.email-block {
			margin-bottom: 24px;
			padding: 20px;
		}
		.bg-container img {
			display: block;
			max-width: 100%;
			height: auto;
		}
		.intranet-email-btn-collab {
			background-color:#3F62E8!important;
			background-color: linear-gradient(#3F62E8, #3F62E8)!important;
			color:#ffffff!important;
		}
		@media (prefers-color-scheme: dark) {
			.intranet-email-btn-collab {
				background-color:#3F62E8!important;
				background-color: linear-gradient(#3F62E8, #3F62E8)!important;
				color:#ffffff!important;
			}
		}
		@media (prefers-color-scheme: dark) {
			u + .body .intranet-email-btn-collab {
				background-color:#3F62E8!important;
				background-color: linear-gradient(#3F62E8, #3F62E8)!important;
				color:#ffffff!important;
			}
		}
	</style>
	<style>
		@media only screen and (max-width: 480px) {
			.intranet-email-link {
				margin-right: 2%!important;
			}
			.intranet-email-mobile-block {
				padding-top: 20px!important;
				padding-right: 20px!important;
				padding-bottom: 20px!important;
				padding-left: 20px!important;
			}
		}
	</style>
</head>
<body class="body">
<table class="bg-container" cellpadding="0" cellspacing="0" border="0" style="padding-top: 0; padding-right: 10px; padding-bottom: 0; padding-left: 10px; width: 100%; border-radius: 10px; background-image: url(<?=$this->getFolder()?>/images/collab/orion-bg.jpg); background-size: cover;">
	<tr>
		<td>
			<!--[if gte mso 9]>
			<v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:1200px;">
				<v:fill type="frame" src="<?=$this->getFolder()?>/images/collab/orion-bg.jpg" color="#f4f4f4" />
				<v:textbox inset="0,0,0,0">
			<![endif]-->
			<table class="email-container" cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 600px; margin: 0 auto;">
				<tr>
					<td>
						<div style="margin-top: 34px; margin-bottom: 34px; text-align: center">
							<img src="<?=$arResult["LOGO"]?>" alt="logo" style="display: block; margin-top: 0; margin-right: auto; margin-bottom: 0; margin-left: auto; width: 100px; height: 20px;">
						</div>
					</td>
				</tr>
				<tr>
					<td>

						<table cellpadding="0" cellspacing="0" border="0" width="100%">
							<tr>
								<td style="">
									<!--[if mso]>
									<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:200px;v-text-anchor:middle;width:100%;" arcsize="10%" stroke="f" fillcolor="#333333">
										<w:anchorlock/>
										<center style="color:#333333;font-family:sans-serif;font-size:14px;font-weight:bold;">
									<![endif]-->
									<div class="email-block intranet-email-mobile-block" style="margin-bottom: 24px; padding-top: 40px; padding-right: 36px; padding-bottom: 40px; padding-left: 36px; border-radius: 10px; background-color: #f8fafb!important; color: #333333;">
										<?php if ($canInsertUserData): ?>
										<div style="margin-bottom: 40px;">
											<table cellpadding="0" cellspacing="0" border="0">
												<tr>
													<?php if (!empty($arResult["USER_PHOTO"])):?>
													<td>
														<!--[if mso]>
														<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?=$arResult["USER_PHOTO"]?>" style="height:42px;v-text-anchor:middle;width:42px;" arcsize="10%" stroke="f" fillcolor="#333333">
															<w:anchorlock/>
															<center style="color:#333333;font-family:sans-serif;font-size:14px;font-weight:bold;">
														<![endif]-->
														<div style="margin-right: 11px;">
															<img src="<?=$arResult["USER_PHOTO"]?>" alt="user" style="width: 42px; height: 42px; border-radius: 50%; background-color: #eee;">
														</div>
														<!--[if mso]>
														</center>
														</v:roundrect>
														<![endif]-->
													</td>
													<?php endif;?>
													<td>
														<div style="font-size: 15px;"><?=htmlspecialcharsbx($arResult["USER_NAME"])?></div>
														<div style="font-size: 14px;"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_INVITE_YOU")?></div>
													</td>
												</tr>
											</table>
										</div>
										<?php endif; ?>
										<div style="margin-bottom: 15px; font-size: 36px; font-weight: 700; line-height: 41px;">
											<?= $canInsertUserData ? htmlspecialcharsbx($arParams['FIELDS']['COLLAB_NAME']) : Loc::getMessage("INTRANET_INVITATION_COLLAB_TITLE") ?>
										</div>
										<div style="margin-bottom: 40px;">
											<a href="<?=$arParams["LINK"]?>"
											   class="intranet-email-btn-collab"
											   style="background-color:#3F62E8!important;background-color: linear-gradient(#3F62E8, #3F62E8)!important;;border-radius:10px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:16px;line-height:46px;text-align:center;text-decoration:none;width:226px;-webkit-text-size-adjust:none;"
											>
												<?=Loc::getMessage("INTRANET_INVITATION_COLLAB_JOIN_BTN")?>
											</a>
										</div>
										<div style="font-size: 16px; line-height: 18px;"><?=$canInsertUserData ? Loc::getMessage("INTRANET_INVITATION_COLLAB_JOIN_US", ["#COLLAB_NAME#" => htmlspecialcharsbx($arParams['FIELDS']['COLLAB_NAME'])]) : Loc::getMessage('INTRANET_INVITATION_COLLAB_JOIN_US_WITHOUT_NAME')?></div>
									</div>
									<!--[if mso]>
									</center>
									</v:roundrect>
									<![endif]-->
								</td>
							</tr>
						</table>

						<table cellpadding="0" cellspacing="0" border="0" width="100%">
							<tr>
								<td style="">
									<!--[if mso]>
									<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:200px;v-text-anchor:middle;width:100%;" arcsize="10%" stroke="f" fillcolor="#333333">
										<w:anchorlock/>
										<center style="color:#333333;font-family:sans-serif;font-size:14px;font-weight:bold;">
									<![endif]-->
									<div class="email-block intranet-email-mobile-block" style="margin-bottom: 24px; padding-top: 30px; padding-right: 36px; padding-bottom: 20px; padding-left: 36px; border-radius: 10px; background-color: #f1f4f6!important; color: #333333; text-align: center;">
										<div style="display: inline-block; margin-right: 5px; margin-bottom: 10px; max-width: 156px; font-size:12px; text-align: center;">
											<img src="<?=$this->getFolder()?>/images/collab/email-icon-1.png" alt="icon" style="display: block; margin-top: 0; margin-left: auto; margin-bottom: 16px; margin-right: auto; max-width: 120px;">
											<div style="line-height: 16px"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_SECTION_IM")?></div>
										</div>
										<div style="display: inline-block; margin-right: 5px; margin-bottom: 10px; max-width: 156px; font-size:12px; text-align: center;">
											<img src="<?=$this->getFolder()?>/images/collab/email-icon-2.png" alt="icon" style="display: block; margin-top: 0; margin-left: auto; margin-bottom: 16px; margin-right: auto; max-width: 120px;">
											<div style="line-height: 16px"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_SECTION_PEOPLE")?></div>
										</div>
										<div style="display: inline-block; margin-bottom: 10px; max-width: 156px; font-size:12px; text-align: center;">
											<img src="<?=$this->getFolder()?>/images/collab/email-icon-3.png" alt="icon" style="display: block; margin-top: 0; margin-left: auto; margin-bottom: 16px; margin-right: auto; max-width: 120px;">
											<div style="line-height: 16px"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_SECTION_FEATURES")?></div>
										</div>
									</div>
									<!--[if mso]>
									</center>
									</v:roundrect>
									<![endif]-->
								</td>
							</tr>
						</table>

						<table cellpadding="0" cellspacing="0" border="0" width="100%">
							<tr>
								<td style="">
									<!--[if mso]>
									<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:200px;v-text-anchor:middle;width:100%;" arcsize="10%" stroke="f" fillcolor="#333333">
										<w:anchorlock/>
										<center style="color:#333333;font-family:sans-serif;font-size:14px;font-weight:bold;">
									<![endif]-->
									<div class="email-block intranet-email-mobile-block" style="margin-bottom: 24px; padding-top: 30px; padding-right: 36px; padding-bottom: 30px; padding-left: 36px; border-radius: 10px; background-color: #f1f4f6!important; color: #333333;">
										<div style="margin-bottom: 10px; font-size: 16px; color:#333333; text-align: center; line-height: 16px;"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_FOOTER_TITLE")?></div>
										<div style="text-align: center;">
											<a href="<?=$arResult["FOOTER_LINK"]['COLLAB']?>" style="display: inline-block; margin-right: 9%; font-size: 11px; color:#333333; text-decoration: underline; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_LINK_COLLAB_NAME")?></a>
											<a href="<?=$arResult["FOOTER_LINK"]['IM']?>" style="display: inline-block; margin-right: 9%; font-size: 11px; color:#333333; text-decoration: underline; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_LINK_IM_NAME")?></a>
											<a href="<?=$arResult["FOOTER_LINK"]['TASKS']?>" style="display: inline-block; margin-right: 9%; font-size: 11px; color:#333333; text-decoration: underline; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_LINK_TASKS_NAME")?></a>
											<a href="<?=$arResult["FOOTER_LINK"]['CRM']?>" style="display: inline-block; margin-right: 9%; font-size: 11px; color:#333333; text-decoration: underline; text-align: left;" class="intranet-email-link"><?=Loc::getMessage("INTRANET_INVITATION_COLLAB_LINK_CRM_NAME")?></a>
											<a href="<?=$arResult["FOOTER_LINK"]['WF']?>" style="display: inline-block; font-size: 11px; color:#333333; text-decoration: underline; text-align: left;" class="intranet-email-link"><?=Loc::getMessage($arResult["FOOTER_LINK"]['LAST_PHRASE'])?></a>
										</div>
									</div>
									<!--[if mso]>
									</center>
									</v:roundrect>
									<![endif]-->
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<!--[if gte mso 9]>
			</v:textbox>
			</v:rect>
			<![endif]-->
		</td>
	</tr>
</table>
</body>
</html>

