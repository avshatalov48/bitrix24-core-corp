<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
$desktopDownloadLinks = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks();

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 500);
?>
<div class="sidebar-widget b24-app-block">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title">
			<?=GetMessage("B24_BANNER_MESSENGER_TITLE_MSGVER_2")?>
		</div>
	</div>
	<div class="sidebar-widget-content">
		<div class="b24-app-block-content">
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['macos']) ?>" target="_blank" class="b24-app-block-content-apps">
			<span class="b24-app-icon-wrapper">
				<span class="b24-app-icon --macos"></span>
			</span>
			<span class="b24-app-title">Mac OS</span>
		</a>
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['windows']) ?>" target="_blank" class="b24-app-block-content-apps">
			<span class="b24-app-icon-wrapper">
				<span class="b24-app-icon --windows"></span>
			</span>
			<span class="b24-app-title">Windows</span>
		</a>
		<a href="<?= htmlspecialcharsbx($desktopDownloadLinks['linuxDeb']) ?>" target="_blank" class="b24-app-block-content-apps b24-app-block-content-apps-linux">
			<span class="b24-app-icon-wrapper">
				<span class="b24-app-icon --linux"></span>
			</span>
			<span class="b24-app-title">Linux</span>
		</a>
		<a href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('mobile_app');" class="b24-app-block-content-apps">
			<span class="b24-app-icon-wrapper">
				<span class="b24-app-icon --ios"></span>
			</span>
			<span class="b24-app-title">iOS</span>
		</a>
		<a href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('mobile_app');" class="b24-app-block-content-apps b24-app-block-separate">
			<span class="b24-app-icon-wrapper">
				<span class="b24-app-icon --android"></span>
			</span>
			<span class="b24-app-title">Android</span>
		</a>
		<div style="clear:both"></div>
	  </div>
	</div>
</div>
<script>
	BX.ready(() => {
		BX.message(<?= \Bitrix\Main\Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		var element = document.querySelector('.b24-app-block-content-apps-linux');
		element.addEventListener('click', function (event){
			BX.Intranet.Bitrix24Banner.getInstance().showMenuForLinux(
				event,
				document.querySelector('.b24-app-block-content-apps-linux .b24-app-title'),
				{
					deb: "<?= htmlspecialcharsbx($desktopDownloadLinks['linuxDeb']) ?>",
					rpm: "<?= htmlspecialcharsbx($desktopDownloadLinks['linuxRpm']) ?>"
				}
			);
		});
	});
</script>
