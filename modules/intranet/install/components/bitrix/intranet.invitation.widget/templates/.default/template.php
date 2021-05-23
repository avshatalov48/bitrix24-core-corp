<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.vue']);

$frame = $this->createFrame()->begin('');
?>

<span data-id="invitationWidgetWrapper"></span>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		BX.Intranet.InvitationWidget = new BX.Intranet.InvitationWidget({
			wrapper: document.querySelector("[data-id='invitationWidgetWrapper']"),
		});
	});
</script>

<?$frame->end(); ?>
