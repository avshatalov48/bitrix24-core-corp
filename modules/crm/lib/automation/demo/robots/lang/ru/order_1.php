<?php
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_EMAIL_TITLE'] = 'Отправить письмо';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_TITLE'] = '{=Document:SHOP_TITLE}: Новый заказ N{=Document:ACCOUNT_NUMBER}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_NEW_ORDER_PAYMENT_TITLE'] = '{=Document:SHOP_TITLE}: Напоминание об оплате заказа N{=Document:ACCOUNT_NUMBER}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_ORDER_PAYED_TITLE'] = '{=Document:SHOP_TITLE}: Заказ N{=Document:ACCOUNT_NUMBER} оплачен';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_ORDER_CANCELED_TITLE'] = '{=Document:SHOP_TITLE}: Отмена заказа N{=Document:ACCOUNT_NUMBER}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_ALLOW_DELIVERY_TITLE'] = 'Разрешить доставку заказа';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_FOOTER'] = 'С уважением,<br> администрация #A1#Интернет-магазина#A2#';


$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_1_TITLE'] = 'Вами оформлен заказ в магазине {=Document:SHOP_TITLE}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_1_BODY'] = '<p style="margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;">
Уважаемый {=Document:CONTACT.NAME} {=Document:CONTACT.SECOND_NAME} {=Document:CONTACT.LAST_NAME},
</p>
<p style="margin-top: 0; margin-bottom: 20px; line-height: 20px;">
Ваш заказ номер {=Document:ACCOUNT_NUMBER} от {=Document:DATE_INSERT} принят.<br>
<br>
Стоимость заказа: {=Document:PRICE_FORMATTED}.<br>
<br>
Вы можете следить за выполнением своего заказа (на какой стадии выполнения он находится), войдя в Ваш персональный раздел сайта {=Document:SHOP_TITLE}.<br>
<br>
Обратите внимание, что для входа в этот раздел Вам необходимо будет ввести логин и пароль пользователя сайта {=Document:SHOP_TITLE}.<br>
<br>
Для того, чтобы аннулировать заказ, воспользуйтесь функцией отмены заказа, которая доступна в Вашем персональном разделе сайта {=Document:SHOP_TITLE}.<br>
<br>
Пожалуйста, при обращении к администрации сайта {=Document:SHOP_TITLE} ОБЯЗАТЕЛЬНО указывайте номер Вашего заказа - {=Document:ACCOUNT_NUMBER}.<br>
<br>
Спасибо за покупку!<br>
</p>';

$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_2_TITLE'] = 'Напоминаем вам об оплате заказа на сайте {=Document:SHOP_TITLE}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_2_BODY'] = '<p style="margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;">
Уважаемый {=Document:CONTACT.NAME} {=Document:CONTACT.SECOND_NAME} {=Document:CONTACT.LAST_NAME},
</p>
<p style="margin-top: 0; margin-bottom: 20px; line-height: 20px;">
Вами был оформлен заказ N{=Document:ACCOUNT_NUMBER} от {=Document:DATE_INSERT} на сумму {=Document:PRICE_FORMATTED}.<br>
<br>
К сожалению, на сегодняшний день средства по этому заказу не поступили к нам.<br>
<br>
Вы можете следить за выполнением своего заказа (на какой стадии выполнения он находится), войдя в Ваш персональный раздел сайта {=Document:SHOP_TITLE}.<br>
<br>
Обратите внимание, что для входа в этот раздел Вам необходимо будет ввести логин и пароль пользователя сайта {=Document:SHOP_TITLE}.<br>
<br>
Для того, чтобы аннулировать заказ, воспользуйтесь функцией отмены заказа, которая доступна в Вашем персональном разделе сайта {=Document:SHOP_TITLE}.<br>
<br>
Пожалуйста, при обращении к администрации сайта {=Document:SHOP_TITLE} ОБЯЗАТЕЛЬНО указывайте номер Вашего заказа - {=Document:ACCOUNT_NUMBER}.<br>
<br>
Спасибо за покупку!<br>
</p>';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_3_TITLE'] = 'Вы оплатили заказ на сайте {=Document:SHOP_TITLE}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_3_BODY'] = '<p style="margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;">
Заказ номер {=Document:ACCOUNT_NUMBER} от {=Document:DATE_INSERT} оплачен.
</p>
<p style="margin-top: 0; margin-bottom: 20px; line-height: 20px;">
Для получения подробной информации по заказу пройдите на сайт {=Document:SHOP_PUBLIC_URL}
</p>';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_4_TITLE'] = '{=Document:SHOP_TITLE}: Отмена заказа N{=Document:ACCOUNT_NUMBER}';
$MESS['CRM_AUTOMATION_DEMO_ORDER_1_MAIL_4_BODY'] = '<p style="margin-top:30px; margin-bottom: 28px; font-weight: bold; font-size: 19px;">
Заказ номер {=Document:ACCOUNT_NUMBER} от {=Document:DATE_INSERT} отменен.
</p>
<p style="margin-top: 0; margin-bottom: 20px; line-height: 20px;">
{=Document:REASON_CANCELED}<br>
<br>
Для получения подробной информации по заказу пройдите на сайт {=Document:SHOP_PUBLIC_URL}
</p>';

