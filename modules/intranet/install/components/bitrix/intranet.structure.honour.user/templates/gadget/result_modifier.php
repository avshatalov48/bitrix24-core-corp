<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$sanitizer = new CBXSanitizer();
$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);

foreach($arResult['ENTRIES'] as $key=>$val)
{
	$arResult['ENTRIES'][$key]['TITLE'] = $sanitizer->SanitizeHtml($val["TITLE"]);
}
?>