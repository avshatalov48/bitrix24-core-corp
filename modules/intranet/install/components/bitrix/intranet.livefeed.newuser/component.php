<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (isset($arParams['USER']))
{
	$arParams['USER']['TYPE'] = "";

	if (
		IsModuleInstalled("mail")
		&& isset($arParams['USER']['EXTERNAL_AUTH_ID'])
		&& $arParams['USER']['EXTERNAL_AUTH_ID'] == 'email'

	)
	{
		$arParams['USER']['TYPE'] = 'email';
	}
	elseif (
		IsModuleInstalled("extranet")
		&& (
			!isset($arParams['USER']['UF_DEPARTMENT'])
			|| !is_array($arParams['USER']['UF_DEPARTMENT'])
			|| empty($arParams['USER']['UF_DEPARTMENT'])
		)
	)
	{
		$arParams['USER']['TYPE'] = 'extranet';
	}
}
$this->IncludeComponentTemplate();
?>