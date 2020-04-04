<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var CMain $APPLICATION */
/** @var array $arResult*/
/** @var array $arParams*/

global $APPLICATION;

CJSCore::Init("sidepanel");

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
<head>
	<script type="text/javascript">
		// Prevent loading page without header and footer
		if(window === window.top)
		{
			window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
		}
	</script>
	<?$APPLICATION->ShowHead();?>
</head>
<body class="voximplant-slider-frame-popup template-<?= SITE_TEMPLATE_ID ?> <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle-menu" id="pagetitle-menu">
				<? $APPLICATION->ShowViewContent("pagetitle"); ?>
			</div>
			<div class="pagetitle">
				<span id="pagetitle" class="pagetitle-item"><? $APPLICATION->ShowTitle(); ?></span>
				<span id="pagetitle_edit" class="pagetitle-edit-button" style="display: none;"></span>
				<input id="pagetitle_input" type="text" class="pagetitle-item" style="display: none;">
			</div>
			<? $APPLICATION->ShowViewContent("inside_pagetitle"); ?>
		</div>
	</div>
	<div id="voximplant-frame-popup-workarea">
		<div id="sidebar"><? $APPLICATION->ShowViewContent("sidebar"); ?></div>
		<div id="workarea-content">
			<div class="workarea-content-paddings voximplant-workarea-content-paddings">

				<?
				$APPLICATION->IncludeComponent(
					$arParams['COMPONENT_NAME'],
					$arParams['COMPONENT_TEMPLATE_NAME'],
					$arParams['COMPONENT_PARAMS']
				);
				?>
			</div>
		</div>
	</div>
</body>
</html>