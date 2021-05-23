<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($arResult['ERRORS'])
{
	echo '<h3 class="document-view-header">'.implode('<br />', $arResult['ERRORS']).'</h3>';
	return;
}

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

if($arResult['imageUrl'])
{
	$APPLICATION->IncludeComponent('bitrix:pdf.viewer', '', [
		'PATH' => $arResult['pdfUrl'],
		'IFRAME' => 'N',
		'WIDTH' => 1000,
		'HEIGHT' => 1200,
		'PRINT' => 'Y',
		'PRINT_URL' => $arResult['printUrl'],
	]);
}
else
{
	\CJSCore::init(["loader", "documentpreview", "sidepanel"]);
	?>
	<h3 class="document-view-header"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_PUBLIC_VIEW_WAIT_TRANSFORMATION');?></h3>
<script>
BX.ready(function()
{
	var options = <?=\CUtil::PhpToJSObject($arResult)?>;
	options.onReady = function(options)
	{
		location.reload();
	};
	var preview = new BX.DocumentGenerator.DocumentPreview(options);
});
</script><?
}