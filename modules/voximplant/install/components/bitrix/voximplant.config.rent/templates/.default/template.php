<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CJSCore::RegisterExt('voximplant_config_rent', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.rent/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.rent/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(array('voximplant.common', 'voximplant_config_rent', 'loader', 'sidepanel', 'ui.alerts', 'ui.hint', 'ui.buttons'));

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>
<div class="pagetitle-container pagetitle-align-right-container">
	<? if($arResult['RENT_PACKET_SIZE'] > 1): ?>
		<a class="ui-btn ui-btn-default" onclick="top.BX.Helper.show('redirect=detail&code=9079921');"><?=GetMessage("VI_CONFIG_RENT_PACKET_DETAILS")?></a>
	<? endif ?>
</div>
<?

if($isBitrix24Template)
{
	$this->EndViewTarget();
}
$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>

<div id="voximplant-rent" class="voximplant-container"></div>

<script type="text/javascript">
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