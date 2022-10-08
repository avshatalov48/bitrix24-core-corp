<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);
?>

<div class="b24-time-container">
	<div class="adm-promo-title b24-list-title">
		<span class="adm-promo-title-item"><?=Loc::getMessage('FACEID_TMS_START_CLOSED_TITLE')?></span>
	</div>
</div>

<div class="b24-time-container">
	<div class="b24-time-desc">
		<?=Loc::getMessage('FACEID_TMS_START_CLOSED_TEXT')?>
	</div>
</div>