<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.vue']);

$frame = $this->createFrame()->begin();
?>

<span data-id="invitationWidgetWrapper">
	<button class="ui-btn ui-btn-round license-btn license-btn-primary">
		<?=Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE')?>
	</button>
</span>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		BX.Intranet.InvitationWidget = new BX.Intranet.InvitationWidget({
			wrapper: document.querySelector("[data-id='invitationWidgetWrapper']"),
			isCrurrentUserAdmin: '<?=$arResult['isCrurrentUserAdmin'] ? 'Y' : 'N'?>',
		});
	});
</script>

<?php $frame->beginStub(); ?>

<button class="ui-btn ui-btn-round license-btn license-btn-primary">
	<?=Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE')?>
</button>

<?php $frame->end(); ?>
