<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$mess = Loc::loadLanguageFile(__FILE__, $arResult['LANGUAGE_ID']);

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

if (!empty($arResult['ERROR']))
{
	ShowError($mess['IMOL_HTML_FORMAT_ERROR']);
	return;
}
?>
<body style="margin: 0;padding: 0;display: flex;flex-direction: column;height: 100%;">
	<div style="height: 71px;background: #17A3EA;text-align: center;margin-bottom: 28px;">
		<div style="padding-top: 15px;font: 15px/20px var(--ui-font-family-secondary, var(--ui-font-family-open-sans)); font-weight: var(--ui-font-weight-semi-bold, 600); color: #fff">
			<?=htmlspecialcharsbx($arResult['TEMPLATE_WIDGET_TITLE'])?>
		</div>
		<a
			href="<?=$arResult['TEMPLATE_WIDGET_LOCATION']?>"
			style="display: inline-block;margin-bottom: 16px;opacity: 0.7; font: 13px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
			color: #fff;border-bottom: 1px dashed;text-decoration: none;"
		>
			<?=$arResult['TEMPLATE_WIDGET_SESSION_ID']?> &ndash; <?=$arResult['TEMPLATE_WIDGET_LOCATION']?>
		</a>
	</div>
	<table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto; padding:0; width: 100%; max-width: 500px " width="100%" align="center">
		<?foreach($arResult['TEMPLATE_MESSAGES'] as $id => $message):?>
			<?if($message['SYSTEM'] == 'Y') {?>
				<tr>
					<td></td>
					<td>
						<div style="background: #fff; border: 1px solid #dfe2e5; border-radius: 8px; padding: 15px;">
							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;" width="100%">
								<tr>
									<td bgcolor="#ffffff"  style="background: #ffffff; padding-bottom: 5px;text-align: left;" ><span style="color: #b2b2b2; font-size: 12px; line-height: 14px; font-family: Helvetica, Arial, sans-serif; word-break: break-word;"><?=$mess['IMOL_HTML_MESSAGE_SYSTEM']?></span></td>
									<td bgcolor="#ffffff"  style="background: #ffffff; padding-bottom: 5px;text-align: right;"><span style="color: #b2b2b2; font-size: 11px; line-height: 14px; font-family: Helvetica, Arial, sans-serif;"><?=($message['DATE'])?></span></td>
								</tr>
								<tr>
									<td bgcolor="#ffffff" colspan="2" align="left" style="background: #ffffff; "><span style="color: #7d7d7d; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif; word-break: break-word;"><?=($message['TEXT'])?></span></td>
								</tr>
							</table>
						</div>
					</td>
					<td></td>
				</tr>
				<tr>
					<td colspan="3" style="height: 6px;"></td>
				</tr>
			<?} elseif($message['CLIENT'] == 'N') {?>
				<tr>
					<td></td>
					<td>
						<div style="background: #E4F8FF; border: 1px solid #E4F8FF; border-radius: 8px; padding: 15px;">
							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;" width="100%">
								<tr>
									<td bgcolor="#E4F8FF" style="background: #E4F8FF; padding-bottom: 5px;text-align: left;" ><span style="color: #6C777F; font-size: 12px; line-height: 14px; font-family: Helvetica, Arial, sans-serif; word-break: break-word;"><?=($message['NAME'])?></span></td>
									<td bgcolor="#E4F8FF" style="background: #E4F8FF; padding-bottom: 5px;text-align: right;"><span style="color: #B5B9BE; font-size: 11px; line-height: 14px; font-family: Helvetica, Arial, sans-serif;"><?=($message['DATE'])?></span></td>
								</tr>
								<tr>
									<td bgcolor="#E4F8FF" colspan="2" align="left" style="background: #E4F8FF;"><span style="color: #000000; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif; word-break: break-word;"><?=($message['TEXT'])?></span></td>
								</tr>
							</table>
						</div>
					</td>
					<td style="width: 50px; text-align: right;"><img src="<?=($message['AVATAR']
							? : $arResult['TEMPLATE_SERVER_ADDRESS'].
							'/bitrix/components/bitrix/imopenlines.mail.history/templates/.default/images/default_avatar.png')?>" alt="" width="34" height="34" style="border-radius: 50%; width: 34px; height: 34px"></td>
				</tr>
				<tr>
					<td colspan="3" style="height: 6px;"></td>
				</tr>
			<?} else {?>
				<tr>
					<td style="width: 50px; text-align: left;"><img src="<?=($message['AVATAR']
							?: $arResult['TEMPLATE_SERVER_ADDRESS'].
							'/bitrix/components/bitrix/imopenlines.mail.history/templates/.default/images/default_avatar.png')?>" alt="" width="34" height="34" style="border-radius: 50%; width: 34px; height: 34px"></td>
					<td>
						<div style="background: #F3F5F7; border: 1px solid #F3F5F7;  border-radius: 8px; padding: 15px;">
							<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;" width="100%">
								<tr>
									<td bgcolor="#F3F5F7" style="background: #F3F5F7; padding-bottom: 5px;text-align: left;" ><span style="color: #6C777F; font-size: 12px; line-height: 14px; font-family: Helvetica, Arial, sans-serif;"><?=($message['NAME'])?></span></td>
									<td bgcolor="#F3F5F7" style="background: #F3F5F7; padding-bottom: 5px;text-align: right;"><span style="color: #B5B9BE; font-size: 11px; line-height: 14px; font-family: Helvetica, Arial, sans-serif;"><?=($message['DATE'])?></span></td>
								</tr>
								<tr>
									<td bgcolor="#F3F5F7" colspan="2" align="left" style="background: #F3F5F7;"><span style="color: #000000; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif;word-break: break-word;">
								<?=($message['TEXT'])?>
							</span></td>
								</tr>
							</table>
						</div>
					</td>
					<td></td>
				</tr>
				<tr>
					<td colspan="3" style="height: 6px;"></td>
				</tr>
			<?}?>
		<?endforeach;?>
	</table>

	<div style="height: 71px;background: #F3F5F7;text-align: center;margin-top: auto;">
		<a
			href="<?=$arResult['TEMPLATE_WIDGET_LOCATION']?>"
			style="display: inline-block;padding-top: 25px;margin-bottom: 29px;font: 13px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
			color: #9b9b9b;border-bottom: 1px dashed; text-decoration: none;"
		>
			<?=$mess['IMOL_HTML_WRITE_TO_LINE']?>
		</a>
	</div>
</body>