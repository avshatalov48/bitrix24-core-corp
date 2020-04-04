<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CJSCore::RegisterExt('voximplant_config_sip', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.sip/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.sip/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(['ui.buttons', 'ui.buttons.icons', 'ui.alerts', 'sidepanel', 'voximplant.common', 'voximplant_config_sip']);

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>
<div class="pagetitle-container pagetitle-align-right-container">
	<? if($arResult["SIP_TYPE"] === CVoxImplantSip::TYPE_CLOUD): ?>
		<button id="add-connection" class="ui-btn ui-btn-md ui-btn-primary ui-btn-icon-add"><?= GetMessage("VI_CONFIG_SIP_CONNECT_CLOUD") ?></button>
	<? else: ?>
		<button id="add-connection" class="ui-btn ui-btn-md ui-btn-primary ui-btn-icon-add"><?= GetMessage("VI_CONFIG_SIP_CONNECT_OFFICE") ?></button>
	<? endif ?>
</div>
<?

if($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<div class="">
	<div id="detail-connector" class="voximplant-ats">
		<div>
			<div class="tel-set-text-block tel-set-text-grey">
				<?=GetMessage('VI_CONFIG_SIP_CONNECT_INFO_P1');?><br>
				<?=GetMessage('VI_CONFIG_SIP_CONNECT_INFO_P2');?><br>

				<?if ($arResult['SIP_ENABLE']):?>
					<br>
					<?=GetMessage('VI_SIP_PAID_BEFORE', Array('#DATE#' => '<b>'.$arResult['DATE_END'].'</b>'))?><br><br>
					<?if (!empty($arResult['LINK_TO_BUY'])):?>

						<a class="ui-btn ui-btn-primary" href="<?=$arResult["LINK_TO_BUY"]?>" target="_blank"><?=GetMessage('VI_SIP_BUTTON')?></a>
					<?endif;?>
				<?else:?>
					<?=GetMessage('VI_CONFIG_SIP_INFO_2');?><br><br>
					<?if (!empty($arResult['LINK_TO_BUY'])):?>
						<?=GetMessage('VI_CONFIG_SIP_CONNECT_INFO_P3');?><br><br>

						<?=GetMessage('VI_SIP_PAID_FREE', Array('#COUNT#' => '<b>'.$arResult['TEST_MINUTES'].'</b>'))?>
						<br>
					<?else:?>
						<div><?=GetMessage('VI_CONFIG_SIP_CONNECT_DISABLE');?></div><br>
					<?endif;?>
					<div class="ui-alert ui-alert-warning">
						<span class="ui-alert-message">
							<?=GetMessage('VI_CONFIG_SIP_CONNECT_NOTICE_2');?>
							<br>
							<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
						</span>
					</div>
					<div class="tel-set-inp-add-new" style="margin-bottom: 35px">
						<span class="ui-btn ui-btn-primary" onclick="BX.Voximplant.Sip.connectModule('<?=$arResult['LINK_TO_BUY']?>')" >
							<?=GetMessage('VI_CONFIG_SIP_ACCEPT_3')?>
						</span>
					</div>

				<?endif;?>
			</div>
			<div id="phone-config-sip-wrap"></div>
			<div class="tel-set-item-group-margin"></div>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.Voximplant.Sip.init({
		publicFolder: '<?=CVoxImplantMain::GetPublicFolder()?>',
		type: '<?=CUtil::JSEscape($arResult['SIP_TYPE'])?>',
		sipConnections: <?= CUtil::PhpToJSObject(array_values($arResult['LIST_SIP_NUMBERS']))?>,
		linkToBuy: '<?=CUtil::JSEscape($arResult['LINK_TO_BUY'])?>'
	});
</script>