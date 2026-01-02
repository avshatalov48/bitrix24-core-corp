<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->setTitle(GetMessage("ERROR_404_TITLE"));
?>

<div class="error-404-block-wrap">
	<div class="error-404-block">
		<div class="error-404-text1"><?=Loc::getMessage('ERROR_404_TEXT1')?></div>
		<div class="error-404-text2"><?=Loc::getMessage('ERROR_404_TEXT2')?></div>
		<div class="error-404-footer"></div>
	</div>
</div>
