<?
if(defined('BX24_HOST_NAME'))
	$serverUri = 'https://' . BX24_HOST_NAME;
else
	$serverUri = '';

$imconnector_default_option = array(
	"debug" => "N",
	"uri_client" => $serverUri,
	"list_connector" => ''
);