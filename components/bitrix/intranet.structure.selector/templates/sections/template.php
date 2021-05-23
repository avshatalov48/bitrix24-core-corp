<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $INTRANET_TOOLBAR;
$INTRANET_TOOLBAR->Show();
global $APPLICATION;

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

if (!function_exists('intr_SelectorTplShowSection'))
{
	function intr_SelectorTplShowSection($ID, $arSections, $arParams, $arResult)
	{
		if (0 == $ID)
		{
			$num = count($arSections[$ID]);

			if ($num <= 0) return;

			$num_per_column = ceil($num/$arParams['COLUMNS']);
			$width = floor(90 / ($num_per_column > 1 ? $arParams['COLUMNS'] : $num));
		}

		if (is_array($arSections[$ID]))
		{
			echo '<ul'.($ID <= 0 ? ' class="bx-users-sections" style="width: '.$width.'%"' : '').'>';

			$cnt = 0;
			foreach ($arSections[$ID] as $key => $arSect)
			{
				$arClassName = array();

				if ($ID <= 0)
					$arClassName[] = 'bx-users-section-top';

				if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] == $arSect['ID'])
					$arClassName[] = 'bx-users-section-current selected';

				echo '<li'.(count($arClassName) > 0 ? ' class="'.implode(' ', $arClassName).'"' : '').'>';

				echo '<a href="'.$arParams['LIST_URL'].'?set_filter_'.$arParams['FILTER_NAME'].'=Y&'.$arParams['FILTER_NAME'].'_UF_DEPARTMENT='.intval($arSect['ID']).GetFilterParams($arResult['FILTER_PARAMS']).'"'.(count($arClassName) > 0 ? ' class="'.implode(' ', $arClassName).'"' : '').'>'.htmlspecialcharsbx($arSect['NAME']).'</a>';

				intr_SelectorTplShowSection($arSect['ID'], $arSections, $arParams, $arResult);

				echo '</li>';

				if ($ID == 0 && (++$cnt) >= $num_per_column)
				{
					echo '</ul>';
					if ($key < $num-1) echo '<ul class="bx-users-sections" style="width: '.$width.'%">';
					$cnt = 0;
				}

			}

			echo '</ul>';
		}
	}
}

if (count($arResult['SECTIONS_CHAIN']) > 1):
?>
<div class="bx-user-sections-chain">
<?
	foreach ($arResult['SECTIONS_CHAIN'] as $key => $arItem)
	{
		?><?=$key > 0 ? '&nbsp;|&nbsp;' : ''?><a href="<?=$arItem[2]?>"><?=htmlspecialcharsex($arItem[1])?></a><?
	}
?>
</div>
<?
endif;

if (isset($arResult['TOP_SECTION']) && $arResult['TOP_SECTION'] > 0):
?>
<div class="bx-user-section-alltop"><a href="<?=$arParams['LIST_URL']?>?set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT=<?=intval($arResult['SECTION_DATA']['ID']).GetFilterParams($arResult['FILTER_PARAMS'])?>"><?=htmlspecialcharsbx($arResult['SECTION_DATA']['NAME'])?></a></div>
<?
endif;

if ($arParams['SHOW_SECTION_INFO'] == 'Y' && is_array($arResult['SECTION_DATA']) && ($arResult['SECTION_DATA']['DETAIL_PICTURE'] || $arResult['SECTION_DATA']['DESCRIPTION'])):
?>
<div class="bx-user-section-info">
<?
	if ($arResult['SECTION_DATA']['DETAIL_PICTURE']):
?>
	<div class="bx-user-section-picture"><?echo $arResult['SECTION_DATA']['DETAIL_PICTURE']?></div>
<?
	endif;
	if ($arResult['SECTION_DATA']['DESCRIPTION']):
?>
	<div class="bx-user-section-text"><?echo ($arResult['SECTION_DATA']['DESCRIPTION_TYPE'] == 'text' ? htmlspecialcharsex($arResult['SECTION_DATA']['DESCRIPTION']) : $arResult['SECTION_DATA']['DESCRIPTION'])?></div>
<?
	endif;
?>
<div class="bx-user-section-delimiter"></div>
</div>
<?
endif;

intr_SelectorTplShowSection(0, $arResult['SECTIONS'], $arParams, $arResult);
?>
<div class="bx-user-section-delimiter"></div>
