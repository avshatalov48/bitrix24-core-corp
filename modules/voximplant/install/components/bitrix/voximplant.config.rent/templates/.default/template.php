<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CJSCore::RegisterExt('voximplant_config_rent', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.rent/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.rent/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(array('voximplant.common', 'voximplant_config_rent', 'loader', 'sidepanel', 'ui.alerts', 'ui.hint', 'ui.buttons'));

$buttonAdd = new \Bitrix\UI\Buttons\Button([
	'text' => GetMessage('VI_CONFIG_RENT_PACKET_DETAILS'),
	'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	'click' => new \Bitrix\UI\Buttons\JsCode(
		"top.BX.Helper.show('redirect=detail&code=9079921');"
	),
	'dataset' => [
		'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::INFO,
	]
]);
$buttonAdd->addAttribute('id', 'vox-blacklist-add');
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonAdd);

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>

<div id="voximplant-rent" class="voximplant-container"></div>

<script>
	BX.ready(function()
	{
		window.test = new BX.Voximplant.Rent({
			id: 'vi-rent-phone',
			container: BX('voximplant-rent'),
			location: BX.message('LANGUAGE_ID').toUpperCase(),
			publicFolder: '<?=CVoxImplantMain::GetPublicFolder()?>',
			canRent: <?= $arResult['CAN_RENT_NUMBER'] ? 'true' : 'false' ?>,
			iframe: <?=CUtil::PhpToJSObject($arResult['IFRAME'])?>,
			currentBalance: <?= (float)$arResult['CURRENT_BALANCE']?>,
			rentPacketSize: <?= (int)$arResult['RENT_PACKET_SIZE']?>
		})
	})
</script>