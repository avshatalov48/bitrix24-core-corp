<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arParams["PERMISSION"] < "W" || empty($_POST["action_button_".$arParams["GRID_ID"]]) || empty($_POST["ID"]))
{
	return true;
}
$arElements = array(); 
foreach ($_POST["ID"] as $id)
{
	if ($id > 0)
		$arElements[] = $id; 
}
$action = strtoupper($_POST["action_button_".$arParams["GRID_ID"]]); 

if (empty($arElements) || $action != "DELETE")
	return true;

$aMsg = array();
$this->IncludeComponentLang("action.php");
/************** Main errors ****************************************/
if (!check_bitrix_sessid())
{
	$aMsg[] = array(
		"id" => "SESSID", 
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
/************** Main errors ****************************************/

/************** Data errors ****************************************/
if (!empty($aMsg))
{}
elseif ($action == "DELETE") 
{
	@set_time_limit(1000);
	foreach ($arElements as $element_id):
		$result = $ob->DELETE($tmp = array("element_id" => $element_id)); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => $element_id,
				"text" => GetMessage("WD_ERROR_DELETE")." ".$result); 
		endif;
	endforeach;
}

if (!empty($aMsg))
{
	$e = new CAdminException($aMsg);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	return false;
}

LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], array("ELEMENT_ID" => $arParams["ELEMENT_ID"]))); 
?>