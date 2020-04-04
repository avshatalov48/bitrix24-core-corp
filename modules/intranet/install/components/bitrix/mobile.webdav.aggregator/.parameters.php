<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
    return;

if (!function_exists('_filterNameTemplates'))
{
    function _filterNameTemplates($nameTemplates)
    {
        $result = array();
        foreach ($nameTemplates as $key => $value)
        {
            if (preg_match("/(\.)|(\,)|(_SHORT)/", $key) == 0)
                $result[$key] = $value;
        }
        return $result;
    }
}

$arIBlockType = array();
$sIBlockType = "";
$arIBlock = array();
$iIblockDefault = 0;
$arCurrentValues["SEF_MODE"] = "Y"; 
$bIBlock = true; 
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
    if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
    {
        if ($sIBlockType == "")
            $sIBlockType = $arr["ID"];
        $arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
    }
}

$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE" => "Y"));

while($arr=$rsIBlock->Fetch())
{
    if ($iIblockDefault <= 0)
        $iIblockDefault = intVal($arr["ID"]);
    $arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}

$arComponentParameters = array(
    "GROUPS" => array(),
    "PARAMETERS" => array(
    "SEF_MODE" => array(
        "USER_FILE_PATH" => array(
            "NAME" => GetMessage("WD_USER_FILE_PATH"),
            "DEFAULT" => "company/personal/user/#USER_ID#/files/lib/#PATH#",
            "VARIABLES" => array("USER_ID", "PATH")),
        "GROUP_FILE_PATH" => array(
            "NAME" => GetMessage("WD_GROUP_FILE_PATH"),
            "DEFAULT" => "workgroups/group/#GROUP_ID#/files/#PATH#",
            "VARIABLES" => array("ELEMENT_ID")),
    ),
    "IBLOCK_TYPE" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("WD_IBLOCK_TYPE"),
        "TYPE" => "LIST",
        "VALUES" => $arIBlockType,
        "REFRESH" => "Y",
        "DEFAULT" => $sIBlockType, 
        "HIDDEN" => ($bIBlock ? "N" : "Y")),
    "IBLOCK_OTHER_IDS" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("WD_IBLOCK_OTHER_ID"),
        "TYPE" => "LIST",
        "MULTIPLE" => "Y",
        "VALUES" => $arIBlock, 
        "DEFAULT" => $iIblockDefault, 
        "HIDDEN" => ($bIBlock ? "N" : "Y")),
    "IBLOCK_GROUP_ID" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("WD_IBLOCK_GROUP_ID"),
        "TYPE" => "LIST",
        "VALUES" => $arIBlock, 
        "DEFAULT" => $iIblockDefault, 
        "HIDDEN" => ($bIBlock ? "N" : "Y")),
    "IBLOCK_USER_ID" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("WD_IBLOCK_USER_ID"),
        "TYPE" => "LIST",
        "VALUES" => $arIBlock, 
        "DEFAULT" => $iIblockDefault, 
        "HIDDEN" => ($bIBlock ? "N" : "Y")),
    "NAME_TEMPLATE" => array(
        "PARENT" => "BASE",
        "TYPE" => "LIST",
        "NAME" => GetMessage("WD_NAME_TEMPLATE"),
        "VALUES" => _filterNameTemplates(CComponentUtil::GetDefaultNameTemplates()),
        "MULTIPLE" => "N",
        "ADDITIONAL_VALUES" => "N",
        "DEFAULT" => "",),
    "CACHE_TIME"  =>  Array("DEFAULT"=>3600),
    ),
);

if (!CBXFeatures::IsFeatureEnabled("Workgroups"))
    unset($arComponentParameters["PARAMETERS"]["IBLOCK_GROUP_ID"]);   

?>
