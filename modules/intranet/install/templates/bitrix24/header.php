<?
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Main\Composite\StaticArea;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Page\AssetLocation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$asset = \Bitrix\Main\Page\Asset::getInstance();

// Ajax Performance Optimization
if (isset($_GET['RELOAD']) && $_GET['RELOAD'] == 'Y')
{
	return; //Live Feed Ajax
}
else if (mb_strpos($_SERVER['REQUEST_URI'], '/historyget/') > 0)
{
	return;
}
else if (isset($_GET['IFRAME']) && $_GET['IFRAME'] === 'Y' && !isset($_GET['SONET']))
{
	//For the task iframe popup
	$APPLICATION->SetPageProperty('BodyClass', 'task-iframe-popup');

	$asset->addCss(SITE_TEMPLATE_PATH . '/interface.css', true);
	$asset->addJs(SITE_TEMPLATE_PATH . '/bitrix24.js', true);
	return;
}

Loader::includeModule('intranet');

\Bitrix\Main\UI\Extension::load([
	"ui.fonts.opensans",
	"intranet.sidepanel.bitrix24",
	"socialnetwork.slider",
	"calendar.sliderloader",
	"ui.notification",
	"ui.info-helper",
	"ui.design-tokens",
]);

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/templates/' . SITE_TEMPLATE_ID . '/header.php');

$asset->moveJs('im');
$asset->moveJs('timeman');
$asset->setUnique('bx24', 'TEMPLATE');

$isCompositeMode = defined('USE_HTML_STATIC_CACHE');

$isIndexPage =
	$APPLICATION->GetCurPage(true) === SITE_DIR . 'stream/index.php' ||
	$APPLICATION->GetCurPage(true) === SITE_DIR . 'index.php' ||
	(defined('BITRIX24_INDEX_PAGE') && constant('BITRIX_INDEX_PAGE') === true)
;

$isBitrix24Cloud = ModuleManager::isModuleInstalled('bitrix24');

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

function showJsTitle()
{
	/** @global CMain $APPLICATION */
	global $APPLICATION;
	$APPLICATION->AddBufferContent("getJsTitle");
}

function getJsTitle()
{
	/** @global CMain $APPLICATION */
	global $APPLICATION;
	$title = $APPLICATION->GetTitle("title", true);
	$title = html_entity_decode($title, ENT_QUOTES, SITE_CHARSET);
	return CUtil::JSEscape($title);
}

?><!DOCTYPE html><?
?><html <?if (LANGUAGE_ID == "tr"):?>lang="<?=LANGUAGE_ID?>"<?endif?>><head><?
$asset->addString('<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />', false, AssetLocation::BEFORE_CSS);
$asset->addString('<meta http-equiv="X-UA-Compatible" content="IE=edge" />', false, AssetLocation::BEFORE_CSS);

if ($isBitrix24Cloud)
{
	$asset->addString('<meta name="apple-itunes-app" content="app-id=561683423" />', false, AssetLocation::BEFORE_CSS);
	$asset->addString('<link rel="apple-touch-icon-precomposed" href="/images/iphone/57x57.png" />', false, AssetLocation::BEFORE_CSS);
	$asset->addString('<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/images/iphone/72x72.png" />', false, AssetLocation::BEFORE_CSS);
	$asset->addString('<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/images/iphone/114x114.png" />', false, AssetLocation::BEFORE_CSS);
	$asset->addString('<link rel="apple-touch-icon-precomposed" sizes="144x144" href="/images/iphone/144x144.png" />', false, AssetLocation::BEFORE_CSS);
}

$APPLICATION->ShowHead(false);
$asset->addCss(SITE_TEMPLATE_PATH . '/interface.css', true);
$asset->addJs(SITE_TEMPLATE_PATH . '/bitrix24.js', true);

ThemePicker::getInstance()->showHeadAssets();

$bodyClass = 'template-bitrix24';
$bodyClass .= ' bitrix24-' . ThemePicker::getInstance()->getCurrentBaseThemeId() . '-theme';

$imBarExists =
	Loader::includeModule('im') &&
	CBXFeatures::IsFeatureEnabled('WebMessenger') &&
	!defined('BX_IM_FULLSCREEN');

if ($imBarExists)
{
	$bodyClass .= ' im-bar-mode';
}

if ($isIndexPage && !$isCompositeMode)
{
	$bodyClass .= ' start-page no-paddings';
}

$asset->addString('<link rel="stylesheet" type="text/css" media="print" href="' . \CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/print.css") . '">', false, AssetLocation::AFTER_CSS);
?><title><? if (!$isCompositeMode) $APPLICATION->ShowTitle() ?></title><?
?></head><body class="<?=$bodyClass?>"><?
ThemePicker::getInstance()->showBodyAssets();

$bodyClassProperty = $APPLICATION->getPageProperty('BodyClass');
$APPLICATION->setPageProperty('BodyClass', ($bodyClassProperty ? $bodyClassProperty.' ' : ''));

if ($isCompositeMode):

	$frame = new StaticArea('title');
	$frame->startDynamicArea();
	?><script type="text/javascript">document.title = "<?showJsTitle()?>";</script><?

	if ($isIndexPage):
		?><script type="text/javascript">document.body.classList.add('no-paddings', 'start-page');</script><?
	endif;

	$frame->finishDynamicArea();

endif;

$isExtranet =
	ModuleManager::isModuleInstalled('extranet') &&
	COption::GetOptionString('extranet', 'extranet_site') === SITE_ID;

$APPLICATION->ShowViewContent('im');
$APPLICATION->ShowViewContent('im-fullscreen');

$layoutMode = '';
if (CUserOptions::GetOption('intranet', 'left_menu_collapsed') === 'Y')
{
	$layoutMode .= ' menu-collapsed-mode';
}

?><table class="bx-layout-table<?=$layoutMode?>"><?
	?><tr><td class="bx-layout-header"><?
		if ((!$isBitrix24Cloud || $USER->IsAdmin()) && !defined('SKIP_SHOW_PANEL')):
			?><div id="panel"><?$APPLICATION->ShowPanel();?></div><?
		endif;

if ($isBitrix24Cloud)
{
	if (Option::get('bitrix24', 'creator_confirmed', 'N') !== 'Y')
	{
		$APPLICATION->IncludeComponent('bitrix:bitrix24.creatorconfirmed', '', [], null, ['HIDE_ICONS' => 'Y']);
	}

	if (
		Option::get("bitrix24", "domain_changed", 'N') === 'N' ||
		is_array(\CUserOptions::GetOption('bitrix24', 'domain_changed', false))
	)
	{
		CJSCore::Init(array('b24_rename'));
	}
}

			?><div id="header" class="<?$APPLICATION->ShowProperty("HeaderClass");?>">
				<div id="header-inner"><?
					// This component was used for menu-create-but.
					// We have to include the component before bitrix:timeman for composite mode.
					if (Loader::includeModule('tasks') && CBXFeatures::IsFeatureEnabled('Tasks')):
						$APPLICATION->IncludeComponent(
							'bitrix:tasks.iframe.popup',
							'.default',
							[
								'ON_TASK_ADDED' => '#SHOW_ADDED_TASK_DETAIL#',
								'ON_TASK_CHANGED' => 'BX.DoNothing',
								'ON_TASK_DELETED' => 'BX.DoNothing'
							],
							null,
							['HIDE_ICONS' => 'Y']
						);
					endif;

					if (!$isExtranet)
					{
						if (
							!ModuleManager::isModuleInstalled("timeman") ||
							!$APPLICATION->IncludeComponent('bitrix:timeman', 'bitrix24', array(), false, array("HIDE_ICONS" => "Y" ))
						)
						{
							$APPLICATION->IncludeComponent('bitrix:planner', 'bitrix24', array(), false, array("HIDE_ICONS" => "Y" ));
						}
					}
					else
					{
						CJSCore::Init("timer");?>
						<div class="timeman-container timer-container timeman-container-<?=LANGUAGE_ID?><?=(IsAmPmMode() ? " am-pm-mode" : "")?>" id="timeman-container">
							<div class="timeman-wrap"><span id="timeman-block" class="timeman-block"><span class="bx-time" id="timeman-timer"></span></span></div>
						</div>
						<script type="text/javascript">BX.ready(function() {
							BX.timer.registerFormat("bitrix24_time", B24.Timemanager.formatCurrentTime);
							BX.timer({
								container: BX("timeman-timer"),
								display : "bitrix24_time"
							});
						});</script><?
					}
					?>
					<!--suppress CheckValidXmlInScriptTagBody -->
					<script type="text/javascript" data-skip-moving="true">
						(function() {
							var isAmPmMode = <?=(IsAmPmMode() ? "true" : "false") ?>;
							var time = document.getElementById("timeman-timer");
							var hours = new Date().getHours();
							var minutes = new Date().getMinutes();
							if (time)
							{
								time.innerHTML = formatTime(hours, minutes, 0, isAmPmMode);
							}
							else if (document.addEventListener)
							{
								document.addEventListener("DOMContentLoaded", function() {
									time.innerHTML = formatTime(hours, minutes, 0, isAmPmMode);
								});
							}

							function formatTime(hours, minutes, seconds, isAmPmMode)
							{
								var ampm = "";
								if (isAmPmMode)
								{

									ampm = hours >= 12 ? "PM" : "AM";
									ampm = '<span class="time-am-pm">' + ampm + '</span>';
									hours = hours % 12;
									hours = hours ? hours : 12;
								}
								else
								{
									hours = hours < 10 ? "0" + hours : hours;
								}

								return	'<span class="time-hours">' + hours + '</span>' + '<span class="time-semicolon">:</span>' +
									'<span class="time-minutes">' + (minutes < 10 ? "0" + minutes : minutes) + '</span>' + ampm;
							}
						})();
					</script>
					<div class="header-logo-block"><?include(__DIR__ . '/logo.php');?></div>
					<div class="header-search"><?
						if (!IsModuleInstalled("bitrix24") /*IsModuleInstalled("search")*/ )
						{
							$searchParams = [
								"NUM_CATEGORIES" => "4",
								"CATEGORY_3_TITLE" => Loc::getMessage('BITRIX24_SEARCH_MICROBLOG'),
								"CATEGORY_3" => [
									0 => "microblog", 1 => "blog",
								],
							];
						}
						else
						{
							$searchParams = ['NUM_CATEGORIES' => '3',];
						}

						$APPLICATION->IncludeComponent(
							(ModuleManager::isModuleInstalled("search") ? "bitrix:search.title" : "bitrix:intranet.search.title"),
							(
								ModuleManager::isModuleInstalled("search")
								&& COption::GetOptionString("intranet", "search_title_old", "") == "Y" ? ".default_old" : ""
							),
							array_merge(
								[
									"CHECK_DATES" => "N",
									"SHOW_OTHERS" => "N",
									"TOP_COUNT" => 7,
									"CATEGORY_0_TITLE" => Loc::getMessage('BITRIX24_SEARCH_EMPLOYEE'),
									"CATEGORY_0" => [
										0 => "custom_users",
									],
									"CATEGORY_1_TITLE" => Loc::getMessage('BITRIX24_SEARCH_GROUP'),
									"CATEGORY_1" => [
										0 => "custom_sonetgroups",
									],
									"CATEGORY_2_TITLE" => Loc::getMessage('BITRIX24_SEARCH_MENUITEMS'),
									"CATEGORY_2" => [
										0 => "custom_menuitems",
									],
									"CATEGORY_OTHERS_TITLE" => Loc::getMessage('BITRIX24_SEARCH_OTHER'),
									"SHOW_INPUT" => "N",
									"INPUT_ID" => "search-textbox-input",
									"CONTAINER_ID" => "search",
									"USE_LANGUAGE_GUESS" => (LANGUAGE_ID == "ru") ? "Y" : "N"
								],
								$searchParams),
							false,
							array('HIDE_ICONS' => 'Y')
						);
					?></div>
					<div class="header-personal"><?
						$profileLink = $isExtranet ? SITE_DIR . 'contacts/personal' : SITE_DIR . 'company/personal';
						$APPLICATION->IncludeComponent(
							"bitrix:intranet.user.profile.button",
							"",
							[
								"PATH_TO_USER_PROFILE" => $profileLink."/user/#user_id#/",
								"PATH_TO_USER_PROFILE_EDIT" => $profileLink."/user/#user_id#/edit/",
								"PATH_TO_USER_STRESSLEVEL" => $profileLink."/user/#user_id#/stresslevel/",
								"PATH_TO_USER_COMMON_SECURITY" => $profileLink."/user/#user_id#/common_security/",
							],
							false
						);
						?><div class="header-item" id="header-buttons"><?
							$APPLICATION->IncludeComponent(
								IsModuleInstalled('bitrix24') ?
									"bitrix:bitrix24.license.widget" :
									"bitrix:intranet.license.widget",
								"",
								[]
							);
							$APPLICATION->IncludeComponent("bitrix:intranet.invitation.widget", "", []);
						?></div>
					</div>
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td class="bx-layout-cont"><?php
			$dynamicArea = new \Bitrix\Main\Composite\StaticArea('inline-scripts');
			$dynamicArea->startDynamicArea();
			$APPLICATION->ShowViewContent('inline-scripts');
			$dynamicArea->finishDynamicArea();

			?><table class="bx-layout-inner-table">
				<tr class="bx-layout-inner-top-row">
					<td class="bx-layout-inner-left" id="layout-left-column">
						<?$APPLICATION->IncludeComponent(
							'bitrix:menu',
							'left_vertical',
							[
								"ROOT_MENU_TYPE" => file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . '.superleft.menu_ext.php') ? 'superleft' : 'top',
								"MENU_CACHE_TYPE" => "Y",
								"MENU_CACHE_TIME" => "604800",
								"MENU_CACHE_USE_GROUPS" => "N",
								"MENU_CACHE_USE_USERS" => "Y",
								"CACHE_SELECTED_ITEMS" => "N",
								"MENU_CACHE_GET_VARS" => [],
								"MAX_LEVEL" => "1",
								"USE_EXT" => "Y",
								"DELAY" => "N",
								"ALLOW_MULTI_SELECT" => "N"
							],
							false
						);

						if ($imBarExists)
						{
							//This component changes user counters on the page.
							//User counters can be changed in the left menu (left_vertical template).
							$APPLICATION->IncludeComponent(
								'bitrix:im.messenger',
								'',
								[
									'CONTEXT' => 'POPUP-FULLSCREEN',
									'RECENT' => 'Y',
									'PATH_TO_SONET_EXTMAIL' => Option::get('intranet', 'path_mail_client', SITE_DIR . 'mail/'),
								],
								false,
								['HIDE_ICONS' => 'Y']
							);
						}
					?></td><td class="bx-layout-inner-center" id="content-table"><?
					if ($isCompositeMode)
					{
						$isDefaultTheme = ThemePicker::getInstance()->getCurrentThemeId() === "default";
						$bodyClass = $isDefaultTheme ? "" : " no--background";
						$dynamicArea = new \Bitrix\Main\Page\FrameStatic("workarea");
						$dynamicArea->setAssetMode(\Bitrix\Main\Page\AssetMode::STANDARD);
						$dynamicArea->setContainerId("content-table");
						$dynamicArea->setStub('
							<table style="display: none" id="bx-composite-start-page-loader" class="im-bar-mode start-page bx-layout-inner-inner-table composite-mode'.$bodyClass.'">
								<colgroup><col class="bx-layout-inner-inner-cont"></colgroup>
								<tr>
									<td class="bx-layout-inner-inner-cont">
										<div id="sidebar">
											<div class="skeleton__white-bg-element skeleton__intranet-ustat">
												<div class="skeleton__graph-circle"></div>
												<div class="skeleton__graph-right">
													<div class="skeleton__graph-right_top">
														<div class="skeleton__graph-right_top-circle --first"></div>
														<div class="skeleton__graph-right_top-circle"></div>
														<div class="skeleton__graph-right_top-circle"></div>
														<div class="skeleton__graph-right_top-circle"></div>
														<div class="skeleton__graph-right_top-circle"></div>
														<div class="skeleton__graph-right_top-circle"></div>
														<div class="skeleton__graph-right_top-circle"></div>
													</div>
													<div class="skeleton__graph-right_bottom">
														<div class="skeleton__graph-right_bottom-line"></div>
														<div class="skeleton__graph-right_bottom-line"></div>
													</div>
												</div>
											</div>
											<div class="skeleton__white-bg-element skeleton__pulse">
												<div class="skeleton__pulse_line"></div>
												<div class="skeleton__pulse_block">
													<div class="skeleton__pulse_block-line"></div>
													<div class="skeleton__pulse_block-vertical-line"></div>
													<div class="skeleton__pulse_block-line"></div>
												</div>
											</div>
											<div class="skeleton__white-bg-element skeleton__sidebar">
												<div class="skeleton__sidebar-header">
													<div class="skeleton__sidebar-header_line"></div>
													<div class="skeleton__sidebar-header_circle"></div>
												</div>
												<div class="skeleton__tasks-row">
													<div class="skeleton__tasks-row_line"></div>
													<div class="skeleton__tasks-row_short-line"></div>
													<div class="skeleton__tasks-row_circle"></div>
												</div>
												<div class="skeleton__tasks-row">
													<div class="skeleton__tasks-row_line"></div>
													<div class="skeleton__tasks-row_short-line"></div>
													<div class="skeleton__tasks-row_circle"></div>
												</div>
												<div class="skeleton__tasks-row">
													<div class="skeleton__tasks-row_line"></div>
													<div class="skeleton__tasks-row_short-line"></div>
													<div class="skeleton__tasks-row_circle"></div>
												</div>
												<div class="skeleton__tasks-row">
													<div class="skeleton__tasks-row_line"></div>
													<div class="skeleton__tasks-row_short-line"></div>
													<div class="skeleton__tasks-row_circle"></div>
												</div>
											</div>
											<div class="skeleton__white-bg-element skeleton__sidebar">
												<div class="skeleton__sidebar-header --orange">
													<div class="skeleton__sidebar-header_line"></div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
											</div>
											<div class="skeleton__white-bg-element skeleton__sidebar">
												<div class="skeleton__sidebar-header">
													<div class="skeleton__sidebar-header_line"></div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
												<div class="skeleton__birthdays-row">
													<div class="skeleton__birthdays-circle"></div>
													<div class="skeleton__birthdays-info">
														<div class="skeleton__birthdays-name"></div>
														<div class="skeleton__birthdays-date"></div>
													</div>
												</div>
											</div>
										</div>
										<div id="workarea" class="workarea">
											<div class="workarea-content-paddings skeleton__workarea-content-paddings">
												<div class="skeleton__white-bg-element skeleton__feed-wrap">
													<div class="skeleton__feed-wrap_header">
														<div class="skeleton__feed-wrap_header-link --long"></div>
														<div class="skeleton__feed-wrap_header-link --one"></div>
														<div class="skeleton__feed-wrap_header-link --two"></div>
														<div class="skeleton__feed-wrap_header-link --one"></div>
														<div class="skeleton__feed-wrap_header-link --two"></div>
													</div>
													<div class="skeleton__feed-wrap_header-content">
														<div class="skeleton__feed-wrap_header-text"></div>
													</div>
												</div>
												
												<div class="skeleton__title-block">
													<div class="skeleton__title-block_text"></div>
													<div class="skeleton__title-block-search">
														<div class="skeleton__title-block_search-text"></div>
													</div>
													<div class="skeleton__title-block_filter"></div>
												</div>
												
												<div class="skeleton__white-bg-element skeleton__feed-item">
													<div class="skeleton__feed-item_user-icon"></div>
													<div class="skeleton__feed-item_content">
														<div class="skeleton__feed-item_main">
															<div class="skeleton__feed-item_text --name"></div>
															<div class="skeleton__feed-item_date"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text --short"></div>
														</div>
														<div class="skeleton__feed-item_nav">
															<div class="skeleton__feed-item_nav-line --one"></div>
															<div class="skeleton__feed-item_nav-line --two"></div>
															<div class="skeleton__feed-item_nav-line --three"></div>
															<div class="skeleton__feed-item_nav-line --four"></div>
														</div>
														<div class="skeleton__feed-item_like">
															<div class="skeleton__feed-item_like-icon"></div>
															<div class="skeleton__feed-item_like-name"></div>
														</div>
														<div class="skeleton__feed-item_comment">
															<div class="skeleton__feed-item_comment-icon"></div>
															<div class="skeleton__feed-item_comment-block">
																<div class="skeleton__feed-item_comment-text"></div>
															</div>
														</div>
													</div>
												</div>
												
												<div class="skeleton__white-bg-element skeleton__feed-item">
													<div class="skeleton__feed-item_user-icon"></div>
													<div class="skeleton__feed-item_content">
														<div class="skeleton__feed-item_main">
															<div class="skeleton__feed-item_text --name"></div>
															<div class="skeleton__feed-item_date"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text --short"></div>
														</div>
														<div class="skeleton__feed-item_nav">
															<div class="skeleton__feed-item_nav-line --one"></div>
															<div class="skeleton__feed-item_nav-line --two"></div>
															<div class="skeleton__feed-item_nav-line --three"></div>
															<div class="skeleton__feed-item_nav-line --four"></div>
														</div>
														<div class="skeleton__feed-item_like">
															<div class="skeleton__feed-item_like-icon"></div>
															<div class="skeleton__feed-item_like-name"></div>
														</div>
														<div class="skeleton__feed-item_comment">
															<div class="skeleton__feed-item_comment-icon"></div>
															<div class="skeleton__feed-item_comment-block">
																<div class="skeleton__feed-item_comment-text"></div>
															</div>
														</div>
													</div>
												</div>
												
												<div class="skeleton__white-bg-element skeleton__feed-item">
													<div class="skeleton__feed-item_user-icon"></div>
													<div class="skeleton__feed-item_content">
														<div class="skeleton__feed-item_main">
															<div class="skeleton__feed-item_text --name"></div>
															<div class="skeleton__feed-item_date"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text"></div>
															<div class="skeleton__feed-item_text --short"></div>
														</div>
														<div class="skeleton__feed-item_nav">
															<div class="skeleton__feed-item_nav-line --one"></div>
															<div class="skeleton__feed-item_nav-line --two"></div>
															<div class="skeleton__feed-item_nav-line --three"></div>
															<div class="skeleton__feed-item_nav-line --four"></div>
														</div>
														<div class="skeleton__feed-item_like">
															<div class="skeleton__feed-item_like-icon"></div>
															<div class="skeleton__feed-item_like-name"></div>
														</div>
														<div class="skeleton__feed-item_comment">
															<div class="skeleton__feed-item_comment-icon"></div>
															<div class="skeleton__feed-item_comment-block">
																<div class="skeleton__feed-item_comment-text"></div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</td>
								</tr>
							</table>
							<table style="display: none" id="bx-composite-all-page-loader" class="bx-layout-inner-inner-table composite-mode'.$bodyClass.'">
								<colgroup><col class="bx-layout-inner-inner-cont"></colgroup>
								<tr class="bx-layout-inner-inner-top-row"><td class="bx-layout-inner-inner-cont"><div class="pagetitle-wrap"></div></td></tr>
								<tr>
									<td class="bx-layout-inner-inner-cont">
										<div style="position: relative; height: 60vh;">
											<div class="intranet-loader-container" id="b24-loader">
												<svg class="intranet-loader-circular" viewBox="25 25 50 50">
													<circle class="intranet-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10" />
												</svg>
											</div>
										</div>
									</td>
								</tr>
							</table>
							<script type="text/javascript">
								const page = window.location.pathname;
								if (page == \'/stream/\' || page == \'/stream/index.php\' || page == \'/index.php\')
								{
									let compositeLoader = BX(\'bx-composite-start-page-loader\');
									compositeLoader.style.display= \'\'; 
								}
								else
								{
									let compositeLoader = BX(\'bx-composite-all-page-loader\');
									compositeLoader.style.display= \'\'; 
								}
								B24.showLoading();
							</script>'
						);
						$dynamicArea->startDynamicArea();
					}
						?><table class="bx-layout-inner-inner-table <?$APPLICATION->ShowProperty("BodyClass");?>">
							<colgroup><col class="bx-layout-inner-inner-cont"></colgroup><?
							if (!$isIndexPage):
							?><tr class="bx-layout-inner-inner-top-row">
								<td class="bx-layout-inner-inner-cont">
									<div class="page-header">
										<div class="page-navigation"><?
											$APPLICATION->ShowViewContent("above_pagetitle");
											$APPLICATION->IncludeComponent(
												"bitrix:menu",
												"top_horizontal",
												[
													"ROOT_MENU_TYPE" => "left",
													"CHILD_MENU_TYPE" => "sub",
													"MENU_CACHE_TYPE" => "N",
													"MENU_CACHE_TIME" => "604800",
													"MENU_CACHE_USE_GROUPS" => "N",
													"MENU_CACHE_USE_USERS" => "Y",
													"CACHE_SELECTED_ITEMS" => "Y",
													"MENU_CACHE_GET_VARS" => [],
													"MAX_LEVEL" => "3",
													"USE_EXT" => "Y",
													"DELAY" => "N",
													"ALLOW_MULTI_SELECT" => "N"
												],
												false
											);
											?><div class="page-toolbar"><? $APPLICATION->IncludeComponent("bitrix:ui.toolbar", '', []);?></div>
										</div>
										<div class="pagetitle-below"><?$APPLICATION->ShowViewContent("below_pagetitle");?></div>
									</div>
								</td>
							</tr><?
							endif;
							?><tr>
								<td class="bx-layout-inner-inner-cont">
									<div id="workarea" class="workarea"><?
										if ($APPLICATION->GetProperty("HIDE_SIDEBAR", "N") != "Y"):
											?><div id="sidebar"><?
											$APPLICATION->ShowViewContent("sidebar");
											$APPLICATION->ShowViewContent("sidebar_tools_1");
											$APPLICATION->ShowViewContent("sidebar_tools_2");
											?></div><?
										endif;
										?><div id="workarea-content" class="workarea-content">
											<div class="workarea-content-paddings"><?
											$APPLICATION->ShowViewContent("topblock");
											if ($isIndexPage):
												?><div class="pagetitle-wrap <?$APPLICATION->ShowProperty("TitleClass");?>">
													<div class="pagetitle-inner-container">
														<div class="pagetitle-menu" id="pagetitle-menu"><?$APPLICATION->ShowViewContent("pagetitle")?></div>
														<div class="pagetitle" id="pagetitle"><?$APPLICATION->ShowTitle(false);?></div><?
														$APPLICATION->ShowViewContent("inside_pagetitle");
													?></div>
												</div><?
											endif;
											CPageOption::SetOptionString("main.interface", "use_themes", "N");