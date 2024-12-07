<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CMain $APPLICATION */

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'popup',
	'sidepanel',
]);

Loc::loadLanguageFile(__FILE__);

if ($arParams['FEEDBACK_TYPE'] === 'integration_request')
{
	$pageTitle = Loc::getMessage('SALESCENTER_FEEDBACK_INTEGRATION_REQUEST_TITLE_MSGVER_2');
}
else
{
	$pageTitle = Loc::getMessage('SALESCENTER_FEEDBACK_TITLE');
}

$isIframe = ($_REQUEST['IFRAME'] ?? null) === 'Y';

if ($isIframe)
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
				})(window,document,'<?= $arResult['domain'] ?>/bitrix/js/crm/form_loader.js','B24SalesCenterFeedback');
			</script>
			<?php $APPLICATION->ShowHead(); ?>
		</head>
		<body class="document-limit-slider">
			<div class="pagetitle-wrap">
				<div class="pagetitle-inner-container">
					<div class="pagetitle">
						<span id="pagetitle" class="pagetitle-item"><?=$pageTitle;?></span>
					</div>
				</div>
			</div>
			<div class="document-limit-container">
<?php
}
else
{
	$APPLICATION->SetTitle($pageTitle);
	?>
	<script id="bx24_form_inline" data-skip-moving="true">
		(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
			(w[b].forms=w[b].forms||[]).push(arguments[0])};
			if(w[b]['forms']) return;
			var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
			var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
		})(window,document,'<?= $arResult['domain'] ?>/bitrix/js/crm/form_loader.js','B24SalesCenterFeedback');
	</script>
	<div class="document-limit-container"><?php
}

unset($arResult['domain']);
?>
			<div class="document-limit-inner" id="salescenter-feedback-form">
				<script>
					BX.ready(function()
					{
						var options = <?= Json::encode($arResult); ?>;
						options.node = BX('salescenter-feedback-form');
						B24SalesCenterFeedback(options);
					});
				</script>
			</div>
		</div>
<?php
if ($isIframe)
{
	?>
		</body>
	</html><?php
	\Bitrix\Main\Application::getInstance()->terminate();
}
