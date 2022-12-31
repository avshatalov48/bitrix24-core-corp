<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.sidepanel-content',
]);
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
?>
<!DOCTYPE html>
<html lang="<?=LANGUAGE_ID;?>">
<head>
	<script data-skip-moving="true">
		// Prevent loading page without header and footer
		if (window === window.top)
		{
			window.location = "<?=CUtil::JSEscape((new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri()))->deleteParams(['IFRAME', 'IFRAME_TYPE']));?>" + window.location.hash;
		}
	</script>
	<?php $APPLICATION->ShowHead(); ?>
</head>
<body class="crm__document-view--slider-wrap">
<div class="ui-slider-no-access-inner">
	<div class="ui-slider-no-access-title"><?php echo $arResult['ERRORS'][0] ?? '' ?></div>
	<div class="ui-slider-no-access-subtitle"><?php echo Loc::getMessage('CRM_SIGN_DOCUMENT_VIEW_NOT_FOUND'); ?></div>
	<div class="ui-slider-no-access-img">
		<div class="ui-slider-no-access-img-inner"></div>
	</div>
</div>
</body>
</html>
<?php
\CMain::FinalActions();