<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

$sTplDir = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/")));

$arParams["TASK_ID"] = intval($arResult["VARIABLES"]["ID"]);
$arParams["USER_ID"] = intval(empty($arParams["USER_ID"]) ? $GLOBALS["USER"]->GetID() : $arParams["USER_ID"]);

if ($arParams["TASK_ID"] > 0)
{
	$dbTask = CBPTaskService::GetList(
		array(),
		array("ID" => $arParams["TASK_ID"], "USER_ID" => $arParams["USER_ID"]),
		false,
		false,
		array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS")
	);
	$arResult["TASK"] = $dbTask->GetNext();
}
if ($arResult["TASK"])
{
    $docID = $arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"][2];

    $arResult["VARIABLES"]["ELEMENT_ID"] = $docID;
    $arResult["VARIABLES"]["ACTION"] = "EDIT";

    $arInfo = include($sTplDir."tab_edit.php");
    if ($arParams["WORKFLOW"] == "bizproc")
    { 
        include($sTplDir."tab_bizproc_history.php");
        include($sTplDir."tab_bizproc_task.php");
        include($sTplDir."tab_versions.php");
    }
    elseif ($arParams["WORKFLOW"] == "workflow")
    {
        include($sTplDir."tab_workflow_history.php");
    }
    else
    {
        include($sTplDir."tab_bizproc_history.php");
    }

    include($sTplDir."tab_comments.php");

    if (!isset($_GET[$arParams["FORM_ID"].'_active_tab']))
        $_REQUEST[$arParams["FORM_ID"].'_active_tab'] = "tab_bizproc_view";

    if (!$arParams["FORM_ID"]) $arParams["FORM_ID"] = "element";
    $APPLICATION->IncludeComponent(
        "bitrix:main.interface.form",
        "",
        array(
            "SHOW_FORM_TAG" => "N",
            "FORM_ID" => $arParams["FORM_ID"],
            "TABS" => $this->__component->arResult['TABS'],
            "DATA" => $this->__component->arResult['DATA'],
        ),
        ($this->__component->__parent ? $this->__component->__parent : $component)
    ); 
}
else
{
    $back_url = (isset($_REQUEST["back_url"])) ? urldecode($_REQUEST["back_url"]) : $arParams["OBJECT"]->base_url;
    LocalRedirect($back_url);
}
?>
