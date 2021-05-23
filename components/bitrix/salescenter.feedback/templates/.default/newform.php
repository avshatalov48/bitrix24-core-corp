<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main,
	\Bitrix\Main\Localization\Loc;

\CJSCore::init("sidepanel");

Loc::loadLanguageFile(__FILE__);
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
					window.location = "<?=CUtil::JSEscape((new Main\Web\Uri(Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
				}
			</script>
			<?$APPLICATION->ShowHead(); ?>
		</head>
		<body class="document-limit-slider">
			<div class="pagetitle-wrap">
				<div class="pagetitle-inner-container">
					<div class="pagetitle">
						<span id="pagetitle" class="pagetitle-item"><?=Loc::getMessage('SALESCENTER_FEEDBACK_TITLE');?></span>
					</div>
				</div>
			</div>
			<div class="document-limit-container">
<?php
}
else
{
	$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_FEEDBACK_TITLE'));
?>
			<div class="document-limit-container"><?
}
?>
				<script data-b24-form="inline/<?=$arResult["id"]?>/<?=$arResult["sec"]?>" data-skip-moving="true">
					(function(w,d,u){
						var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
						var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
					})(window,document,'https://cdn-ru.bitrix24.ru/<?=$arResult["code"]?>/crm/form/loader_<?=$arResult["id"]?>.js');
				</script>
			</div>
<?php
if(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
?>
		</body>
	</html>
<?php
	Main\Application::getInstance()->terminate();
}