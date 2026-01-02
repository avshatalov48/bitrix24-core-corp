<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

$expandSubMenu = function($item, $subMenuItems) use (&$expandSubMenu) {
	foreach ($subMenuItems as $subMenuItem)
	{
		if (isset($subMenu['IS_DELIMITER']) || (isset($item['PERMISSION']) && $item['PERMISSION'] <= 'D'))
		{
			continue;
		}

		$title = htmlspecialcharsbx($subMenuItem['TEXT'] ?? '', ENT_COMPAT, false);
		$link = htmlspecialcharsbx($subMenuItem['URL'] ?? '', ENT_COMPAT, false);
		$hasChildren = !empty($subMenuItem['ITEMS']);

		if (empty($title) && empty($link))
		{
			continue;
		}

		$tag = empty($link) ? 'span' : 'a';

		?><div class="sitemap-section-item<? if ($hasChildren): ?> --has-children<? endif ?>"><?
			?><<?=$tag?>
				class="sitemap-section-item-title"
				title="<?=$title?>"
				<? if ($tag === 'a'): ?>
					href="<?=$link?>"
					target="_top"
				<? endif ?>
				<? if (!empty($subMenuItem['ON_CLICK'])): ?>
					data-onclick="<?=htmlspecialcharsbx($subMenuItem['ON_CLICK'])?>"
					onclick="return invokeSiteMapItemOnClick(event, this)"
				<? endif ?>
			><?=$title?></<?=$tag?>><?

			if ($hasChildren)
			{
				?><div class="sitemap-section-item-children"><?=$expandSubMenu($subMenuItem, $subMenuItem['ITEMS'])?></div><?
			}
		?></div><?
	}
};
?>

<div class="sitemap-window">
	<div class="sitemap-content"><?
	foreach ($arResult['MAP_ITEMS'] as $item):
		if (isset($item['PERMISSION']) && $item['PERMISSION'] <= 'D')
		{
			continue;
		}

		$title = htmlspecialcharsbx($item['TEXT'] ?? '', ENT_COMPAT, false);
		$link = htmlspecialcharsbx($item['URL'] ?? '', ENT_COMPAT, false);

		if (empty($title) && empty($link))
		{
			continue;
		}

		$tag = empty($link) ? 'span' : 'a';
		?><div class="sitemap-section"><?
		?><<?=$tag?> class="sitemap-section-title" href="<?=$link?>" target="_top"><?=$title?></<?=$tag?>><?
			if (!empty($item['ITEMS'])):
				?><div class="sitemap-section-items"><?=$expandSubMenu($item, $item['ITEMS'])?></div><?
			endif;
		?></div><?php
	endforeach ?>
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
