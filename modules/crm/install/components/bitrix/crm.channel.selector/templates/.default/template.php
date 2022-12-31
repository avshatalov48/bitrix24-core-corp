<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/activity.js');

\Bitrix\Main\UI\Extension::load([
	'crm.channel-selector',
]);

$activityEditorParams = [
	'CONTAINER_ID' => '',
	'EDITOR_ID' => $arResult['activityEditorId'],
	'PREFIX' => '',
	'ENABLE_UI' => false,
	'ENABLE_EMAIL_ADD' => true,
	'ENABLE_TOOLBAR' => false,
	'TOOLBAR_ID' => '',
	'OWNER_TYPE' => $arResult['entityTypeName'],
	'OWNER_ID' => $arResult['entityId'],
	'SKIP_VISUAL_COMPONENTS' => 'Y',
];

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$activityEditorParams,
	$this->getComponent(),
	array('HIDE_ICONS' => 'Y')
);

if (\Bitrix\Crm\Integration\Bitrix24\Product::isRegionRussian(true))
{
	$arResult['channelIcons'] = [
		'telephone',
		'whatsapp',
		'messenger',
	];
}
else
{
	$arResult['channelIcons'] = [
		'telephone',
		'whatsapp',
		'facebook',
		'messenger',
		'instagram',
	];
}
$arResult['contactCenterUrl'] = \Bitrix\Main\Loader::includeModule('bitrix24') ? '/contact_center/' : '/services/contact_center/';
?>
	<div id="channel-selector-container"></div>

	<script>
		BX.ready(function() {
			var selector = new BX.Crm.ChannelSelector.List(<?=CUtil::PhpToJSObject($arResult);?>);
			var node = selector.render();
			document.getElementById('channel-selector-container').appendChild(node);
		});
	</script>
