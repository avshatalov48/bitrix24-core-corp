<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if ($this->startResultCache(600))
{
	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->RegisterTag('intranet_ustat');
	}

	if (!CModule::IncludeModule('intranet'))
	{
		$this->abortResultCache();
		return;
	}

	$arResult['STATUS_INFO'] = \Bitrix\Intranet\UStat\UStat::getStatusInformation();

	$this->IncludeComponentTemplate();
}

$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/intranet.ustat/style.css');

return $arResult['STATUS_INFO'];
