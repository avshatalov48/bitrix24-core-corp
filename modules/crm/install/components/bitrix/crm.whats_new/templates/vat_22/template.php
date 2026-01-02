<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load(['ui.banner-dispatcher', 'ui.system.dialog']);

?>

<script>
	BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);
	BX.ready(function() {
		var popup = new BX.Crm.WhatsNew.Vat22Popup();
		popup.show();
	});
</script>
