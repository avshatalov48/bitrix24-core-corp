<?php
$MESS["CRM_ORDER_STATUS_N"] = "Принят, ожидается оплата";
$MESS["CRM_ORDER_STATUS_P"] = "Оплачен, формируется к отправке";
$MESS["CRM_ORDER_STATUS_F"] = "Выполнен";
$MESS["CRM_ORDER_STATUS_D"] = "Отменён";
$MESS["CRM_ORDER_SHIPMENT_STATUS_DN"] = "Ожидает обработки";
$MESS["CRM_ORDER_SHIPMENT_STATUS_DF"] = "Отгружен";
$MESS["CRM_ORDER_SHIPMENT_STATUS_DD"] = "Отменён";

$MESS["SALE_USER_GROUP_SHOP_BUYER_NAME"] = "Все покупатели";
$MESS["SALE_USER_GROUP_SHOP_BUYER_DESC"] = "Группа пользователей, содержащая всех покупателей магазина";

$MESS["SALE_USER_GROUP_SHOP_ADMIN_NAME"] = "Администраторы магазина";
$MESS["SALE_USER_GROUP_SHOP_MANAGER_NAME"] = "Менеджеры магазина";
$MESS["SALE_USER_GROUP_SHOP_ADMIN_DESC"] = "Группа пользователей, которые могут настраивать магазин";
$MESS["SALE_USER_GROUP_SHOP_MANAGER_DESC"] = "Группа пользователей, которые могут работать с магазином";

//region Enable sale events
$MESS["UP_TYPE_SUBJECT"] = "Уведомление о поступлении товара";
$MESS["UP_TYPE_SUBJECT_DESC"] = "#USER_NAME# - имя пользователя
#EMAIL# - email пользователя
#NAME# - название товара
#PAGE_URL# - детальная страница товара";
$MESS["SMAIL_FOOTER_BR"] = "С уважением,<br />администрация";
$MESS["SMAIL_FOOTER_SHOP"] = "Интернет-магазина";

$MESS["SALE_CHECK_PRINT_ERROR_TYPE_NAME"] = "Уведомление об ошибке при печати чека";
$MESS["SALE_CHECK_PRINT_ERROR_TYPE_DESC"] = "#ORDER_ACCOUNT_NUMBER# - код заказа
#ORDER_DATE# - дата заказа
#ORDER_ID# - ID заказа
#CHECK_ID# - номер чека";
$MESS["SALE_CHECK_PRINT_ERROR_SUBJECT"] = "Ошибка при печати чека";
$MESS["SALE_CHECK_PRINT_ERROR_HTML_TITLE"] = "Ошибка при печати чека";
$MESS["SALE_CHECK_PRINT_ERROR_HTML_SUB_TITLE"] = "Здравствуйте!";
$MESS["SALE_CHECK_PRINT_ERROR_HTML_TEXT"] = "
По какой-то причине чек №#CHECK_ID# по заказу №#ORDER_ACCOUNT_NUMBER# от #ORDER_DATE# не удалось распечатать!

Перейдите по ссылке, чтобы устранить причину возникшей ситуации:
#LINK_URL#
";
//endregion Enable sale events
