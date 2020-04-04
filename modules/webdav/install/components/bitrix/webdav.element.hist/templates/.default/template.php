<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;


$arHeaders = array();
$arHeaders[] = array("id"=>"ID", "name"=>"ID", "default"=>true, "editable"=>true);
$arHeaders[] = array("id"=>"NAME", "name"=>GetMessage('WD_FILE_NAME'), "default"=>true);
if (isset($arParams["OBJECT"]))
    $arHeaders[] = array("id"=>"FILE_SIZE", "name"=>GetMessage('WD_FILE_SIZE'), "default"=>true);
$arHeaders[] = array("id"=>"WF_STATUS_ID", "name"=>GetMessage('WD_STATUS'), "default"=>true);
$arHeaders[] = array("id"=>"WF_COMMENTS", "name"=>GetMessage('WD_COMMENTS'), "default"=>true);
$arHeaders[] = array("id"=>"MODIFIED_BY", "name"=>GetMessage('WD_MODIFIED_BY'), "default"=>true);
$arHeaders[] = array("id"=>"TIMESTAMP_X", "name"=>GetMessage('WD_CHANGE_DATE'), "default"=>true);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>"webdav_".$arParams["IBLOCK_ID"],
		"HEADERS"=>$arHeaders,
		"SORT"=>array($by => $order),
		"ROWS"=>$arResult["VERSIONS_GRID"],
		"FOOTER"=>array(array("title"=>GetMessage("WD_ALL"), "value"=>count($arResult['VERSIONS_GRID']))),
		"ACTIONS"=>array(
			"delete"=>true, 
			"custom_html"=>'<input type="hidden" name="ELEMENT_ID" value="'.$arParams["ELEMENT_ID"].'" />
			',
		),
        "ACTION_ALL_ROWS" => false,
        "EDITABLE" => false,
        "NAV_OBJECT"=>$arResult["NAV_RESULT"],
        "AJAX_MODE"=>"N",
        "AJAX_OPTION_JUMP"=>"N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);
?>
