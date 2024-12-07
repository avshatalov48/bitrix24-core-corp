<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var array $arParams */
/** @var \Bitrix\Sign\Item\Document $document */
/** @var SignMasterComponent $component */
/** @var CMain $APPLICATION */

$document = $arResult['DOCUMENT'] ?? null;
if (empty($document))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:ui.info.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'USE_PADDING' => false,
			'USE_UI_TOOLBAR' => 'N',
		]
	);

	return;
}

$APPLICATION->SetTitle(Loc::getMessage('SIGN_B2E_PREVIEW_TEMPLATE_HEADER', ['#TITLE#' => $document->title]));

\Bitrix\Main\UI\Extension::load([
	'sign.v2.preview-document',
]);
?>
<div class="sign-preview-document-wrapper">
	<div id="sign-master__preview"></div>
</div>

<script>
	BX.ready(function()
	{
		(new BX.Sign.V2.PreviewDocument({
			container: document.getElementById('sign-master__preview'),
			documentId: '<?= CUtil::JSescape($document->uid) ?>',
		})).render();
	});
</script>
