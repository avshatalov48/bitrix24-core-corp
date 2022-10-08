<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);

if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}

?>
	<script id="bx24_form_inline" data-skip-moving="true">
		(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
			(w[b].forms=w[b].forms||[]).push(arguments[0])};
			if(w[b]['forms']) return;
			var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
			var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
		})(window,document,'https://product-feedback.bitrix24.com/bitrix/js/crm/form_loader.js','B24RpaFeedback');
	</script>
	<div class="document-limit-container">
		<div class="document-limit-inner" id="rpa-feedback-form">
			<script>
				BX.ready(function()
				{
					var options = <?=\CUtil::PhpToJSObject($arResult);?>;
					options.node = BX('rpa-feedback-form');
					B24RpaFeedback(options);
				});
			</script>
		</div>
	</div>