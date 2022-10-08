<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.sidepanel-content',
]);
?>

<div class="ui-slider-no-access-inner">
	<div class="ui-slider-no-access-title"><?php echo $arResult['ERRORS'][0] ?? '' ?></div>
	<div class="ui-slider-no-access-subtitle"><?php echo Loc::getMessage('CRM_WEBFORM_UNAVAILABLE_ASK_ADMIN'); ?></div>
	<div class="ui-slider-no-access-img">
		<div class="ui-slider-no-access-img-inner"></div>
	</div>
</div>
