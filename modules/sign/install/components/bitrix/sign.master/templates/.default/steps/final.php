<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
?>

<script>
	BX.ready(function()
	{
		BX.SidePanel.Instance.close();
		<?php if (isset($arResult['OPEN_URL_AFTER_CLOSE'])):?>
		BX.SidePanel.Instance.open('<?php echo \CUtil::jsEscape($arResult['OPEN_URL_AFTER_CLOSE'])?>');
		<?php endif?>
	});
</script>
