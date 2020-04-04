<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

\CJSCore::Init(['ui.icons', 'ui.hint']);

if (empty($arResult['PATH']))
{
	return;
}
?>
<span title="">
<?
$lastIndex = count($arResult['PATH']) - 1;
foreach ($arResult['PATH'] as $index => $item):
	$name = htmlspecialcharsbx($item['NAME']);
	$desc = htmlspecialcharsbx($item['DESC']);
	$icon = htmlspecialcharsbx($item['ICON']);
	$iconColor = htmlspecialcharsbx($item['ICON_COLOR']);

	if ($icon)
	{
		if (!empty($arParams['SKIP_SOURCE']))
		{
			continue;
		}

		?><span class="crm-tracking-entity-path-icon <?=$icon?>"
			<?if($desc):?>onmouseover="BX.UI.Hint.show(this, '<?=\CUtil::JSEscape($desc)?>');"
			onmouseout="BX.UI.Hint.hide();"<?endif;?>
			style="width: 14px; height: 14px; margin: 0 5px 0 0; transform: translateY(2px);"
		><i style="<?=($iconColor ? "background-color: $iconColor;" : '')?>"></i></span><?
	}

	if ($arParams['ONLY_SOURCE_ICON'])
	{
		break;
	}

	if ($name)
	{
		?><span	class="crm-tracking-entity-path-item" title=""
			<?if($desc):?>onmouseover="BX.UI.Hint.show(this, '<?=\CUtil::JSEscape($desc)?>');"
			onmouseout="BX.UI.Hint.hide();"<?endif;?>
		><?=$name?></span><?
	}

	if ($index < $lastIndex)
	{
		?><span class="crm-tracking-entity-path-arrow" title=""></span><?
	}
endforeach;
?>
</span>