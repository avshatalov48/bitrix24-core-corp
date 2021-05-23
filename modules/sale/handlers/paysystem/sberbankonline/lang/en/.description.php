<?php
$MESS["SALE_HPS_SBERBANK"] = "Sberbank";
$MESS["SALE_HPS_SBERBANK_CHANGE_STATUS_PAY"] = "Auto change order status to paid when payment success status is received";
$MESS["SALE_HPS_SBERBANK_DESCRIPTION_MAIN"] = "To have the order status change, configure <a href='https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start' target='_blank'>Callback notifications</a> to include checksum and the \"bx_paysystem_code\" parameter.";
$MESS["SALE_HPS_SBERBANK_LOGIN"] = "Login";
$MESS["SALE_HPS_SBERBANK_LOGIN_DESC"] = "Login";
$MESS["SALE_HPS_SBERBANK_ORDER_DESCRIPTION"] = "Order description";
$MESS["SALE_HPS_SBERBANK_ORDER_DESCRIPTION_DESC"] = "Only the first 24 characters on this field are passed on to the Sberbank processing. Please contact the helpdesk if you want to submit this field to the processing. The following macros are possible: #PAYMENT_ID# - payment ID, #ORDER_ID# - order ID, #PAYMENT_NUMBER# - payment no., #ORDER_NUMBER# - order no., #USER_EMAIL# - customer email";
$MESS["SALE_HPS_SBERBANK_ORDER_DESCRIPTION_TEMPLATE"] = "Payment ##PAYMENT_NUMBER# for order ##ORDER_NUMBER# for #USER_EMAIL#";
$MESS["SALE_HPS_SBERBANK_PASSWORD"] = "Password";
$MESS["SALE_HPS_SBERBANK_PASSWORD_DESC"] = "Password";
$MESS["SALE_HPS_SBERBANK_RETURN_FAIL_URL"] = "Redirect customer to this page upon unsuccessful payment";
$MESS["SALE_HPS_SBERBANK_RETURN_FAIL_URL_DESC"] = "A fully qualified address including protocol is required. Leave this field empty to redirect customer to a page from which payment was initiated";
$MESS["SALE_HPS_SBERBANK_RETURN_SUCCESS_URL"] = "Redirect customer to this page upon successful payment";
$MESS["SALE_HPS_SBERBANK_RETURN_SUCCESS_URL_DESC"] = "A fully qualified address including protocol is required. Leave this field empty to redirect customer to a page from which payment was initiated";
$MESS["SALE_HPS_SBERBANK_SECRET_KEY"] = "Secret key";
$MESS["SALE_HPS_SBERBANK_SECRET_KEY_DESC"] = "Specify when using callback notifications with checksum";
$MESS["SALE_HPS_SBERBANK_TEST_MODE"] = "Test mode";
$MESS["SALE_HPS_SBERBANK_TEST_MODE_DESC"] = "Select this option to enable test mode.";
