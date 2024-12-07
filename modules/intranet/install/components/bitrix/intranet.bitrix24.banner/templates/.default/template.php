<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
$desktopDownloadLinks = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks();

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 500);
?>
<div class="b24-app-block b24-app-desktop">
	<div class="b24-app-block-header"><?=GetMessage("B24_BANNER_MESSENGER_TITLE_MSGVER_1")?></div>
	<div class="b24-app-block-content">
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['macos']) ?>" target="_blank" style="width: 33%;" class="b24-app-block-content-apps">
			<span class="b24-app-icon b24-app-icon-macos"></span>
			<span>Mac OS</span>
		</a>
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['windows']) ?>" target="_blank" style="width: 33%;" class="b24-app-block-content-apps">
			<span class="b24-app-icon b24-app-icon-windows"></span>
			<span>Windows</span>
		</a>
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['linuxDeb']) ?>" target="_blank" style="width: 33%;" class="b24-app-block-content-apps b24-app-block-content-apps-linux">
			<span class="b24-app-icon b24-app-icon-linux"></span>
			<span class="b24-app-text-linux">Linux</span>
		</a>
		<div style="clear:both"></div>
	</div>
</div>
<div class="b24-app-block b24-app-mobile">
	<div class="b24-app-block-header"><?=GetMessage("B24_BANNER_MOBILE_TITLE_MSGVER_1")?></div>
	<div class="b24-app-block-content">
		<a href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('mobile_app');" style="width: 45%;" class="b24-app-block-content-apps">
			<span class="b24-app-icon b24-app-icon-ios"></span>
			<span>App Store</span>
		</a>
		<a href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('mobile_app');" style="width: 55%;" class="b24-app-block-content-apps b24-app-block-separate">
			<span class="b24-app-icon b24-app-icon-android"></span>
			<span>Google Play</span>
		</a>
		<div style="clear:both"></div>
	</div>
</div>
<script>
	BX.ready(() => {
		BX.message(<?= \Bitrix\Main\Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		var element = document.querySelector('.b24-app-block-content-apps-linux');
		element.addEventListener('click', function (event){
			BX.Intranet.Bitrix24Banner.getInstance().showMenuForLinux(
				event,
				document.querySelector('.b24-app-text-linux'),
				{
					deb: "<?= htmlspecialcharsbx($desktopDownloadLinks['linuxDeb']) ?>",
					rpm: "<?= htmlspecialcharsbx($desktopDownloadLinks['linuxRpm']) ?>"
				}
			);
		});
	});
</script>
