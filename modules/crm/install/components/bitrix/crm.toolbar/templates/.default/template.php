<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Extension::load(['crm.toolbar-component', 'ui.fonts.opensans']);

if(!empty($arResult['additionalScripts']))
{
	foreach ($arResult['additionalScripts'] as $path)
	{
		Asset::getInstance()->addJs($path);
	}
}

if(isset($arResult['hideBorder']) && $arResult['hideBorder'] === true)
{
	global $APPLICATION;

	$bodyClass = $APPLICATION->getPageProperty("BodyClass");
	$APPLICATION->setPageProperty("BodyClass",
		($bodyClass ? $bodyClass." " : "").
		"crm-toolbar-no-border"
	);
}

if(isset($arResult['spotlight']) && is_array($arResult['spotlight']))
{
	$APPLICATION->includeComponent(
		"bitrix:spotlight",
		"",
		$arResult['spotlight'],
	);
}

?>
<?php if (!empty($arResult['communications']['buttons'])):?>
	<script>
	BX.ready(function() {
		<?php foreach ($arResult['communications']['buttons'] as $buttonInfo): ?>
			<?php if (!empty($buttonInfo['messages'])):?>
			<?=$buttonInfo['class'];?>.messages = <?=CUtil::PhpToJSObject($buttonInfo['messages']);?>;
			<? endif;?>
			var node = null;
			var button = BX.UI.ButtonManager.getByUniqid('<?=CUtil::JSEscape($buttonInfo['buttonUniqueId']);?>');
			if(button)
			{
				node = button.getContainer();
			}
			if(node && <?=$buttonInfo['class'];?>)
			{
				<?=$buttonInfo['class'];?>.create(
					'<?=CUtil::JSEscape($buttonInfo['objectId']);?>',
					{
						button: node,
						data: <?=CUtil::PhpToJSObject($buttonInfo['data'])?>,
						ownerInfo: <?=CUtil::PhpToJSObject($buttonInfo['ownerInfo'])?>
					}
				);
			}
		<?php endforeach;?>
	});
	</script>
<?php endif;?>
<?php

$filterId = $arResult['filter']['FILTER_ID'] ?? ($arResult['filter']['GRID_ID'] ?? null);
$navigationBarId = htmlspecialcharsbx(mb_strtolower("{$filterId}-nav-bar"));
$renderViews = static function(array $views): void {
	foreach ($views as $view):
		if (!empty($view['html']))
		{
			echo $view['html'];
			continue;
		}

		$className = $view['className'] ?? 'crm-view-switcher-list-item';
		if ($view['isActive'] === true)
		{
			$className .= ' crm-view-switcher-list-item-active';
		}
		$href = '';
		if (!empty($view['url']))
		{
			$url = (string)$view['url'];
			$relativeUrl = mb_strpos($url, '/') === 0 ? $url : '/';
			$href = 'href="' . htmlspecialcharsbx($relativeUrl) . '"';
		}
		$onclick = '';
		if (!empty($view['onclick']))
		{
			$onclick = 'onclick="' . $view['onclick'] . '"';
		}
		?>
		<a
			class="<?= $className ?>"
			<?=$href?>
			<?=$onclick?>
		>
			<?=htmlspecialcharsbx($view['title'])?>
		</a>
	<?php endforeach;
};
?>
<?php if (!empty($arResult['views'])): ?>
	<div id="<?=$navigationBarId?>" class="crm-view-switcher"></div>
	<?php if (isset($arResult['views']['counter_panel_html'])): ?>
		<?= $arResult['views']['counter_panel_html']; ?>
	<?php endif; ?>
	<div class="crm-view-switcher-buttons pagetitle-align-right-container">
		<?php if (!empty($arResult['views']['right'])): ?>
			<?php $renderViews($arResult['views']['right']); ?>
		<?php endif; ?>
	</div>
	<script type="text/javascript">
		BX.ready(function() {
			// init navigation bar panel
			(new BX.Crm.NavigationBar({
				id: "<?= $navigationBarId ?>",
				items: <?= CUtil::PhpToJSObject($arResult['views']['left']) ?>
			})).init();
		});
	</script>
<?php endif; ?>
