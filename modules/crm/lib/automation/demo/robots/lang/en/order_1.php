<?
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_EMAIL_TITLE"] = "Send message";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_TITLE"] = "{=Document:SHOP_TITLE}: New order #{=Document:ACCOUNT_NUMBER}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_PAYMENT_TITLE"] = "{=Document:SHOP_TITLE}: Order payment reminder #{=Document:ACCOUNT_NUMBER}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_ORDER_PAYED_TITLE"] = "{=Document:SHOP_TITLE}: Order #{=Document:ACCOUNT_NUMBER} has been paid.";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_ORDER_CANCELED_TITLE"] = "{=Document:SHOP_TITLE}: Cancel order #{=Document:ACCOUNT_NUMBER}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_ALLOW_DELIVERY_TITLE"] = "Allow delivery";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_FOOTER"] = "Best regards,<br> #A1#Online store#A2#";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_1_TITLE"] = "You have placed an order with {=Document:SHOP_TITLE}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_1_BODY"] = "<p style=\"margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;\">
Dear {=Document:CONTACT.NAME} {=Document:CONTACT.SECOND_NAME} {=Document:CONTACT.LAST_NAME},
</p>
<p style=\"margin-top: 0; margin-bottom: 20px; line-height: 20px;\">
Your order #{=Document:ACCOUNT_NUMBER} from {=Document:DATE_INSERT} has been confirmed.<br>
<br>
Order total: {=Document:PRICE_FORMATTED}.<br>
<br>
You can track your order progress in your account on {=Document:SHOP_TITLE}.<br>
<br>
Note that you will need your login and password you used to register on {=Document:SHOP_TITLE}.<br>
<br>
If you wish to cancel your order, you can do so in your personal account.<br>
<br>
Please make sure you mention your order #{=Document:ACCOUNT_NUMBER} whenever you contact {=Document:SHOP_TITLE} administration.<br>
<br>
Thank you for your order!<br>
</p>";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_2_TITLE"] = "Don't forget to pay your order with {=Document:SHOP_TITLE}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_2_BODY"] = "<p style=\"margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;\">
Dear {=Document:CONTACT.NAME} {=Document:CONTACT.SECOND_NAME} {=Document:CONTACT.LAST_NAME},
</p>
<p style=\"margin-top: 0; margin-bottom: 20px; line-height: 20px;\">
You placed an order #{=Document:ACCOUNT_NUMBER} on {=Document:DATE_INSERT} for {=Document:PRICE_FORMATTED}.<br>
<br>
Unfortunately you haven't pay for it yet.<br>
<br>
You can track your order progress in your account on {=Document:SHOP_TITLE}.<br>
<br>
Note that you will need your login and password you used to register on {=Document:SHOP_TITLE}.<br>
<br>
If you wish to cancel your order, you can do so in your personal account.<br>
<br>
Please make sure you mention your order #{=Document:ACCOUNT_NUMBER} whenever you contact {=Document:SHOP_TITLE} administration.<br>
<br>
Thank you for your order!<br>
</p>";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_3_TITLE"] = "Your payment for order with {=Document:SHOP_TITLE}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_3_BODY"] = "<p style=\"margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;\">
Order #{=Document:ACCOUNT_NUMBER} of {=Document:DATE_INSERT} has been paid.
</p>
<p style=\"margin-top: 0; margin-bottom: 20px; line-height: 20px;\">
Use this link for more information: {=Document:SHOP_PUBLIC_URL}
</p>";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_4_TITLE"] = "{=Document:SHOP_TITLE}: Cancel order #{=Document:ACCOUNT_NUMBER}";
$MESS["CRM_AUTOMATION_DEMO_ORDER_1_MAIL_4_BODY"] = "<p style=\"margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;\">
Order #{=Document:ACCOUNT_NUMBER} of {=Document:DATE_INSERT} has been canceled.
</p>
<p style=\"margin-top: 0; margin-bottom: 20px; line-height: 20px;\">
{=Document:REASON_CANCELED}<br>
<br>
Use this link for more information: {=Document:SHOP_PUBLIC_URL}
</p>";
?>