<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init("popup");
?>
<div class="bx-otp-info-popup-container" id="otp_mandatory_info" style="display: none">
	<div class="bx-otp-info-popup-content" style="">
		<div class="bx-otp-info-popup-col-left">
			<div class="bx-otp-info-popup-col-left-img"></div>
			<span><?=GetMessage("INTRANET_OTP_PASS")?></span><br/>
			<span><?=GetMessage("INTRANET_OTP_CODE")?></span>
		</div>
		<div class="bx-otp-info-popup-col-right">
			<div class="bx-otp-info-popup-content-title"><?=GetMessage("INTRANET_OTP_MANDATORY_TITLE")?></div>
			<?=GetMessage("INTRANET_OTP_MANDATORY_DESCR")?>
			<?if (intval($arResult["USER"]["OTP_DAYS_LEFT"])):?>
				<?=GetMessage("INTRANET_OTP_MANDATORY_DESCR2", array("#NUM#" => $arResult["USER"]["OTP_DAYS_LEFT"]))?>
			<?endif?>
		</div>
		<div class="clb"></div>
	</div>
	<div class="bx-otp-info-popup-buttons">
		<a class="bx-otp-info-btn big green" href="<?=$arResult["PATH_TO_PROFILE_SECURITY"]?>"><?=GetMessage("INTRANET_OTP_GOTO")?></a>
		<a class="bx-otp-info-btn big transparent" href="javascript:void(0)" onclick="BX.PopupWindowManager.getCurrentPopup().close()"><?=GetMessage("INTRANET_OTP_CLOSE")?></a>
	</div>
</div>

<script>
	BX.ready(function(){
		if (BX("<?=CUtil::JSEscape($arResult["POPUP_NAME"])?>"))
		{
			var otpPopup = BX.PopupWindowManager.create("otpInfoPopup", null, {
				autoHide: false,
				offsetLeft: 20,
				offsetTop: 10,
				overlay : true,
				draggable: {restrict:true},
				closeByEsc: true,
				content: BX("<?=CUtil::JSEscape($arResult["POPUP_NAME"])?>")
			});

			otpPopup.show();
		}
	});
</script>