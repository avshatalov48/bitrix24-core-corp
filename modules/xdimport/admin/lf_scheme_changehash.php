<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!CModule::IncludeModule('xdimport') || !$USER->IsAdmin() || !check_bitrix_sessid())
	die();

if (intval($_POST["scheme_id"]) > 0)
{
		$new_hash = md5(randString(20));
		$ob = new CXDILFScheme();
		$arFields = array(
			"HASH" => $new_hash
		);
						
		$res = $ob->Update($_POST["scheme_id"], $arFields);
		if($res > 0)		
			echo $new_hash;
}
?>