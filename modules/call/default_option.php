<?php
$call_default_option = array(
	'call_server_large_room' => 1000,
);

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/call_options.php"))
{
	$additionalOptions = include($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/call_options.php");
	if (is_array($additionalOptions))
	{
		$call_default_option = array_merge($call_default_option, $additionalOptions);
	}
}