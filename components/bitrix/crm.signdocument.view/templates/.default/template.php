<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.viewer',
]);?>

<div class="sign-document-container">
	<div class="sign-document-preview-container">
		<div data-role="pdf-viewer" data-viewer-type="document"></div>
	</div>
	<div class="sign-document-description-container">
		<div class="sign-document-channels">
			Channels
		</div>
	</div>
</div>
<script>
	BX.ready(function(){
		const params = <?=\CUtil::PhpToJSObject($arResult, false, false, true);?>;
		params.pdfNode = document.querySelector('[data-role="pdf-viewer"]');

		new BX.Crm.Component.SignDocumentView(params);
	});
</script>
