<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
?>
<?php
if(!empty($arResult['additionalScripts']))
{
	foreach ($arResult['additionalScripts'] as $path)
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs($path);
	}
}

if (isset($arResult['hideBorder']) && $arResult['hideBorder'] === true)
{
	global $APPLICATION;
	$bodyClass = $APPLICATION->getPageProperty("BodyClass");
	$APPLICATION->setPageProperty("BodyClass",
		($bodyClass ? $bodyClass." " : "").
		"crm-toolbar-no-border"
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
	<div class="crm-view-switcher">
		<div class="crm-view-switcher-list">
			<?php if (!empty($arResult['views']['left'])): ?>
				<?php $renderViews($arResult['views']['left']); ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="crm-view-switcher-buttons">
		<?php if (!empty($arResult['views']['right'])): ?>
			<?php $renderViews($arResult['views']['right']); ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
