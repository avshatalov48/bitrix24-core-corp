<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load([
	'intranet_theme_picker',
	'loader'
]);

/**
 * @global CMain $APPLICATION
 * @global CBitrixComponentTemplate $this
 * @var array $arParams
 */
$arParams['SHOW_LOADER'] = $arParams['SHOW_LOADER'] ?? 'Y';
if ($arParams['SHOW_LOADER'] !== 'N')
{
	$APPLICATION->SetPageProperty('BodyClass', 'no-all-paddings semi-transparent-background');
}
?>
<script>
	BX.ready(() => {
		<?php if ($arParams['SHOW_LOADER'] !== 'N'):
			?>(new BX.Loader({target: document.querySelector('.workarea-content')})).show();<?php
		endif;?>
		const path = ['/', window.location.pathname, '/'].join('').replaceAll('//', '/');
		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [
						window.location.host + path + '$',
						path + 'index.php',
						new RegExp(path + "\\?page|analyticContext=[a-z]+", 'i'),
					],
					options: {
						loader: 'intranet:slider-settings',
						width: 1034
					}
				},
			]
		});

		let isThemePickerBound = false;
		window.addEventListener('message', (event) => {
			if (String(event.data).indexOf('themeChanging') < 0)
			{
				return;
			}
			const sideSlider = BX.SidePanel.Instance.getSlider('<?=CUtil::JSEscape($_SERVER['REQUEST_URI'])?>');
			const data = JSON.parse(event.data);

			if (sideSlider && data.eventName === 'themeChanging')
			{
				BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog(false);

				if (data.themeId)
				{
					const selectTheme = (attempt) => {
						const container = BX.Intranet.Bitrix24.ThemePicker.Singleton.getContentContainer();
						if (!BX.type.isDomNode(container))
						{
							if (attempt > 0)
							{
								setTimeout(selectTheme, 100, --attempt);
							}
							else
							{
								sideSlider.hide();
							}
							return;
						}
						const themeNode = BX.Intranet.Bitrix24.ThemePicker.Singleton
							.getContentContainer()
							.querySelector(`[data-theme-id="${data.themeId}"]`);
						if (themeNode)
						{
							BX.Intranet.Bitrix24.ThemePicker.Singleton.selectItem(themeNode);
							sideSlider.hide();
						}
					};
					selectTheme(10);
				}
				else
				{
					sideSlider.hide();
				}
				BX.addCustomEvent(
					BX.Intranet.Bitrix24.ThemePicker.Singleton.getThemeListDialog(),
					'onPopupDestroy',
					() => {
						sideSlider.unhide();
					}
				);
				if (isThemePickerBound === false)
				{
					isThemePickerBound = true;
					BX.addCustomEvent(
						'Intranet.ThemePicker:onSaveTheme',
						(themeParams) => {
							sideSlider.getFrame().contentWindow.postMessage('onSaveTheme', '*');
						}
					);
				}
			}
		}, false);
	});
</script><?php

 $APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'.default',
	[
		'PREVENT_LOADING_WITHOUT_IFRAME' => true,
		'USE_PADDING' => false,
		'POPUP_COMPONENT_NAME' => $this->getComponent()->getName(),
		'POPUP_COMPONENT_TEMPLATE_NAME' => '.main',
		'USE_BACKGROUND_CONTENT' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => null,
		'POPUP_COMPONENT_PARAMS' => [
			'START_PAGE' => $_REQUEST['page'] ?? null,
		],
		'USE_UI_TOOLBAR' => 'Y',
		// 'PLAIN_VIEW' => true,
		// 'IFRAME_MODE' => true,

]);
