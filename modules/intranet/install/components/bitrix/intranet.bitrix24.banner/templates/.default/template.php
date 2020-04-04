<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 500);
?>
<div class="b24-app-block b24-app-desktop">
	<div class="b24-app-block-header"><?=GetMessage("B24_BANNER_MESSENGER_TITLE")?></div>
	<div class="b24-app-block-content">
		<a href="http://dl.bitrix24.com/b24/bitrix24_desktop.dmg" target="_blank" style="width: 33%;" class="b24-app-block-content-apps">
			<span class="b24-app-icon b24-app-icon-macos"></span>
			<span>Mac OS</span>
		</a>
		<a href="http://dl.bitrix24.com/b24/bitrix24_desktop.exe" target="_blank" style="width: 37%;" class="b24-app-block-content-apps b24-app-block-separate">
			<span class="b24-app-icon b24-app-icon-windows"></span>
			<span>Windows</span>
		</a>
		<a href="https://github.com/buglloc/brick" target="_blank" style="width: 30%;" class="b24-app-block-content-apps b24-app-block-separate">
			<span class="b24-app-icon b24-app-icon-linux"></span>
			<span>Linux</span>
		</a>
		<div style="clear:both"></div>
	</div>
</div>
<div class="b24-app-block b24-app-mobile">
	<div class="b24-app-block-header"><?=GetMessage("B24_BANNER_MOBILE_TITLE")?></div>
	<div class="b24-app-block-content">
		<a href="<?=GetMessage("B24_BANNER_MOBILE_APPSTORE_URL")?>" target="_blank" style="width: 45%;" class="b24-app-block-content-apps">
			<span class="b24-app-icon b24-app-icon-ios"></span>
			<span>App Store</span>
		</a>
		<a href="<?=GetMessage("B24_BANNER_MOBILE_GOOGLE_PLAY_URL")?>" target="_blank" style="width: 55%;" class="b24-app-block-content-apps b24-app-block-separate">
			<span class="b24-app-icon b24-app-icon-android"></span>
			<span>Google Play</span>
		</a>
		<div style="clear:both"></div>
	</div>
</div>