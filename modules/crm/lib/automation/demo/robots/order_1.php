<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$getMailBody = function ($id)
{
	$title = Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_MAIL_'.$id.'_TITLE');
	$body = Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_MAIL_'.$id.'_BODY');
	$footer = Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_FOOTER', [
		'#A1#' => '<a href="{=Document:SHOP_PUBLIC_URL}" style="color:#2e6eb6;">',
		'#A2#' => '</a>'
	]);

	$mailBody = <<<HTML
<style>
	body
	{
		font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
		font-size: 14px;
		color: #000;
	}
</style>
<table cellpadding="0" cellspacing="0" width="850" style="background-color: #d1d1d1; border-radius: 2px; border:1px solid #d1d1d1; margin: 0 auto;" border="1" bordercolor="#d1d1d1">
	<tbody>
	<tr>
		<td height="83" width="850" bgcolor="#eaf3f5" style="border: none; padding-top: 23px; padding-right: 17px; padding-bottom: 24px; padding-left: 17px;">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tbody>
				<tr>
					<td bgcolor="#ffffff" height="75" style="font-weight: bold; text-align: center; font-size: 26px; color: #0b3961;">
						{$title}
					</td>
				</tr>
				<tr>
					<td bgcolor="#bad3df" height="11">
					</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td width="850" bgcolor="#f7f7f7" valign="top" style="border: none; padding-top: 0; padding-right: 44px; padding-bottom: 16px; padding-left: 44px;">
			{$body}
		</td>
	</tr>
	<tr>
		<td height="40px" width="850" bgcolor="#f7f7f7" valign="top" style="border: none; padding-top: 0; padding-right: 44px; padding-bottom: 30px; padding-left: 44px;">
			<p style="border-top: 1px solid #d1d1d1; margin-bottom: 5px; margin-top: 0; padding-top: 20px; line-height:21px;">
				{$footer}<br>
				E-mail: <a href="mailto:{=Document:RESPONSIBLE_ID.EMAIL}" style="color:#2e6eb6;">{=Document:RESPONSIBLE_ID.EMAIL}</a>
			</p>
		</td>
	</tr>
	</tbody>
</table>
HTML;
	return 'base64,' . base64_encode($mailBody);
};

$runtime = CBPRuntime::getRuntime();

$email = $runtime->getActivityDescription('CrmSendEmailActivity');
$allowDelivery = $runtime->getActivityDescription('CrmSetOrderAllowDelivery');

$emailTitle = $email['NAME'] ?? Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_EMAIL_TITLE');
$deliveryTitle = $allowDelivery['NAME'] ?? Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_ALLOW_DELIVERY_TITLE');

return array(
	'N' => array(
		array (
			'Type' => 'CrmSendEmailActivity',
			'Properties' =>
				array (
					'Subject' => Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_TITLE'),
					'MessageText' => $getMailBody(1),
					'MessageTextType' => 'html',
					'AttachmentType' => '',
					'MessageFrom' => '',
					'MessageTextEncoded' => '1',
					'Attachment' => '',
					'Title' => $emailTitle,
				),
			'Name' => 'A55212_94855_26115_62703',
			'Condition' =>
				[
					'type' => 'field',
					'items' =>
						[
							[['field' => 'SHOP_TITLE', 'operator' => '!empty', 'value' => ''], 'AND']
						]
				],
		),
		array (
			'Type' => 'CrmSendEmailActivity',
			'Properties' =>
				array (
					'Subject' => Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_PAYMENT_TITLE'),
					'MessageText' => $getMailBody(2),
					'MessageTextType' => 'html',
					'AttachmentType' => '',
					'MessageFrom' => '',
					'MessageTextEncoded' => '1',
					'Attachment' => '',
					'Title' => $emailTitle,
				),
			'Name' => 'A6399_28970_46418_16409',
			'Delay' =>
				array (
					'type' => 'after',
					'value' => '3',
					'valueType' => 'd',
					'basis' => '{=System:Now}',
					'workTime' => '0',
					'localTime' => '0',
				),
			'DelayName' => 'A58433_73648_64985_73308',
			'Condition' =>
				[
					'type' => 'field',
					'items' =>
						[
							[['field' => 'SHOP_TITLE', 'operator' => '!empty', 'value' => ''], 'AND']
						]
				],
		),
	),
	'P' => array(
		array (
			'Type' => 'CrmSetOrderAllowDelivery',
			'Properties' =>
				array (
					'Title' => $deliveryTitle,
				),
			'Name' => 'A22097_33160_61403_50820',
		),
		array (
			'Type' => 'CrmSendEmailActivity',
			'Properties' =>
				array (
					'Subject' => Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_ORDER_PAYED_TITLE'),
					'MessageText' => $getMailBody(3),
					'MessageTextType' => 'html',
					'AttachmentType' => '',
					'MessageFrom' => '',
					'MessageTextEncoded' => '1',
					'Attachment' => '',
					'Title' => $emailTitle,
				),
			'Name' => 'A20358_3458_26512_35912',
			'Condition' =>
				[
					'type' => 'field',
					'items' =>
						[
							[['field' => 'SHOP_TITLE', 'operator' => '!empty', 'value' => ''], 'AND']
						]
				],
		),
	),
	'D' => array(
		array (
			'Type' => 'CrmSendEmailActivity',
			'Properties' =>
				array (
					'Subject' => Loc::getMessage('CRM_AUTOMATION_DEMO_ORDER_1_ORDER_CANCELED_TITLE'),
					'MessageText' => $getMailBody(4),
					'MessageTextType' => 'html',
					'AttachmentType' => '',
					'MessageFrom' => '',
					'MessageTextEncoded' => '1',
					'Attachment' => '',
					'Title' => $emailTitle,
				),
			'Name' => 'A35684_47771_46305_4412',
			'Condition' =>
				[
					'type' => 'field',
					'items' =>
						[
							[['field' => 'SHOP_TITLE', 'operator' => '!empty', 'value' => ''], 'AND']
						]
				],
		),
	)
);