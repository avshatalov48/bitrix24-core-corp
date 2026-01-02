<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var $arResult array
 */

Extension::load(['intranet.widget-loader']);

?>

<div class="air-header__button" data-id="invitationButton">
	<button class="air-header-button">
		<span class="air-header-button__text"><?=Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE')?></span>
		<span class="air-header-button__counter invitation-widget-counter"></span>
	</button>
</div>

<?php
$frame = $this->createFrame()->begin('');
?>

<script>
	BX.ready(() => {
		BX.Intranet.Bitrix24.InvitationButton.init({
			skeleton: <?= Json::encode($arResult['skeleton']) ?>,
			counterId: '<?= $arResult['counterId'] ?? '' ?>',
			invitationCounter: <?= $arResult['invitationCounter'] ?? 0 ?>,
			shouldShowStructureCounter: <?= $arResult['shouldShowStructureCounter'] ? 'true' : 'false' ?>,
		});
	});
</script>

<?php
$frame->end();
