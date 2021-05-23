<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CUtil::InitJSCore();

if (is_array($arParams['ALPHABET_LANG'])) TrimArr($arParams['ALPHABET_LANG']);
if (!isset($arParams['ALPHABET_LANG']) || !is_array($arParams['ALPHABET_LANG']) || count($arParams['ALPHABET_LANG']) <= 0)
	$arParams['ALPHABET_LANG'] = array(LANGUAGE_ID);

$arResult['ALPHABET'] = array();
foreach ($arParams['ALPHABET_LANG'] as $key => $language_id)
{
	$file = dirname(__FILE__).'/lang/'.$language_id.'/template.php';
	if (file_exists($file))
	{
		if ($arMess = __IncludeLang($file, true));
		{
			$arResult['ALPHABET'][$language_id] = $arMess;
		}
	}
}
?>