<?

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
	'sidepanel',
	'documentpreview',
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
if(empty($arResult['ERROR']))
{
	if(empty($arResult['TOP_VIEW_TARGET_ID']))
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
					<div class="pagetitle-container pagetitle-align-right-container">
						<?if(Bitrix24Manager::isEnabled())
						{
							?><button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.DocumentGenerator.Feedback.open('<?=\htmlspecialcharsbx(\CUtil::JSEscape($arParams['PROVIDER']))?>');"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_TEMPLATE_LIST_FEEDBACK');?></button>
							<?
						}?>
						<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-icon-setting" id="docgen-templates-settings-button"></button>
						<button class="ui-btn ui-btn-md ui-btn-primary ui-btn-primary-docs-template" onclick="BX.DocumentGenerator.TemplateList.edit();"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_TEMPLATE_LIST_UPLOAD');?></button>
					</div>
	<?if(empty($arResult['TOP_VIEW_TARGET_ID']))
	{?>
				</div>
			</div>
		</div>
	<?}
	else
	{
		$this->EndViewTarget();
	}
}?>
	<div class="docs-template-info-inner">
		<div class="docs-template-info-message docs-template-error-message" id="docgen-templates-error-message"<?
		if(!empty($arResult['ERROR']))
		{
			?> style="display: block;"><?=htmlspecialcharsbx($arResult['ERROR']);
		}
		else
		{
			?>><?
		}?></div>
		<?if(empty($arResult['ERROR']))
		{?>
			<div class="docs-template-info-message"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_TEMPLATE_LIST_MORE_INFO');?>
				<a class="docs-template-info-link" onclick="BX.DocumentGenerator.TemplateList.openMoreLink(event);"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_TEMPLATE_LIST_MORE');?></a>
			</div>
		<?}?>
	</div>
	<?if(empty($arResult['ERROR']))
	{?>
		<div class="docs-template-grid">
				<?$APPLICATION->IncludeComponent(
			"bitrix:main.ui.grid",
			"",
			$arResult['GRID']
			);?>
		</div>
		<?}?>
</div>
<?if(empty($arResult['ERROR']))
{?>
<script>
	BX.ready(function()
	{
		BX.DocumentGenerator.TemplateList.init('<?=CUtil::JSEscape($arResult['GRID']['GRID_ID']);?>', <?=CUtil::PhpToJSObject($arResult['params']);?>);
		<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
	});
</script>
<?}?>
<?
if($arResult['IS_SLIDER'])
{
	?>
</body>
	</html><?
	\CMain::FinalActions();
}
