<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
	"CONNECT_PAGE" => "Y"
));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");