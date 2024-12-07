<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'crm.communication-rule',
]);

/** @var $arResult array */
?>
<script>
	BX.ready(() => {
		new BX.Crm.CommunicationRule(
			'routeDetails',
			{
				rule: <?= \Bitrix\Main\Web\Json::encode($arResult['RULE']) ?>,
				searchTargetEntities: <?= \Bitrix\Main\Web\Json::encode($arResult['SEARCH_TARGET_ENTITIES']) ?>,
				channels: <?= \Bitrix\Main\Web\Json::encode($arResult['CHANNELS']) ?>,
				entities: <?= \Bitrix\Main\Web\Json::encode($arResult['ENTITIES']) ?>,
				selectedChannelId: <?= (int) $arResult['CHANNEL_ID'] ?>,
			},
		);
	});
</script>
<div id="routeDetails"></div>
