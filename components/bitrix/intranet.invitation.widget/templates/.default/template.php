<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.fonts.opensans']);

$frame = $this->createFrame()->begin();
?>

<button data-id='invitationButton' class="ui-btn ui-btn-round license-btn license-btn-primary">
	<?= Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE') ?>
</button>

<script>
	BX.message(<?= CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__)) ?>);

	BX.ready(() => {
		BX.Intranet.InvitationWidget = new BX.Intranet.InvitationWidget({
			button: document.querySelector("[data-id='invitationButton']"),
			isCurrentUserAdmin: <?= $arResult['isCurrentUserAdmin'] ? 'true' : 'false' ?>,
			isExtranetAvailable: <?= $arResult['isExtranetAvailable'] ? 'true' : 'false' ?>,
			isInvitationAvailable: <?= $arResult['isInvitationAvailable'] ? 'true' : 'false' ?>,
			structureLink: '<?= CUtil::JSEscape($arResult['structureLink']) ?>',
			invitationLink: '<?= CUtil::JSEscape($arResult['invitationLink']) ?>',
		});
	});
</script>

<?php $frame->beginStub(); ?>

<button data-id='invitationButton' class="ui-btn ui-btn-round license-btn license-btn-primary">
	<?=Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE')?>
</button>

<?php $frame->end(); ?>
