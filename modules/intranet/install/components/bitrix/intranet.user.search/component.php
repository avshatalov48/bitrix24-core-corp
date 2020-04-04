<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/*
$arParams['FORM_NAME'] - name of form
$arParams['INPUT_NAME'] - input name for value
$arParams['ONSELECT'] - js function or method which will be called in its own context when employee(s) will be selected
$arParams['SITE_ID'] - 
$arParams['IS_EXTRANET']

if SHOW_INPUT == 'Y', input field with employee user id will be created with INPUT_NAME name. Starting value should be specified in INPUT_VALUE param.

al least one of the {ONSELECT|INPUT_NAME} must be specified. both specified is legal.

1. If INPUT_NAME is specified, starting data is taken from it.
2. If INPUT_NAME is specified and none ONSELECT specified, resulting data written into it.
3. If INPUT_NAME and ONSELECT are both specified, resulting data is transferred to ONSELECT only. value of INPUT_NAME must be updated manually
4. If INPUT_NAME is not specified but ONSELECT is, starting data must be set via {OBJECT}.SetData(value). Resulting data is transferred to ONSELECT.
5. If none of the INPUT_NAME or ONSELECT is specified, it's illegal, cause there's no way to get data from form in this case.

Component returns object name to use.

If you don't wanna use standard button, you should specify 'SHOW_BUTTON'=>'N', and use {OBJECT}.Show() by your own way.
*/
$arParams['MULTIPLE'] = $arParams['MULTIPLE'] == 'Y' ? 'Y' : 'N'; // allow multiple user selection

$arParams['SHOW_BUTTON'] = $arParams['SHOW_BUTTON'] == 'N' ? 'N' : 'Y'; // show button for control run. Show() method should be used otherwise.
$arParams['SHOW_INPUT'] = $arParams['SHOW_INPUT'] == 'Y' ? 'Y' : 'N'; // whether to show input field.

$arParams['FORM_NAME'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['FORM_NAME']) ? $arParams['FORM_NAME'] : false;
$arParams['INPUT_NAME'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
if (empty($arParams['NAME']))
{
	$arParams['NAME'] = strtolower($arParams['INPUT_NAME']);
}
$arParams['SITE_ID'] = preg_match('/^[a-zA-Z0-9_]+$/', $arParams['SITE_ID']) ? $arParams['SITE_ID'] : false;
if(strlen($arParams['SITE_ID']) <= 0 || strlen($arParams['SITE_ID']) > 2)
	$arParams['SITE_ID'] = SITE_ID;
$arParams['IS_EXTRANET'] = $arParams['IS_EXTRANET'] == 'Y' ? 'Y' : 'N'; // whether to show input field.

if (!$arParams['INPUT_NAME'] && !$arParams['ONSELECT'])
	return false;

$arParams['GET_FULL_INFO'] = $arParams['GET_FULL_INFO'] == 'Y' ? 'Y' : 'N'; // whether onselect handler should take full info. legal only with ONSELECT.

$arParams['NAME'] = (($arParams['NAME'] && preg_match('/^[a-zA-Z0-9_]+$/', $arParams['NAME'])) ? $arParams['NAME'] : 'emp_selector_'.rand(0, 10000));

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['MULTIPLE'] == 'N')
	$arParams['INPUT_VALUE'] = intval($arParams['INPUT_VALUE']);
elseif (is_array($arParams['INPUT_VALUE']))
	$arParams['INPUT_VALUE'] = implode(', ', $arParams['INPUT_VALUE']);

CUtil::InitJSCore();

$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
$this->IncludeComponentTemplate();

return CUtil::JSEscape($arParams['NAME']);
?>