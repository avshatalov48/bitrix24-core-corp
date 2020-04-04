<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams["PATH_TO_USER"] = (isset($arParams["PATH_TO_USER"]) ? $arParams["PATH_TO_USER"] : SITE_DIR."company/personal/user/#user_id#/");
$arParams["PATH_TO_USER_EDIT"] = (isset($arParams["PATH_TO_USER_EDIT"]) ? $arParams["PATH_TO_USER_EDIT"] : SITE_DIR."company/personal/user/#user_id#/edit/");
$arParams["VIS_STRUCTURE_URL"] = (isset($arParams["VIS_STRUCTURE_URL"]) ? $arParams["VIS_STRUCTURE_URL"] : SITE_DIR."company/vis_structure.php");

$IBLOCK_PERMISSION = CIBlock::GetPermission(COption::GetOptionInt('intranet', 'iblock_structure'));
$arParams['bAdmin'] = $IBLOCK_PERMISSION >= 'U';

$iblockID = COption::GetOptionInt("intranet", "iblock_structure");
$db_up_department = CIBlockSection::GetList(Array(), Array("SECTION_ID"=>0, "IBLOCK_ID"=>$iblockID));
if ($ar_up_department = $db_up_department->Fetch())
{
	$arParams["TOP_DEPARTMENT"] = $ar_up_department['ID'];
}
?>