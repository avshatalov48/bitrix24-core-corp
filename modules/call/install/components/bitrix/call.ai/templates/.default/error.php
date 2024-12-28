<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Localization\Loc;

/**
 * @global \CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 */
global $APPLICATION;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');

$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background bx-call-component-call-ai-page --error bitrix24-light-theme'
);

Toolbar::deleteFavoriteStar();


$arResult['ERROR_DESC']


?>

<div class="bx-call-component-call-ai-error">
	<div class="bx-call-component-call-ai-error__text">
		<span class="bx-call-component-call-ai-error__title"><?= $arResult['ERROR'] ?></span>
		<span class="bx-call-component-call-ai-error__description"><?= Loc::getMessage('CALL_COMPONENT_ERROR_DESCRIPTION') ?></span>
	</div>
	<div class="bx-call-component-call-ai-error__icon"></div>
</div>
