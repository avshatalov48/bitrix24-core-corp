<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

$mess = Loc::loadLanguageFile(__FILE__, $arResult['LANGUAGE_ID']);

if(!empty($arResult['ERROR']))
{
	ShowError($mess['IMOL_MAIL_FORMAT_ERROR']);
	return;
}
?>

<table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto; padding:0; width: 100%; max-width: 500px " width="100%" align="center">
<?foreach($arResult['TEMPLATE_MESSAGES'] as $id => $message):?>
	<?if($message['SYSTEM'] == 'Y') {?>
	<tr>
		<td></td>
		<td>
			<div style="background: #fff; border: 1px solid #dfe2e5; border-radius: 8px; padding: 15px;">
				<table border="0" cellpadding="0" cellspacing="0" style="margin:0; padding:0; width: 100%;" width="100%">
					<tr>
						<td bgcolor="#ffffff"  style="background: #ffffff; padding-bottom: 5px;text-align: left;" ><span style="color: #b2b2b2; font-size: 12px; line-height: 14px; font-family: Helvetica, Arial, sans-serif; word-break: break-word;"><?=$mess['IMOL_MAIL_MESSAGE_SYSTEM']?></span></td>
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
		<td style="width: 50px; text-align: right;"><img src="<?=($message['AVATAR']? $message['AVATAR']: $arResult['TEMPLATE_SERVER_ADDRESS'].'/bitrix/components/bitrix/imopenlines.mail.history/templates/.default/images/default_avatar.png')?>" alt="" width="34" height="34" style="border-radius: 50%; width: 34px; height: 34px"></td>
	</tr>
	<tr>
		<td colspan="3" style="height: 6px;"></td>
	</tr>
	<?} else {?>
	<tr>
		<td style="width: 50px; text-align: left;"><img src="<?=($message['AVATAR']? $message['AVATAR']: $arResult['TEMPLATE_SERVER_ADDRESS'].'/bitrix/components/bitrix/imopenlines.mail.history/templates/.default/images/default_avatar.png')?>" alt="" width="34" height="34" style="border-radius: 50%; width: 34px; height: 34px"></td>
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

<div style="width: 100%; border-bottom: 1px solid #EFF0F1;padding-top: 20px;margin-bottom: 30px;"></div>

<?if ($arResult['TEMPLATE_TYPE'] == 'HISTORY'):?>
<div style="text-align: center;padding-bottom: 40px;"><a href="<?=$arResult['TEMPLATE_WIDGET_URL']?>" style="color: #535C6A; text-decoration: underline; font-weight: 400; font-size: 13px; line-height: 15px; font-family: Helvetica, Arial, sans-serif;"><?=$mess['IMOL_MAIL_WRITE_TO_LINE']?></a></div>
<?else:?>
<div style="text-align: center; padding-bottom: 20px;"><a href="<?=$arResult['TEMPLATE_WIDGET_URL']?>" style="color: #FFFFFF; text-decoration: none; font-weight: 400; font-size: 13px; line-height: 17px; font-family: Helvetica, Arial, sans-serif; display: inline-block; padding: 11px 24px; background-color: #3BC8F5; border-radius:2px"><?=$mess['IMOL_MAIL_BACK_TO_TALK']?></a></div>
<?endif;?>

<div style="width: 100%;margin-bottom: 10px;"><span style="color: #A3ACB2; font-weight: 300; font-size: 11px; line-height: 15px; font-family: Helvetica, Arial, sans-serif;"><?=$mess['IMOL_MAIL_DONT_REPLY_NEW']?></span></div>