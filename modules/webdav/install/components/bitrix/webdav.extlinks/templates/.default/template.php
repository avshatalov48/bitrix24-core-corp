<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
	CUtil::InitJSCore(array('viewer', 'ajax'));
	$sType = $arResult["SITE_TYPE"];
	$fileNotFound = $arResult["FILE_NOT_FOUND"];
	
	
	$tempPatch = CWebDavExtLinks::GetFullURL($this->GetFolder());
	
	$compName = htmlspecialcharsbx($arResult["COMPANY_NAME"]);
	$fileName = htmlspecialcharsbx($arResult["NAME"]);	
	$fileDescription = htmlspecialcharsbx($arResult["DESCRIPTION"]);
	$fileSize = (intval($arResult["F_SIZE"]) > 0 ? CFile::FormatSize(intval($arResult["F_SIZE"])) : "");
	$icon = $arResult["ICON"];
	
?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="windows-1251">
	<title><? echo GetMessage("WD_EXT_LINK_COMP_LINK"); ?></title>
	<!-- <link rel="stylesheet" href="https://cp.bitrix.ru/bitrix/templates/bitrix24/interface.css?1353493044"/> -->
	<link rel="stylesheet" href="<? echo $tempPatch; ?>/style.css"/>
	<?= CJSCore::GetHTML (array('viewer'))?>
</head>
<body>
	<div class="sharing-link-page<? if(substr_count($sType, "b24") <= 0){ echo " old-design"; } ?>">
		<div id="header">
			<div class="header-logo-block">
				<a href="<? echo SITE_DIR; ?>" title="<? echo GetMessage("WD_EXT_LINK_COMP_LOGO_C"); ?>" class="logo">
					<span class="logo-text"><? echo $compName; ?></span>
<? 
	if(substr_count($sType, "b24") > 0)
	{
?>
					<span class="logo-color">24</span>
<?
	}
?>
				</a>
			</div>
		</div>
		
<?
	if($arResult["PASSWORD"] == "NOT")
	{
		$loadUrl = CWebDavExtLinks::GetFullExternalURL() .  $arResult["HASH"] . '/?LoadFile=1';
		
?>
		<script type="text/javascript">

			BX.ready(function(){
				BX.viewElementBind('cont-general-info', {}, {attribute: 'data-bx-viewer'});
			});
		</script>

		<div id="cont-general-info" class="fl-page">
			<img src="<? echo $tempPatch; ?>/icon/<? echo $icon; ?>" class="sh-fileicon" alt=""/><br/>
<?
			if(!$fileNotFound)
			{
?>
			<a href="<? echo $loadUrl; ?>" class="sh-filename"><? echo $fileName; ?></a> <span class="sh-filesize"><? echo $fileSize; ?></span>
			<p class="sh-filedesc"><? echo $fileDescription; ?></p>
			<a class="<? echo (substr_count($sType, "b24") > 0 ? "button24" : "webform-small-button webform-small-button-accept"); ?>" href="<? echo $loadUrl; ?>">
				<span class="<? echo (substr_count($sType, "b24") > 0 ? "button24-l" : "webform-small-button-left"); ?>"></span><span class="<? echo (substr_count($sType, "b24") > 0 ? "button24-t" : "webform-small-button-text"); ?>"><? echo GetMessage("WD_EXT_LINK_COMP_LINK"); ?></span><span class="<? echo (substr_count($sType, "b24") > 0 ? "button24-r" : "webform-small-button-right"); ?>"></span>
			</a>

			<? if(!empty($arResult['ALLOW_VIEWER'])): ?>
				<? if(!substr_count($sType, "b24")): ?>
					<a data-bx-viewer="iframe-extlinks" data-bx-title="<?= CUtil::JSEscape($fileName) ?>" data-bx-src="<?= $loadUrl ?>" data-bx-viewerUrl="<? echo CWebDavExtLinks::$urlGoogleViewer . urlencode($loadUrl) . '&embedded=true'; ?>" class="webform-small-button webform-small-button-simple" href="" onclick="return false;">
						<span class="webform-small-button-left"></span><span class="webform-small-button-text"><? echo GetMessage("WD_EXT_LINK_COMP_PREVIEW"); ?></span><span class="webform-small-button-right"></span>
					</a>
				<? else: ?>
					<span data-bx-viewer="iframe-extlinks" data-bx-title="<?= CUtil::JSEscape($fileName) ?>" data-bx-src="<?= $loadUrl ?>" data-bx-viewerUrl="<? echo CWebDavExtLinks::$urlGoogleViewer . urlencode($loadUrl) . '&embedded=true'; ?>" class="button24-simple" href="" onclick="return false;">
						<? echo GetMessage("WD_EXT_LINK_COMP_PREVIEW"); ?>
					</span>
				<? endif; ?>
			<? endif; ?>
<?
			}
			else
			{
?>
			<span class="sh-filename-error"><? echo $fileName; ?></span>
			<p class="sh-filedesc"><? echo $fileDescription; ?></p>
<?
			}
?>
		</div>
<? 
	}
	else
	{
		$pasText = "<strong>" . GetMessage("WD_EXT_LINKS_COMP_PASS_TITLE") . "</strong><br>" . GetMessage("WD_EXT_LINKS_COMP_PASS_TEXT");
		$pasDivAddClass = "";
		if($arResult["PASSWORD"] == "PASSWORD_WRONG")
		{
			$pasText = GetMessage("WD_EXT_LINKS_COMP_PASS_TITLE_WRONG");
			$pasDivAddClass = " sharing-link-pass-error";
		}
?>
		<div class="sharing-link-pass-block<? echo $pasDivAddClass; ?>">
			<div class="sharing-link-pass-text">
				<? echo $pasText; ?>
			</div>
			<div class="sharing-link-pass-form">
				<form id="form-pass" action="<?=POST_FORM_ACTION_URI?>" method="post">
					<span class="sharing-link-pass-form-label"><? echo GetMessage("WD_EXT_LINKS_COMP_PASS"); ?>:</span><input name="USER_PASSWORD" type="password" class="sharing-link-pass-form-input"/>
				</form>
			</div>
			<div class="sharing-link-pass-bottom">
				<a onClick="SendPass();" class="webform-button webform-button-accept"><span class="webform-button-left"></span><span class="webform-button-text"><? echo GetMessage("WD_EXT_LINKS_COMP_PASS_CONTINUE"); ?></span><span class="webform-button-right"></span></a>
			</div>
		</div>
		<script type="text/javascript">
			function SendPass()
			{
				var form = document.getElementById("form-pass");
				form.submit();
			}
		</script>
<?
	}
	if($sType == "b24")
	{
		switch(strtolower(LANGUAGE_ID))
		{
			case 'ru':
			case 'de':
			case 'ua':
				$d = strtolower(LANGUAGE_ID);
				break;
			default:
				$d = 'com';
		}
?>
		<div class="banner-wrap">
			<div class="banner-text"><? echo GetMessage("WD_EXT_LINK_COMP_T1"); ?> <a href="http://www.bitrix24.<? echo $d; ?>/features/" class="banner-link"><? echo GetMessage("WD_EXT_LINK_COMP_B"); ?></a></div>
			<a href="http://www.bitrix24.<? echo $d; ?>/features/"><div class="banner-block-wrap"><div class="banner-block <?= LANGUAGE_ID; ?>"></div></div></a>
		</div>

<?
	}
?>
	</div>
</body>
</html>