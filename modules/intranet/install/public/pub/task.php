<?php

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

require 'page_include.php';

if ($hasAccess)
{
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.iframe.popup",
		"public",
		array(),
		null,
		array("HIDE_ICONS" => "Y")
	);
}
else
{
	$arReturn = array('ERROR_CODE' => !$USER->isAuthorized() ? 'NO_AUTH' : 'NO_RIGHTS');
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
