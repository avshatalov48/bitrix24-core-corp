<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @global \CMain $APPLICATION */

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Extension::load(['crm.toolbar-component', 'ui.fonts.opensans', 'ui.actions-bar']);

if (isset($arResult['hideBorder']) && $arResult['hideBorder'] === true)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty(
		'BodyClass',
		($bodyClass ? $bodyClass . ' ' : '') . 'crm-toolbar-no-border',
	);
}

if (isset($arResult['spotlight']) && is_array($arResult['spotlight']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:spotlight',
		'',
		$arResult['spotlight'],
	);
}

if (!empty($arResult['communications']['buttons'])): ?>
	<script>
	<?php foreach ($arResult['communications']['buttons'] as $buttonInfo): ?>
		BX.ready(() => {
			const button = BX.UI.ButtonManager.getByUniqid('<?= CUtil::JSEscape($buttonInfo['buttonUniqueId']); ?>');
			if (button && <?= $buttonInfo['class']; ?>)
			{
				new <?= $buttonInfo['class']; ?>(
					{
						button: button,
						data: <?= \Bitrix\Main\Web\Json::encode($buttonInfo['data']); ?>,
						ownerInfo: <?= \Bitrix\Main\Web\Json::encode($buttonInfo['ownerInfo']); ?>,
						useClientSelector: <?= (($buttonInfo['useClientSelector'] ?? false) === true ? 'true' : 'false') ?>,
					}
				);
			}
		});
	<?php endforeach; ?>
	</script>
<?php endif;

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
	<div class="ui-actions-bar">
		<?php if (!empty($arResult['views']['left'])): ?>
			<div id="<?=$navigationBarId?>" class="ui-actions-bar__panel"></div>
			<script>
				// init navigation bar panel
				(new BX.Crm.NavigationBar({
					id: "<?= $navigationBarId ?>",
					items: <?= CUtil::PhpToJSObject($arResult['views']['left']) ?>
				})).init();
			</script>
		<?php endif; ?>
		<?php if (isset($arResult['views']['counter_panel_html'])): ?>
			<?= $arResult['views']['counter_panel_html']; ?>
		<?php endif; ?>
		<?php if (!empty($arResult['views']['right'])): ?>
			<div id="crm-view-switcher-buttons-right" class="ui-actions-bar__buttons crm-view-switcher-buttons">
				<?php $renderViews($arResult['views']['right']); ?>
			</div>
			<script>
				(new BX.UI.ActionsBar.RightButtons({
					buttonsContainer: document.getElementById('crm-view-switcher-buttons-right'),
					collapsable: true,
				})).init();
			</script>
		<?php endif; ?>
	</div>
<?php endif;

$APPLICATION->IncludeComponent('bitrix:crm.filterdependent.wrapper', '');
