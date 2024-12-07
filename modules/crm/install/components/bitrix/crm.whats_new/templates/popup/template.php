<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load('crm.integration.ui.banner-dispatcher');

?>

<script>
	BX.ready(function() {
		var options = <?= Json::encode([
			'data' => $arParams['SLIDES'][0] ?? [], // multiple slides is not supported (use default template)
			'options' => $arParams['OPTIONS'] ?? [],
			'userOptionCategory' => $arParams['CLOSE_OPTION_CATEGORY'] ?? '',
			'userOptionName' => $arParams['CLOSE_OPTION_NAME'] ?? '',
		]) ?>;

		var popup = new BX.Crm.WhatsNew.RichPopup(options);
		popup.show();
	});
</script>
