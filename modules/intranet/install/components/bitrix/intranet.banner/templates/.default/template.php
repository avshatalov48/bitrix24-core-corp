<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?if ($arParams['ALLOW_CLOSE'] == 'Y'):?><script type="text/javascript">if (null == window.phpVars) window.phpVars = {}; if (!window.phpVars.bitrix_sessid) window.phpVars.bitrix_sessid='<?=bitrix_sessid()?>';</script><?endif;?>
<div class="bx-intranet-bnr" id="bx_intranet_bnr_<?echo $arParams['ID']?>">
	<div class="bx-intranet-bnr-head"><?if ($arParams['ALLOW_CLOSE'] == 'Y'):?><a href="javascript:void(0)" class="btn-close" onclick="BXIntrCloseBnr('<?echo $arParams['ID']?>')" title="<?echo htmlspecialcharsbx(GetMessage('INTR_BANNER_CLOSE'));?>"></a><?endif;?></div>
	<div class="bx-intranet-bnr-body">
		<?if ($arParams['ICON']):?>
			<a class="bx-intranet-bnr-icon <?echo $arParams['ICON']?>"<?if ($arParams['ICON_HREF']):?> href="<?echo $arParams['ICON_HREF']?>"<?endif;?>></a>
		<?endif;?>
		<div class="bx-intranet-bnr-content<?if ($arParams['ICON']):?> bx-intranet-bnr-margin<?endif;?>">
			<?echo $arParams['~CONTENT']?>
		</div>
		<div style="clear: both;"></div>
	</div>
</div>