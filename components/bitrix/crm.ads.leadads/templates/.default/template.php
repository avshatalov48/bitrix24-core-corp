<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */

Extension::load('ui.sidepanel-content');
?>
	<div class="ui-slider-no-access">
		<div class="ui-slider-no-access-inner">
			<div class="ui-slider-no-access-title"></div>
			<div class="ui-slider-no-access-subtitle"></div>
			<div class="ui-slider-no-access-img">
				<div class="ui-slider-no-access-img-inner"></div>
			</div>
		</div>
	</div>
<?php
exit;