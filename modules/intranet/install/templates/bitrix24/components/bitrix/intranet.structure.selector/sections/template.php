<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/breadcrumbs.css");

if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'])
{
	if ($arKeys = array_keys($arResult['FILTER_PARAMS'], $arParams['FILTER_NAME'].'_UF_DEPARTMENT'))
	{
		foreach ($arKeys as $key)
		{
			unset($arResult['FILTER_PARAMS'][$key]);
		}
	}
}

if (count($arResult['SECTIONS_CHAIN']) > 0):?>
	<div class="breadcrumbs">
		<a class="breadcrumbs-home" href="<?=$arParams["VIS_STRUCTURE_URL"]?>"><i></i></a><?
		$last_section_key = count($arResult['SECTIONS_CHAIN']) - 1; 
		foreach ($arResult['SECTIONS_CHAIN'] as $key => $arItem)
		{    
			if ($key != $last_section_key):?><a class="breadcrumbs-item" href="<?=$arItem[2]?>"><?=$arItem[1]?><i></i></a><?else:?><span class="breadcrumbs-item-selected"><?=$arItem[1]?></span><?endif;			
		}
	?></div>
<?endif;
if (intval($arResult["SECTION_DATA"]["IBLOCK_SECTION_ID"]) > 0):?>
	<div class="department-unit-manager">
		<div class="department-titles"><?=GetMessage("INTR_STR_HEAD_DEPARTMENT")?></div>
		<span class=""><a class="department-link" href="<?=$arParams['LIST_URL']?>?set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT=<?=($arResult["SECTION_DATA"]["IBLOCK_SECTION_ID"].GetFilterParams($arResult["FILTER_PARAMS"]))?>"><?=$arResult["SECTION_DATA"]["IBLOCK_SECTION_NAME"]?></a>	 
		<?if (intval($arResult["SECTION_DATA"]["IBLOCK_SECTION_UF_HEAD"]) > 0):?>
		&nbsp;(<?=GetMessage("INTR_IS_TPL_SUB_DEP_HEAD")?> &ndash; <a href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arResult["SECTION_DATA"]["IBLOCK_SECTION_UF_HEAD"]))?>"><?=$arResult["SECTION_DATA"]["IBLOCK_SECTION_UF_HEAD_NAME"]?></a>)
		<?endif;?>
		</span>
	</div>
<?endif;
if (is_array($arResult["SECTION_DATA"]["UF_HEAD"])):
	$arHeadParams = $arParams;
	$arHeadParams["USER"] = $arResult["SECTION_DATA"]["UF_HEAD"];
	$APPLICATION->IncludeComponent("bitrix:intranet.system.person", "section_head", $arHeadParams);
endif; 
if ($arParams['SHOW_SECTION_INFO'] == 'Y' && is_array($arResult['SECTION_DATA']) && $arResult['SECTION_DATA']['DESCRIPTION']):?>
<div class="department-description">
	<div class="department-titles"><?=GetMessage("INTR_STR_ABOUT_DEPARTMENT")?></div>
	<div class="department-description-text">
	<?echo ($arResult['SECTION_DATA']['DESCRIPTION_TYPE'] == 'text' ? htmlspecialcharsex($arResult['SECTION_DATA']['DESCRIPTION']) : $arResult['SECTION_DATA']['DESCRIPTION'])?>
	</div>
</div>
<?endif;

if (count($arResult['SECTIONS']) > 0):
?>
<div class="department-subdivision-list">
	<div class="department-titles"><?=GetMessage("INTR_IS_TPL_SUB_DEPARTMENTS")?></div>
	<?foreach ($arResult['SECTIONS'] as $key => $arSect):?>
		<div class="">
			<a class="department-link" href="<?=$arParams['LIST_URL']?>?set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT=<?=(intval($arSect["ID"]).GetFilterParams($arResult["FILTER_PARAMS"]))?>"><?=$arSect['NAME']?></a>
			<?if ($arSect["UF_HEAD"] > 0):?>
			&nbsp;(<?=GetMessage("INTR_IS_TPL_SUB_DEP_HEAD")?> &ndash; <a href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arSect["UF_HEAD"]))?>"><?=$arSect["UF_HEAD_NAME"]?></a>)
			<?endif?>
		</div>
	<?endforeach?>
</div>
<?endif;?>
<?
if (intval($arResult['SECTION_DATA']["USER_COUNT"]) > 0):
?>  
	<div class="department-employee-list">
		<div class="department-titles"><?=GetMessage("INTR_IS_TPL_EMPLOYEES")?><?echo " (".$arResult['SECTION_DATA']["USER_COUNT"].")"?></div>
	<?
	if ($arResult["CURRENT_SECTION"]):
		$APPLICATION->IncludeComponent("bitrix:intranet.structure.list", "list", $arParams, $component);
	endif;    
	?>
	</div>
<?endif?>
