<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\CJSCore::init("sidepanel");

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

if(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
	$APPLICATION->RestartBuffer();
	?>
	<!DOCTYPE html>
	<html>
<head>
	<?$APPLICATION->ShowHead(); ?>
	<script data-skip-moving="true">
		// Prevent loading page without header and footer
		if (window === window.top)
		{
			window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
		}
	</script>
	<script>
		BX.SidePanel.Instance.getTopSlider().setWidth(735);
		BX.SidePanel.Instance.getTopSlider().adjustLayout();
	</script>
</head>
<body class="document-limit-slider">
<div class="pagetitle-wrap">
	<div class="pagetitle-inner-container">
		<div class="pagetitle">
			<span id="pagetitle" class="pagetitle-item"><?=\Bitrix\Main\Localization\Loc::getMessage('CRM_DOCUMENT_LIMIT_TITLE');?></span>
		</div>
	</div>
</div>
<?}
else
{
	$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_DOCUMENT_LIMIT_TITLE'));
}?>
<div class="document-limit-container">
	<div class="document-limit-inner">
		<div class="document-limit-desc">
			<div class="document-limit-img">
				<div class="document-limit-img-lock"></div>
			</div>
			<div class="document-limit-desc-text">
				<?=\Bitrix\Main\Localization\Loc::getMessage('CRM_DOCUMENT_LIMIT_TEXT_EXTENDED', ['#MAX#' => \Bitrix\DocumentGenerator\Integration\Bitrix24Manager::getDocumentsLimit()]);?>
			</div>
		</div>
		<div class="document-limit-buttons">
			<? \Bitrix\DocumentGenerator\Integration\Bitrix24Manager::showTariffRestrictionButtons(); ?>
		</div>
	</div>
</div>
<?
if(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
?>
</body>
	</html><?
	\CMain::FinalActions();
}