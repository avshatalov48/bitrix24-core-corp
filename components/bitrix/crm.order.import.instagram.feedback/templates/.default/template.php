<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
]);

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_OIIF_FEEDBACK_TITLE'));
?>
<script id="bx24_form_inline" data-skip-moving="true">
	(function(w, d, u, b)
	{
		w['Bitrix24FormObject'] = b;
		w[b] = w[b] || function()
		{
			arguments[0].ref = u;
			(w[b].forms = w[b].forms || []).push(arguments[0])
		};
		if (w[b]['forms']) return;
		var s = d.createElement('script');
		s.async = 1;
		s.src = u + '?' + (1 * new Date());
		var h = d.getElementsByTagName('script')[0];
		h.parentNode.insertBefore(s, h);
	})(window, document, 'https://product-feedback.bitrix24.com/bitrix/js/crm/form_loader.js', 'b24form');
</script>
<div class="import-instagram-limit-container">
	<div class="import-instagram-limit-inner" id="import-instagram-feedback-form">
		<script>
			BX.ready(function()
			{
				var options = <?=\CUtil::PhpToJSObject($arResult);?>;
				options.node = BX('import-instagram-feedback-form');
				b24form(options);
			});
		</script>
	</div>
</div>