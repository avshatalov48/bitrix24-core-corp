<?
define("BX_XMPP_SERVER_DOMAIN", "192.168.0.8");

$arClasses = array(
	"CXMPPClient" => "classes/client.php",
	"CXMPPFactory" => "classes/factory.php",
	"CXMPPServer" => "classes/server.php",
	"CXMPPParser" => "classes/parser.php",
	"CXMPPUtility" => "classes/util.php",
	"CXMPPFactoryHandler" => "classes/interface.php",
);
CModule::AddAutoloadClasses("xmpp", $arClasses);
?>