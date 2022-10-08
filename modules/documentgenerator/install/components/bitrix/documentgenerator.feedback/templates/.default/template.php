<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
]);

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
if(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
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
			<script id="bx24_form_inline" data-skip-moving="true">
				(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
					(w[b].forms=w[b].forms||[]).push(arguments[0])};
					if(w[b]['forms']) return;
					var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
					var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
				})(window,document,'https://product-feedback.bitrix24.com/bitrix/js/crm/form_loader.js','B24DocGenFeedback');
			</script>
			<?$APPLICATION->ShowHead(); ?>
		</head>
		<body class="document-limit-slider">
			<div class="pagetitle-wrap">
				<div class="pagetitle-inner-container">
					<div class="pagetitle">
						<span id="pagetitle" class="pagetitle-item"><?=\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_FEEDBACK_TITLE');?></span>
					</div>
				</div>
			</div>
			<div class="document-limit-container">
<?}
else
{
	$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('DOCGEN_FEEDBACK_TITLE'));
	?>
	<script id="bx24_form_inline" data-skip-moving="true">
		(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
			(w[b].forms=w[b].forms||[]).push(arguments[0])};
			if(w[b]['forms']) return;
			var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
			var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
		})(window,document,'https://product-feedback.bitrix24.com/bitrix/js/crm/form_loader.js','B24DocGenFeedback');
	</script>
	<div class="document-limit-container"><?
}
?>
			<div class="document-limit-inner" id="document-feedback-form">
				<script>
					BX.ready(function()
					{
						var options = <?=\CUtil::PhpToJSObject($arResult);?>;
						options.node = BX('document-feedback-form');
						B24DocGenFeedback(options);
					});
				</script>
			</div>
		</div>
<?if(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{?>
		</body>
	</html><?
	\Bitrix\Main\Application::getInstance()->terminate();
}