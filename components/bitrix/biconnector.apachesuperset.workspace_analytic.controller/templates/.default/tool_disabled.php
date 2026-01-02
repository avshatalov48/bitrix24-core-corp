<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

if ($arResult['FEATURE_AVAILABLE'] === false || $arResult['TOOLS_AVAILABLE'] === false)
{
	echo '
		<script>
			BX.SidePanel.Instance.closeAll();
		</script>
	';
}
