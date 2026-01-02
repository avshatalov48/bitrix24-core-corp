<?
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Intranet\Integration\Templates\Air\AirTemplate;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Main\Composite\StaticArea;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetMode;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$asset = Asset::getInstance();
// Performance optimization for sliders
if (isset($_GET['IFRAME']) && $_GET['IFRAME'] === 'Y' && !isset($_GET['SONET']))
{
	$asset->addCss(SITE_TEMPLATE_PATH . '/src/css/typography.css', true);
	$asset->addCss(SITE_TEMPLATE_PATH . '/src/css/standalone/iframe-scrollbar.css', true);

	return;
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$isSearchTitleRequest = !empty($request->get('ajax_call'));
if ($request->isAjaxRequest() && !$isSearchTitleRequest)
{
	return;
}

// Live Feed Ajax
if (isset($_GET['RELOAD']) && $_GET['RELOAD'] == 'Y')
{
	return;
}

Loader::includeModule('intranet');

\Bitrix\Main\UI\Extension::load([
	'intranet.sidepanel.air',
	'socialnetwork.slider',
	'calendar.sliderloader',
	'ui.counter',
	'ui.buttons',
]);

$isBitrix24Cloud = ModuleManager::isModuleInstalled('bitrix24');
$isCompositeMode = defined('USE_HTML_STATIC_CACHE');
$isIndexPage =
	$APPLICATION->GetCurPage(true) === SITE_DIR . 'stream/index.php' ||
	$APPLICATION->GetCurPage(true) === SITE_DIR . 'index.php' ||
	(defined('BITRIX24_INDEX_PAGE') && constant('BITRIX_INDEX_PAGE') === true)
;

if ($isIndexPage)
{
	if (!defined('BITRIX24_INDEX_PAGE'))
	{
		define('BITRIX24_INDEX_PAGE', true);
	}

	if ($isCompositeMode)
	{
		define('BITRIX24_INDEX_COMPOSITE', true);
	}
}

if (defined('AIR_TEMPLATE_HIDE_CHAR_BAR') && !defined('BX_IM_FULLSCREEN'))
{
	define('BX_IM_FULLSCREEN', true);
}

Loc::loadMessages(__DIR__ . '/site_template.php');

?><!DOCTYPE html>
<html<? if (LANGUAGE_ID === 'tr'):?> lang="<?=LANGUAGE_ID?>"<? endif ?>>
<head>
<? if ($isBitrix24Cloud): ?>
<meta name="apple-itunes-app" content="app-id=561683423">
<link rel="icon" href="<?= SITE_TEMPLATE_PATH ?>/src/images/favicons/favicon.ico" sizes="32x32">
<link rel="icon" href="<?= SITE_TEMPLATE_PATH ?>/src/images/favicons/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= SITE_TEMPLATE_PATH ?>/src/images/favicons/apple-touch-icon.png">
<? endif ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no"><?

$APPLICATION->showHead(false);
$asset->addCss(SITE_TEMPLATE_PATH . '/dist/bitrix24.bundle.css', true);
$asset->addJs(SITE_TEMPLATE_PATH . '/dist/bitrix24.bundle.js', true);
ThemePicker::getInstance()->showHeadAssets();

$layoutMode = \Bitrix\Intranet\UI\LeftMenu\Menu::isCollapsed() ? ' menu-collapsed-mode' : '';
?>
<title><? if (!$isCompositeMode) $APPLICATION->showTitle() ?></title>
</head>
<body class="<?= AirTemplate::getBodyClasses() ?>"><?
	ThemePicker::getInstance()->showBodyAssets();
	$frame = new StaticArea('title');
	$frame->startDynamicArea();
		?><script>
			document.title = "<? AirTemplate::showJsTitle() ?>";
			document.body.classList.add(<?= AirTemplate::getCompositeBodyClasses() ?>);
		</script><?
	$frame->finishDynamicArea();
?>
<div class="root">
	<? if ((!$isBitrix24Cloud || $USER->isAdmin()) && !defined('SKIP_SHOW_PANEL')): ?>
	<div id="panel"><? $APPLICATION->showPanel() ?></div>
	<? endif ?>
	<div class="app<?= $layoutMode ?> js-app">
		<div class="app__left-menu js-app__left-menu">
			<? $APPLICATION->includeComponent(
				'bitrix:menu',
				'left_vertical',
				[
					'ROOT_MENU_TYPE' => (
					file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . '.superleft.menu_ext.php')
						? 'superleft'
						: 'top'
					),
					'MENU_CACHE_TYPE' => 'Y',
					'MENU_CACHE_TIME' => '604800',
					'MENU_CACHE_USE_GROUPS' => 'N',
					'MENU_CACHE_USE_USERS' => 'Y',
					'CACHE_SELECTED_ITEMS' => 'N',
					'MENU_CACHE_GET_VARS' => [],
					'MAX_LEVEL' => '1',
					'USE_EXT' => 'Y',
					'DELAY' => 'N',
					'ALLOW_MULTI_SELECT' => 'N',
					'ADD_ADMIN_PANEL_BUTTONS' => 'N',
				],
				false
			) ?>
		</div>
		<? if (AirTemplate::shouldShowImBar()): ?>
		<div class="app__right-bar" id="right-bar">
			<?php $APPLICATION->includeComponent('bitrix:intranet.avatar.widget', '', []) ?>
			<div class="air-chat-bar">
				<? $APPLICATION->includeComponent('bitrix:intranet.chat.bar', '', []) ?>
			</div>
		</div>
		<? endif ?>
		<div class="app__go-top-button" id="goTopButtonWrapper">
		<?
			$goTopButton = AirTemplate::getGoTopButton();
			echo $goTopButton->render(false);
		?>
		</div>
		<div class="app__main">
			<header class="app__header">
				<div class="air-header" id="header">
					<div class="air-header__personal-info"><?php
						$APPLICATION->includeComponent('bitrix:intranet.search.title', 'air', [
							'CHECK_DATES' => 'N',
							'SHOW_OTHERS' => 'N',
							'TOP_COUNT' => 7,
							'CATEGORY_0_TITLE' => Loc::getMessage('BITRIX24_SEARCH_EMPLOYEE'),
							'CATEGORY_0' => [
								0 => 'custom_users',
							],
							'CATEGORY_1_TITLE' => Loc::getMessage('BITRIX24_SEARCH_GROUP'),
							'CATEGORY_1' => [
								0 => 'custom_sonetgroups',
							],
							'CATEGORY_2_TITLE' => Loc::getMessage('BITRIX24_SEARCH_COLLAB'),
							'CATEGORY_2' => [
								0 => 'custom_collabs',
							],
							'CATEGORY_3_TITLE' => Loc::getMessage('BITRIX24_SEARCH_MENUITEMS'),
							'CATEGORY_3' => [
								0 => 'custom_menuitems',
							],
							'NUM_CATEGORIES' => '4',
							'CATEGORY_OTHERS_TITLE' => Loc::getMessage('BITRIX24_SEARCH_OTHER'),
							'SHOW_INPUT' => 'N',
							'INPUT_ID' => 'search-textbox-input',
							'CONTAINER_ID' => 'search',
							'USE_LANGUAGE_GUESS' => (LANGUAGE_ID == 'ru') ? 'Y' : 'N',
							]);
						?>
						<div class="air-header__logo">
							<? include(__DIR__ . '/logo.php'); ?>
						</div>
						<div class="air-header__buttons"><?php
							$APPLICATION->includeComponent('bitrix:intranet.invitation.widget', 'air', []);
							$APPLICATION->includeComponent(
								$isBitrix24Cloud
									? 'bitrix:bitrix24.license.widget'
									: 'bitrix:intranet.license.widget'
								,
								'air'
							);
							$APPLICATION->includeComponent('bitrix:intranet.helpdesk', 'air', [], false);
							?>
						</div>
						<?php if (!AirTemplate::shouldShowImBar()): ?>
						<div class="air-header__user-profile">
							<?php $APPLICATION->includeComponent('bitrix:intranet.avatar.widget', '', []) ?>
						</div>
						<?php endif; ?>
					</div>
					<div class="air-header__menu" id="air-header-menu"><?
						$headerArea = new StaticArea('header-menu');
						$headerArea->setContainerId('air-header-menu');
						$headerArea->setAssetMode(AssetMode::STANDARD);
						$headerArea->startDynamicArea();
						$headerArea->setStub('');

						$APPLICATION->showViewContent('above_pagetitle');
						// $APPLICATION->showViewContent('main-navigation');
						$APPLICATION->includeComponent(
							'bitrix:menu',
							'top_horizontal',
							[
								'ROOT_MENU_TYPE' => 'left',
								'CHILD_MENU_TYPE' => 'sub',
								'MENU_CACHE_TYPE' => 'N',
								'MENU_CACHE_TIME' => '604800',
								'MENU_CACHE_USE_GROUPS' => 'N',
								'MENU_CACHE_USE_USERS' => 'Y',
								'CACHE_SELECTED_ITEMS' => 'Y',
								'MENU_CACHE_GET_VARS' => [],
								'MAX_LEVEL' => '3',
								'USE_EXT' => 'Y',
								'DELAY' => 'N',
								'ALLOW_MULTI_SELECT' => 'N',
								'ADD_ADMIN_PANEL_BUTTONS' => 'N',
							],
							false
						);

						$APPLICATION->showViewContent('inline-scripts');

						$headerArea->finishDynamicArea();
					?>
					</div>
				</div>
			</header>
			<div class="app__page" id="page-area"><?
				$dynamicArea = new StaticArea("page-area");
				$dynamicArea->setContainerId('page-area');
				$dynamicArea->setAssetMode(AssetMode::STANDARD);
				$dynamicArea->setStub('<script>BX.Intranet.Bitrix24.Template.getComposite().showLoader()</script>');
				$dynamicArea->startDynamicArea();
				?>
				<div class="page <?$APPLICATION->showProperty('BodyClass');?>">
					<header class="page__header">
						<div class="page__menu"><? $APPLICATION->showViewContent('page_menu') ?></div>
						<div class="page__toolbar"><? $APPLICATION->includeComponent('bitrix:ui.toolbar', '', []) ?></div>
						<div class="page__actions"><? $APPLICATION->showViewContent('below_pagetitle') ?></div>
					</header>
					<div class="page__workarea">
						<div class="page__sidebar" id="sidebar"><?
							$APPLICATION->showViewContent('sidebar');
							$APPLICATION->showViewContent('sidebar_tools_1');
							$APPLICATION->showViewContent('sidebar_tools_2');
						?></div>
						<main id="air-workarea-content" class="page__workarea-content<?
							$GLOBALS['APPLICATION']->addBufferContent([AirTemplate::class, 'getWorkAreaContent'])?>"><?
