<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->IncludeComponent("bitrix:main.mail.blacklist",
	".default",
	[
		'SET_TITLE'=>'Y'
	]);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");