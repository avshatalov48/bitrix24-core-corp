<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
{
	return;
}

$this->IncludeComponentTemplate();
?>