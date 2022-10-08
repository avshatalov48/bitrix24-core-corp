<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */
\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.forms',
	'ui.design-tokens',
]);
?>
<div class="intranet-apps-widget">
	<div class="intranet-apps-widget-item">
		<div class="intranet-apps-widget-title"><?= Loc::getMessage('INTRANET_APPS_WIDGET_MOBILE_APP') ?></div>
		<?php
		$classList = [
			'intranet-apps-widget-icon',
			'intranet-apps-widget-icon-android',
			(
				$arResult['APP_ANDROID_INSTALLED']
					? 'intranet-apps-widget-icon-active'
					: 'intranet-apps-widget-icon-download'
			)
		];
		?>
		<span data-role="profile-android-app" class="<?= implode(' ', $classList) ?>"></span>
		<?php
		$classList = [
			'intranet-apps-widget-icon',
			'intranet-apps-widget-icon-appstore',
			(
				$arResult['APP_IOS_INSTALLED']
					? 'intranet-apps-widget-icon-active'
					: 'intranet-apps-widget-icon-download'
			)
		];
		?>
		<span data-role="profile-ios-app" class="<?= implode(' ', $classList) ?>"></span>
	</div>
	<div class="intranet-apps-widget-item">
		<div class="intranet-apps-widget-title"><?= Loc::getMessage('INTRANET_APPS_WIDGET_DESKTOP_APP') ?></div>
		<?php
		$classList = [
			'intranet-apps-widget-icon',
			'intranet-apps-widget-icon-windows',
			(
				$arResult['APP_WINDOWS_INSTALLED']
					? 'intranet-apps-widget-icon-active'
					: 'intranet-apps-widget-icon-download'
			)
		];
		?>
		<a href="https://dl.bitrix24.com/b24/bitrix24_desktop.exe"
		   target="_blank"
		   class="<?= implode(' ', $classList) ?>"></a><?php

		$classList = [
			'intranet-apps-widget-icon',
			'intranet-apps-widget-icon-iphone',
			(
				$arResult['APP_MAC_INSTALLED']
					? 'intranet-apps-widget-icon-active'
					: 'intranet-apps-widget-icon-download'
			)
		];
		?>
		<a href="https://dl.bitrix24.com/b24/bitrix24_desktop.dmg"
		   target="_blank"
		   class="<?= implode(' ', $classList) ?>"></a>
	</div>
</div>
<script>
	BX.ready(function() {
		BX.message({
			'INTRANET_APPS_WIDGET_INSTALL': '<?= CUtil::JSEscape(Loc::getMessage('INTRANET_APPS_WIDGET_INSTALL')) ?>',
			'INTRANET_APPS_WIDGET_INSTALL_TEXT': '<?= CUtil::JSEscape(Loc::getMessage('INTRANET_APPS_WIDGET_INSTALL_TEXT')) ?>',
			'INTRANET_APPS_WIDGET_PHONE': '<?= CUtil::JSEscape(Loc::getMessage('INTRANET_APPS_WIDGET_PHONE')) ?>',
			'INTRANET_APPS_WIDGET_SEND': '<?= CUtil::JSEscape(Loc::getMessage('INTRANET_APPS_WIDGET_SEND')) ?>',
		});

		new BX.Intranet.AppsWidget({
			personalMobile: '<?= CUtil::JSEscape($arResult['PERSONAL_MOBILE']) ?>',
		});
	});
</script>
