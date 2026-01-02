<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CMain $APPLICATION
 * @var array $arParams
 */
global $APPLICATION;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
<head>
	<script>
		// Prevent loading page without header and footer
		if (window == window.top)
		{
			window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME')));?>";
		}
	</script>
	<?php
	$APPLICATION->ShowHead();
	?>
</head>
<body class="crm-frame-popup template-<?=SITE_TEMPLATE_ID?> <?php $APPLICATION->ShowProperty('BodyClass'); ?>"
		onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');"
		onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">
<div class="pagetitle-wrap">
	<div class="pagetitle-inner-container">
		<div class="pagetitle">
			<span id="pagetitle" class="pagetitle-item"><?php
				$APPLICATION->ShowTitle();
			?></span>
		</div>
	</div>
</div>

<div id="crm-frame-popup-workarea">
	<div id="sidebar"><?php
		$APPLICATION->ShowViewContent("sidebar");
	?></div>
	<div id="workarea-content">
		<div class="workarea-content-paddings">
			<?php
			$APPLICATION->IncludeComponent(
				$arParams['POPUP_COMPONENT_NAME'],
				$arParams['POPUP_COMPONENT_TEMPLATE_NAME'],
				$arParams['POPUP_COMPONENT_PARAMS']
			);
			?>
		</div>
	</div>
</div>
</body>
</html>