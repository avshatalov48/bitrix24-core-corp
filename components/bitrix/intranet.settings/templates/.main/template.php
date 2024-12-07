<?php
/**
 * @var CBitrixComponentTemplate $this
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI;

global $APPLICATION;

Extension::load([
	'ui.dialogs.messagebox',
	'ui.switcher',
	'ui.collapser',
	'ui.section',
	'ui.icon-set.main',
	'ui.icon-set.actions',
	'ui.icon-set.editor',
	'ui.icon-set.social',
	'ui.icon-set.crm',
	'ui.forms',
	'ui.common',
	'ui.info-helper',
	'ui.entity-selector',
	'ui.alerts',
	'ui.tabs',
	'ui.ears',
	'ui.form-elements.view',
	'ui.form-elements.field',
	'ui.switcher-nested',
	'main.qrcode',
	'intranet_theme_picker',
	'bitrix24.license',
	'ui.analytics',
	'main.loader'
]);
$APPLICATION->SetPageProperty('BodyClass', 'intranet-settings-iframe-popup');
// TODO delete after release train 30.08.2023
UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

$this->SetViewTarget('left-panel');
?>
<div class="ui-sidepanel-sidebar">
	<div class="ui-sidepanel-head">
		<h2 class="ui-sidepanel-title">
			<span class="intranet-settings-main-title"><?=Loc::getMessage('INTRANET_SETTINGS_TITLE_MENU')?></span>
		</h2>
	</div>

	<div class="main-ui-filter-search main-ui-filter-theme-default main-ui-filter-set-inside" id="intranet-settings-search-container">
		<input <?= $arResult['PERMISSION'] < 2 ? 'disabled' : '' ?> type="text" tabindex="1" value="" name="FIND" placeholder="<?=Loc::getMessage('INTRANET_SETTINGS_SEARCH_PLACEHOLDER')?>" class="main-ui-filter-search-filter" id="intranet-settings-search" autocomplete="off">
		<div class="main-ui-item-icon-block" id="intranet-settings-search-icon-container">
			<span class="main-ui-item-icon main-ui-search <?= $arResult['PERMISSION'] < 2 ? '--disabled' : '' ?>"></span>
			<span class="main-ui-item-icon main-ui-delete" id="intranet-settings-icon-delete"></span>
		</div>
	</div>
</div>
<?php
$this->EndViewTarget();
?>

<div id="intranet-settings-page" class="intranet-settings-page-wrapper">



	<?php
		$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrappermenu', '', [
			'ID' => 'intranet-settings-left-menu',
			'ITEMS' => $arResult['MENU_ITEMS'] ?? [],
//			'TITLE_HTML' => '<span class="intranet-settings-main-title">' . Loc::getMessage('INTRANET_SETTINGS_TITLE_MENU') . '</span>',
		]);
	$this->SetViewTarget('left-panel');
	?>
	<ul class="intranet-settings__sidepanel-menu">
		<li class="intranet-settings__menu-item">
			<a onclick="top.BX.Helper.show('redirect=detail&code=18371844'); return false;" class="intranet-settings__menu-link"><?=Loc::getMessage('INTRANET_SETTINGS_GUIDE')?></a>
		</li>
<?php

if (Bitrix\Main\Loader::includeModule('bitrix24'))
{
	$partnerID = COption::GetOptionString("bitrix24", "partner_id", "");

	if (empty($partnerID))
	{
		$callback = new \Bitrix\UI\Buttons\JsCode(
			"BX.UI.InfoHelper.show('info_implementation_request');"
		);
	}
	else if ($partnerID != "9409443") //sber
	{
		$arParamsPartner = [
			"MESS" => [
				"BX24_PARTNER_TITLE" => GetMessage("BITRIX24_PARTNER_POPUP_TITLE"),
				"BX24_BUTTON_SEND" => GetMessage("BITRIX24_PARTNER_POPUP_BUTTON"),
			]
		];
		$callback = new \Bitrix\UI\Buttons\JsCode(
			"showPartnerForm(" . \CUtil::PhpToJSObject($arParamsPartner) . ");"
		);
	}

	if (isset($callback))
	{
		?>
		<li class="intranet-settings__menu-item">
			<a onclick="<?= $callback->getCode() ?>; return false;" class="intranet-settings__menu-link">
				<?= Loc::getMessage('BITRIX24_PARTNER_ORDER_MSGVER_1') ?>
			</a>
		</li>
		<?php
	}
}
?>
	</ul>
	<?php
	$this->EndViewTarget();
	?>
		<div class="intranet-settings-content"></div>
	<?php
		$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				'save',
				[
					'type' => 'custom',
					'layout' => '<input type="submit" class="ui-btn ui-btn-link" name="cancel" value="'.Loc::getMessage('INTRANET_SETTINGS_CANCEL_BUTTON').'">'
				]
			],
			'HIDE' => true,
		]);
		CJSCore::Init(['popup', 'date']);
	?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		const permission = new BX.Intranet.Permission(<?=$arResult['PERMISSION']?>);

		if (permission.canEdit())
		{
			const searcher = new BX.Intranet.Searcher(new BX.Intranet.ServerDataSource());
			new BX.Intranet.Renderer({
				searcher: searcher,
				inputNode: BX('intranet-settings-search'),
				timeout: 500,
			iconContainer: BX('intranet-settings-search-icon-container'),});
		}

		const pagesPermission = <?=CUtil::PhpToJSObject($arResult['PAGES_PERMISSION'])?>;
		const analyticContext = '<?= CUtil::JSEscape($arResult['ANALYTIC_CONTEXT'] ?? '') ?>';
		const startPage = '<?= CUtil::JSEscape($arResult['START_PAGE'] ?? '') ?>';
		const isBitrix24 = <?= \Bitrix\Main\Loader::includeModule('bitrix24') ? 'true' : 'false'?>;
		const settings = new BX.Intranet.Settings({
			basePage: top.window.location.pathname,
			startPage: startPage,
			isBitrix24: isBitrix24,
			analyticContext: analyticContext,
			menuNode: BX('intranet-settings-left-menu'),
			settingsNode: BX('intranet-settings-page'),
			contentNode: BX('intranet-settings-page').querySelector('.intranet-settings-content'),
			pages: Array.from(<?=CUtil::PhpToJSObject(
				array_values(array_map(
					fn($item) => ucfirst($item['ATTRIBUTES']['DATA']['type']),
					array_filter(
						$arResult['MENU_ITEMS'],
						fn($item) => ($item['EXTERNAL'] ?? 'N') === 'N'
					)
				))
			)?>).map((pageId) => {
				return  new BX.Intranet[pageId + 'Page'];
			}),
			externalPages: <?=CUtil::PhpToJSObject(
				array_map(
					fn($item) => [
						'type' => $item['ATTRIBUTES']['DATA']['type'],
						'extensions' => $item['EXTENSIONS'],
					],
					array_filter(
						$arResult['MENU_ITEMS'],
						fn($item) => ($item['EXTERNAL'] ?? 'N') === 'Y'
					)
				)
			)?>,
			subPages: <?=CUtil::PhpToJSObject($arResult['SUBPAGES'])?>,
			permission: permission,
			pagesPermission: pagesPermission,
		});
		settings.show(startPage, '<?= CUtil::JSEscape($arResult['OPTION_TO_MOVE']) ?? '' ?>');

		const mainPageSection = document.querySelector(".ui-page-slider-left-panel a[data-type='mainpage']");
		if (mainPageSection)
		{
			mainPageSection.onclick = function()
			{
				BX.UI.Analytics.sendData({
					tool: 'landing',
					category: 'vibe',
					event: 'open_settings_main',
					c_sub_section: 'from_settings',
				});
			};
		}
	});
</script>
