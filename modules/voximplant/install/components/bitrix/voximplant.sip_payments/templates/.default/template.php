<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CJSCore::RegisterExt('voximplant_sip_payments', array(
	'js' => '/bitrix/components/bitrix/voximplant.sip_payments/templates/.default/template.js',
));
CJSCore::Init(array('voximplant.common', 'voximplant_sip_payments'));
?>
<script type="text/javascript">
	BX.ready(function(){
		BX.VoxImplant.sipPayments.init();
	})
</script>
<a name="sipConnector"></a>
<div class="tel-history-block">
	<div class="tel-history-title"><?=GetMessage('VI_SIP_TITLE')?></div>
	<?if ($arResult['SIP_NOTICE_OLD_CONFIG_OFFICE_PBX']):?>
		<div class="tel-notice-in-block" id="vi_sip_notify_block">
			<?=GetMessage('VI_NOTICE_OLD_CONFIG_OFFICE_PBX', Array('#ACCOUNT_NAME#' => $arResult['ACCOUNT_NAME']))?>
			<div class="tel-sip-notice-button">
				<span id="vi_sip_notify_button" class="tel-balance-blue-btn">
					<span class="tel-balance-update-btn-text"><?=GetMessage('VI_NOTICE_BUTTON_DONE')?></span>
				</span>
			</div>
		</div>
	<?endif;?>
	<?if($arResult['DATE_END']):?>
		<div class="tel-sip-paid-content">
			<div class="tel-sip-paid-left">
				<?=GetMessage('VI_SIP_PAID_BEFORE', Array('#DATE#' => '<b>'.$arResult['DATE_END'].'</b>'))?>
				<div class="tel-sip-notice"><?=GetMessage('VI_SIP_PAID_NOTICE')?></div>
				<div class="tel-history-more">
					<a href="<?=CVoxImplantMain::GetPublicFolder()?>lines.php?MODE=SIP" class="tel-history-more-link"><?=GetMessage('VI_SIP_CONFIG')?></a>
				</div>
			</div>
			<div class="tel-sip-paid-right">
				<?if (!empty($arResult['LINK_TO_BUY'])):?>
					<a href="<?=$arResult['LINK_TO_BUY']?>" class="tel-balance-blue-btn tel-sip-button">
						<span class="tel-balance-update-btn-text"><?=GetMessage('VI_SIP_BUTTON')?></span>
					</a>
				<?endif;?>
			</div>
		</div>
	<?else:?>
		<div class="tel-sip-paid-content">
			<div class="tel-sip-paid-left">
				<?=GetMessage('VI_SIP_PAID_FREE', Array('#COUNT#' => '<b>'.$arResult['FREE'].'</b>'))?>
				<div class="tel-sip-notice"><?=GetMessage('VI_SIP_PAID_NOTICE_2')?></div>
			</div>
			<div class="tel-sip-paid-right">
				<a href="<?=CVoxImplantMain::GetPublicFolder()?>lines.php?MODE=SIP" class="tel-balance-blue-btn tel-sip-button">
					<span class="tel-balance-update-btn-text"><?=GetMessage('VI_SIP_BUTTON_BUY')?></span>
				</a>
			</div>
		</div>
	<?endif;?>
</div>