<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.sidepanel-content',
]);

$APPLICATION->SetTitle(htmlspecialcharsbx($arResult['title']));

if (\Bitrix\Main\Loader::includeModule('ui'))
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
}
?>
<div class="ui-slider-no-access-inner">
	<div class="ui-slider-no-access-title"><?php echo $arResult['ERRORS'][0] ?? '' ?></div>
	<div class="ui-slider-no-access-subtitle"><?php echo Loc::getMessage('CRM_SIGN_DOCUMENT_VIEW_NOT_FOUND'); ?></div>
	<div class="ui-slider-no-access-img">
		<div class="ui-slider-no-access-img-inner"></div>
	</div>
</div>