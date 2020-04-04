<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
if (!empty($arResult["ERROR_MESSAGE"])): 
	ShowError($arResult["ERROR_MESSAGE"]);
endif;
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID" => $arParams["GRID_ID"],
		"HEADERS" => array(
			array("id" => "ID", "name" => "ID", "sort" => "id", "default" => false), 
			array("id" => "NAME", "name" => GetMessage('WD_FILE_NAME'), "default" => true),
			array("id" => "TIMESTAMP_X", "name" => GetMessage('WD_CHANGE_DATE'), "default" => true), 
			array("id" => "MODIFIED_BY", "name" => GetMessage('WD_MODIFIED_BY'), "default" => true),
			array("id" => "COMMENTS", "name" => GetMessage('IBLIST_A_BP_H'), "default" => true),
		),
		"SORT" => array($by => $order),
		"ROWS" => $arResult["VERSIONS"],
		"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => count($arResult["VERSIONS"]))),
		"ACTIONS" => array(
			"delete" => true
		),
        "TAB_ID" => (isset($arParams["TAB_ID"]) ? $arParams["TAB_ID"] : ""),
        "FORM_ID" => (isset($arParams["FORM_ID"]) ? $arParams["FORM_ID"] : ""),
		"ACTION_ALL_ROWS" => false,
		"EDITABLE" => ($arParams["PERMISSION"] >= "W"),
		"NAV_OBJECT" => $arResult["NAV_RESULT"],
		"AJAX_MODE" => "N",
		"AJAX_OPTION_JUMP" => "N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);
if ($arResult["ELEMENT"]["SHOW"]["BP_CLONE"] == "Y" && $this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = (is_array($this->__component->__parent->arResult["arButtons"]) ? 
		$this->__component->__parent->arResult["arButtons"] : array()); 
    
    $arBtnBizprocClone = array(
		"TEXT" => GetMessage("WD_CREATE_VERSION"),
		"TITLE" => GetMessage("WD_CREATE_VERSION_ALT"),
		"LINK" => $arResult["URL"]["CLONE"],
		"ICON" => "btn-edit element-clone"); 

    if (isset($this->__component->__parent->arResult["arButtons"]["context"]))
    {
        $arTmp = array();
        foreach($this->__component->__parent->arResult["arButtons"] as $key => $value)
        {
            if ($key == 'context')
                $arTmp['bizproc_clone'] = $arBtnBizprocClone;
            $arTmp[$key] = $value;
        }
        $this->__component->__parent->arResult["arButtons"] = $arTmp;
    }
}
?>
