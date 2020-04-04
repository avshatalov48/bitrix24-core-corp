<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(["voximplant.common"]);
?>

<?if($arResult['CALLER_ID']['PHONE_NUMBER'] == ''):?>
<div class="tel-set-item-block tel-set-item-block-margin tel-set-item-icon">
	<b><?=GetMessage('TELEPHONY_EMPTY_PHONE')?></b><br>
	<?=GetMessage('TELEPHONY_EMPTY_PHONE_DESC')?>
</div>
<?else:?>
	<div class="tel-set-item-block tel-set-item-block-margin tel-set-item-icon">
		<b><?=GetMessage('TELEPHONY_CALLERID_NUMBER', array('#CALLER_ID#' => $arResult['CALLER_ID']['PHONE_NUMBER_FORMATTED']))?></b><br>
		<?
		if($arResult['CALLER_ID']['VERIFIED'])
			echo GetMessage('TELEPHONY_CONFIRM_DATE', array('#DATE#' => $arResult['CALLER_ID']['VERIFIED_UNTIL']));
		else
			echo GetMessage('TELEPHONY_NOT_CONFIRMED');
		?>
	</div>
<?endif;?>
<div class="tel-set-num-block tel-set-num-sip-block">
	<span class="tel-set-inp tel-set-inp-ready-to-use"><?=htmlspecialcharsbx($arResult['CONFIG']['PHONE_NAME'])?></span>
	<a class="ui-btn ui-btn-light-border ui-btn-lg"
	   href="<?=CVoxImplantMain::GetPublicFolder()?>edit.php?ID=<?=$arResult['CONFIG']['ID']?>&LINE_TYPE=LINK&ACTION=show<?=$arResult['IFRAME'] ? '&IFRAME=Y' : ''?>"
	><?=GetMessage("TELEPHONY_NUMBER_CONFIG")?></a>
</div>
