<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.notification',
	'ui.buttons.icons',
	'sidepanel',
	'ui.design-tokens',
]);

if($arResult['IS_SLIDER'])
{
	$APPLICATION->RestartBuffer();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<script data-skip-moving="true">
			// Prevent loading page without header and footer
			if (window === window.top)
			{
				window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
			}
		</script>
		<?$APPLICATION->ShowHead(); ?>
	</head>
	<body>
	<div class="docs-template-wrap-slider">
<?}
else
{
	$APPLICATION->SetTitle($arResult['TITLE']);?>
	<div class="docs-template-wrap">
<?}
if(!$arResult['TOP_VIEW_TARGET_ID'])
{?>
	<div class="pagetitle-wrap">
	<div class="docs-template-pagetitle-wrap">
	<div class="docs-template-pagetitle-inner pagetitle-inner-container">
	<div class="pagetitle">
		<span class="docs-template-pagetitle-item pagetitle-item" id="pagetitle"><?=$arResult['TITLE'];?></span>
	</div>
<?}
else
{
	$this->SetViewTarget($arResult['TOP_VIEW_TARGET_ID']);
}?>
	<div class="pagetitle-container pagetitle-flexible-space pagetitle-container-docs-template">
		<? $APPLICATION->IncludeComponent(
			"bitrix:main.ui.filter",
			"",
			$arResult['FILTER']
		); ?>
	</div>
<?if(!$arResult['TOP_VIEW_TARGET_ID'])
{?>
	</div>
	</div>
	</div>
<?}
else
{
	$this->EndViewTarget();
}?>
	<div class="docs-template-info-inner">
		<div class="docs-template-info-message docs-template-error-message" id="docgen-default-templates-error-message"></div>
	</div>
	<div class="docs-template-grid">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.ui.grid",
			"",
			$arResult['GRID']
		);?>
	</div>
	</div>
	<script>
		BX.ready(function()
		{
			BX.DocumentGenerator.TemplatesDefault.init(<?=CUtil::PhpToJSObject($arResult['params']);?>);
			<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
		});
	</script>
<?
if($arResult['IS_SLIDER'])
{
	?>
	</body>
	</html><?
	\CMain::FinalActions();
}