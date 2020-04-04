<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$id = 'intranet_binding_menu_crm_switcher';
?>
<?if (count($arResult['ITEMS']) == 1):
	$item = array_shift($arResult['ITEMS']);
	?>
	<a class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes" <?
	?>href="<?= $item['href'];?>" <?
	if ($item['onclick']){?> onclick="<?=$item['onclick']; ?>; return false;" <?}?>>
		<?= $item['text'];?>
	</a>
<?else:?>
	<div id="<?= $id;?>" class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes">
		<?= $arResult['DEFAULT_BUTTON_NAME'];?>
	</div>
<?endif;?>

<?if ($arResult['ADDITIONAL']):?>
	<div class="ui-btn-split" id="<?= $id;?>_additional">
		<div class="ui-btn-menu"></div>
	</div>
<?endif;?>

<script type="text/javascript">
	BX.ready(function()
	{
		(new BX.Intranet.Binding.Menu(
			'<?= $id;?>',
			<?= \CUtil::phpToJSObject($arResult['ITEMS']);?>,
			<?= \CUtil::phpToJSObject($arResult['ADDITIONAL']);?>
		)).binding();
	});
</script>
