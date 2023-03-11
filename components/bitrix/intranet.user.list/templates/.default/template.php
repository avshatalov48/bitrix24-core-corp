<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
$component = $this->getComponent();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;

\Bitrix\Main\Loader::includeModule('ui');

CUtil::InitJSCore(['popup']);
\Bitrix\Main\UI\Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
	"ui.fonts.opensans",
]);


$toolbarId = mb_strtolower($arResult['GRID_ID']).'_toolbar';

Toolbar::addFilter([
	'GRID_ID' => $arResult['GRID_ID'],
	'FILTER_ID' => $arResult['FILTER_ID'],
	'FILTER' => $arResult['FILTER'],
	'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'LAZY_LOAD' => [
		'CONTROLLER' => [
			'getList' => 'main.filter.user.getlist',
			'getField' => 'main.filter.user.getfield',
			'componentName' => 'intranet.user.list',
			'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters('intranet.user.list', [
				'USER_PROPERTY_LIST' => $arParams['USER_PROPERTY_LIST']
			])
		]
	],
	'CONFIG' => [
		'AUTOFOCUS' => false,
	],
]);

if (
	isset($_REQUEST['IFRAME'])
	&& $_REQUEST['IFRAME'] == 'Y'
)
{
	Toolbar::deleteFavoriteStar();
}

$buttonID = "{$toolbarId}_button";

if(!empty($arResult['TOOLBAR_MENU']))
{
	$menuButton = new \Bitrix\UI\Buttons\Button([
		"color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
		"icon" => \Bitrix\UI\Buttons\Icon::SETTING,
	]);
	$menuButton->addAttribute('id', $buttonID);
	Toolbar::addButton($menuButton);
}

if(!empty($arResult['TOOLBAR_BUTTONS']))
{
	foreach($arResult['TOOLBAR_BUTTONS'] as $button)
	{
		switch($button['TYPE'])
		{
			case 'ADD':
				$icon = \Bitrix\UI\Buttons\Icon::ADD;
				break;
			default:
				$icon = '';
		}

		Toolbar::addButton([
			"link" => $button['LINK'] ?? null,
			"color" => \Bitrix\UI\Buttons\Color::PRIMARY,
			"icon" => $icon,
			"text" => $button['TITLE'],
			"click" => $button['CLICK']
		]);
	}
}

if (
	SITE_TEMPLATE_ID == "bitrix24"
	&& (
		(
			ModuleManager::isModuleInstalled('bitrix24')
			&& \CBitrix24::isPortalAdmin($arResult["currentUserId"] ?? null)
		)
		|| (
			!ModuleManager::isModuleInstalled('bitrix24')
			&& $USER->IsAdmin()
		)
	)
)
{
	echo Bitrix\Main\Update\Stepper::getHtml([
		'main' => 'Bitrix\Main\Update\UserStepper'
	], Loc::getMessage('INTRANET_USER_LIST_STEPPER_TITLE'));
}

$gridContainerId = 'bx-iul-'.$arResult['GRID_ID'].'-container';
$currentPage = ($arResult['NAV_OBJECT'] instanceof \Bitrix\Main\UI\PageNavigation) ?
	$arResult['NAV_OBJECT']->getCurrentPage() :
	null;

?><span id="<?=htmlspecialcharsbx($gridContainerId)?>"><?
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		[
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'ROWS' => $arResult['ROWS'],
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'CURRENT_PAGE' => $currentPage,
			'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
			'ACTION_ALL_ROWS' => false,
			'AJAX_OPTION_HISTORY' => 'N',
			'AJAX_MODE' => 'Y',
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'EDITABLE' => false
		],
		$component
	);
?></span><?

?><script>
	BX.ready(function () {

		BX.message({
			'INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS': '<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS"))?>',
			'INTRANET_USER_LIST_ACTION_RESTORE_CONFIRM': '<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_LIST_ACTION_RESTORE_CONFIRM"))?>',
			'INTRANET_USER_LIST_ACTION_DELETE_CONFIRM': '<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_LIST_ACTION_DELETE_CONFIRM"))?>',
			'INTRANET_USER_LIST_ACTION_DEACTIVATE_CONFIRM': '<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_LIST_ACTION_DEACTIVATE_CONFIRM"))?>',
			'INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_CONFIRM': '<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_CONFIRM"))?>'
		});

		jsBXIUL = new BX.Intranet.UserList.Manager({
			componentName: '<?=$component->getName() ?>',
			signedParameters: '<?=$component->getSignedParameters()?>',
			gridId: '<?=\CUtil::JSEscape($arResult['GRID_ID'])?>',
			filterId: '<?=\CUtil::JSEscape($arResult['FILTER_ID'])?>',
			gridContainerId: '<?=\CUtil::JSEscape($gridContainerId)?>',
			toolbar: {
				id: '<?=CUtil::JSEscape($toolbarId)?>',
				menuButtonId: '<?=\CUtil::JSEscape($buttonID)?>',
				menuItems: <?=CUtil::PhpToJSObject($arResult['TOOLBAR_MENU'])?>
			},
			invitationLink: '<?=UrlManager::getInstance()->create('getSliderContent', [
				'c' => 'bitrix:intranet.invitation',
				'mode' => Router::COMPONENT_MODE_AJAX,
				'analyticsLabel[source]' => 'userList',
			]);?>'
		});

	});
</script><?
if (
	SITE_TEMPLATE_ID == 'bitrix24'
	&& !empty($arParams['SLIDER_PROFILE_USER_ID'])
	&& intval($arParams['SLIDER_PROFILE_USER_ID']) > 0
)
{
	$userProfileUrl = \Bitrix\Main\Config\Option::get('intranet', 'search_user_url', '/user/#ID#/');
	$userProfileUrl = str_replace(['#ID#', '#USER_ID#'], intval($arParams['SLIDER_PROFILE_USER_ID']), $userProfileUrl);

	$request = \Bitrix\Main\Context::getCurrent()->getRequest();

	if (
		$request->get('entityType')
		&& $request->get('entityId')
	)
	{
		$uri = new Bitrix\Main\Web\Uri($userProfileUrl);
		$uri->deleteParams(['entityType', 'entityId']);
		$uri->addParams([
			'entityType' => $request->get('entityType'),
			'entityId' => $request->get('entityId')
		]);
		$userProfileUrl = $uri->getUri();
	}

	?><script>
	BX.ready(function () {
		BX.SidePanel.Instance.open(
			'<?=$userProfileUrl?>',
			{
				cacheable: false,
				allowChangeHistory: false,
				contentClassName: "bitrix24-profile-slider-content",
				loader: "intranet:slider-profile",
				width: 1100,
				events: {
					onCloseComplete: function(event) {
						// timeout recommended by Compote
						setTimeout(function() {
							window.history.replaceState({}, "", '<?=$arParams['LIST_URL']?>');
						}, 500);
					}
				}
			}
		);
	});
</script><?
}
else if (
	\Bitrix\Intranet\CurrentUser::get()->isAdmin() &&
	\Bitrix\Main\Context::getCurrent()->getRequest()->get('showInviteDialog')
)
{
?>
<script>
	BX.ready(function () {
		<?=CIntranetInviteDialog::ShowInviteDialogLink($arInviteParams)?>;
	});
</script>
<?php
}