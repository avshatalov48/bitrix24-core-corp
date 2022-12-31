<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);
?>

<div class="sitemap-window">
	<div class="sitemap-content"><?
	$previousLevel = 0;
	foreach ($arResult['MAP_ITEMS'] as $index => $item):
		if ($item['PERMISSION'] <= 'D')
		{
			continue;
		}

		$link = $item['PARAMS']['real_link'] ?? $item['LINK'];
		$link = htmlspecialcharsbx($link, ENT_COMPAT, false);
		$link = $link === '' ? '#' : $link;

		$title = htmlspecialcharsbx($item['TEXT'], ENT_COMPAT, false);
		$depthLevel = $item['DEPTH_LEVEL'];
		$hasChildren =
			isset($arResult['MAP_ITEMS'][$index + 1])
			&& $arResult['MAP_ITEMS'][$index + 1]['DEPTH_LEVEL'] > $depthLevel
		;

		if ($previousLevel && $depthLevel < $previousLevel):
			?><?=str_repeat('</div></div>', ($previousLevel - $depthLevel))?><?
		endif;

		if ($depthLevel === 1):
			?><div class="sitemap-section"><?
				?><a class="sitemap-section-title" href="<?=$link?>" target="_top"><?=$title?></a><?
				if ($hasChildren):
					?><div class="sitemap-section-items"><?
				else:
					?></div><?
				endif;
		else:
			?><div class="sitemap-section-item<? if ($hasChildren): ?> --has-children<? endif ?>"><?
				?><a
					class="sitemap-section-item-title"
					<? if ($hasChildren): ?>
						onclick="BX.Dom.toggleClass(this.parentNode, '--open')"
					<? else: ?>
						href="<?=$link?>"
						target="_top"
						<? if (isset($item['PARAMS']['onclick'])): ?>
							data-onclick="<?=htmlspecialcharsbx($item['PARAMS']['onclick'])?>"
							onclick="return invokeSiteMapItemOnClick(event, this)"
						<? endif ?>
					<? endif ?>
				><?=$title?></a><?

				if ($hasChildren):
					?><div class="sitemap-section-item-children"><?
				else:
					?></div><?
				endif;
		endif;

		$previousLevel = $depthLevel;
	endforeach;

	//close last item tags
	if ($previousLevel > 1):
		?><?=str_repeat('</div></div>', ($previousLevel - 1))?><?
	endif
	?>
	</div>
</div>

<script>
	function invokeSiteMapItemOnClick(event, item)
	{
		if (BX.Type.isStringFilled(item.dataset['onclick']))
		{
			eval('(function() {' + item.dataset['onclick'] + '})();');

			return false;
		}

		return true;
	}
</script>