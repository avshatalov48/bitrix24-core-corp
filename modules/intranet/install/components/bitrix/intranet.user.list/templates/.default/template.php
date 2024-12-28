<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
$component = $this->getComponent();

use Bitrix\Intranet\CurrentUser;
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
	'ui.counterpanel',
	'intranet.invitation-counter',
	'pull.client',
	'main.pagination.lazyloadtotalcount',
]);


$toolbarId = mb_strtolower($arResult['GRID_ID']).'_toolbar';

Toolbar::addFilter(\Bitrix\Main\Filter\Component\ComponentParams::get($arResult['GRID_FILTER'],
	[
		'GRID_ID' => $arResult['FILTER_ID'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'ENABLE_LIVE_SEARCH' => true,
		'ENABLE_LABEL' => true,
		'LAZY_LOAD' => [
			'CONTROLLER' => [
				'getList' => 'intranet.filter.user.getlist',
				'getField' => 'intranet.filter.user.getfield',
				'componentName' => 'intranet.user.list',
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters('intranet.user.list', [
					'USER_PROPERTY_LIST' => $arParams['USER_PROPERTY_LIST']
				])
			]
		],
		'CONFIG' => [
			'AUTOFOCUS' => false,
		],
	])
);

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
	Toolbar::addButton($arResult['TOOLBAR_MENU']);
}

if(!empty($arResult['TOOLBAR_BUTTONS']))
{
	foreach($arResult['TOOLBAR_BUTTONS'] as $button)
	{
		$icon = match ($button['TYPE'] ?? '') {
			'ADD' => \Bitrix\UI\Buttons\Icon::ADD,
			default => '',
		};

		Toolbar::addButton([
			'dropdown' => false,
			'link' => $button['LINK'] ?? null,
			'color' => \Bitrix\UI\Buttons\Color::SUCCESS,
			'icon' => $icon,
			'text' => $button['TITLE'],
			'click' => $button['CLICK'] ?? null
		], \Bitrix\UI\Toolbar\ButtonLocation::AFTER_TITLE);
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

$intranetUser = new \Bitrix\Intranet\User();
if (
	$intranetUser->isAdmin()
	|| (
		$intranetUser->getInvitationCounterValue() > 0
		&& $arResult['INVITE_FILTER_AVAILABLE']
	)
):

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-pagetitle-view');

$this->SetViewTarget('below_pagetitle', 1000);
?>
	<div id="invitation-employee-counter_panel" class="intranet-user-list-counter-panel"></div>
<?php
$this->EndViewTarget();
endif;


?><span class="intranet-user-list-grid-container" id="<?=htmlspecialcharsbx($gridContainerId)?>"><?php
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult['GRID_PARAMS'],
	$component
);
?></span><?php

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
			gridId: '<?= \CUtil::JSEscape($arResult['GRID_ID'])?>',
			invitationLink: '<?=UrlManager::getInstance()->create('getSliderContent', [
				'c' => 'bitrix:intranet.invitation',
				'mode' => Router::COMPONENT_MODE_AJAX,
				'analyticsLabel[source]' => 'userList',
				'analyticsLabel[tool]' => 'Invitation',
				'analyticsLabel[category]' => 'invitation',
				'analyticsLabel[event]' => 'drawer_open',
				'analyticsLabel[c_section]' => 'userList',
			]);?>',
		});

		const filterId = '<?=\CUtil::JSEscape($arResult['FILTER_ID'])?>';
		const counterId = '<?=\Bitrix\Intranet\Invitation::getInvitedCounterId()?>';
		const waitConfirmationCounterId = '<?=\Bitrix\Intranet\Invitation::getWaitConfirmationCounterId()?>';
		const presetId = 'invited';
		const waitConfirmationPresetId = 'wait_confirmation';
		const filter = BX.Main.filterManager.getById(filterId);
		<?php if(
			$intranetUser->isAdmin()
			|| (
				$intranetUser->getInvitationCounterValue() > 0
				&& $arResult['INVITE_FILTER_AVAILABLE']
			)
		): ?>
		const counter = new BX.Intranet.InvitationCounter({
			target: BX('invitation-employee-counter_panel'),
			title: '<?=Loc::getMessage('INTRANET_USER_LIST_COUNTER_PANEL_TITLE')?>',
			items: [
				<?php if($arResult['WAITING_FILTER_AVAILABLE']): ?>
				{
					id: waitConfirmationCounterId,
					separator: true,
					title: '<?=Loc::getMessage('INTRANET_USER_LIST_COUNTER_WAITING_CONFIRMATION_TITLE')?>',
					value: <?=$intranetUser->getWaitConfirmationCounterValue()?>,
					isActive: filter.getPreset().getCurrentPresetId() === waitConfirmationPresetId,
					eventsForActive: {
						click: () => {
							if (filter !== null)
							{
								const preset = filter.getPreset();
								preset.applyPinnedPreset();
							}
						}
					},
					eventsForUnActive: {
						click: () => {
							if (filter !== null)
							{
								const preset = filter.getPreset();
								preset.deactivateAllPresets();
								preset.activatePreset(waitConfirmationPresetId);
								preset.applyPreset(waitConfirmationPresetId);
								filter.applyFilter(null, true);
							}
						}
					},
				},
				<?php endif; ?>
				<?php if($arResult['INVITE_FILTER_AVAILABLE']): ?>
				{
					id: counterId,
					separator: false,
					title: '<?=Loc::getMessage('INTRANET_USER_LIST_COUNTER_INVITED_TITLE')?>',
					value: <?=$intranetUser->getInvitationCounterValue()?>,
					isActive: filter.getPreset().getCurrentPresetId() === presetId,
					eventsForActive: {
						click: () => {
							if (filter !== null)
							{
								const preset = filter.getPreset();
								preset.applyPinnedPreset();
							}
						}
					},
					eventsForUnActive: {
						click: () => {
							if (filter !== null)
							{
								const preset = filter.getPreset();
								preset.deactivateAllPresets();
								preset.activatePreset(presetId);
								preset.applyPreset(presetId);
								filter.applyFilter(null, true);
							}
						}
					},
				}
				<?php endif; ?>
			],
			filterEvents: {
				apply: (event) => {
					const [filterId, action, filter] = event.data;
					const counterItem = counter.getCounterPanel().getItemById(counterId);
					const waitConfirmationCounterItem = counter.getCounterPanel().getItemById(waitConfirmationCounterId);

					if (filter.getPreset().getCurrentPresetId() === presetId)
					{
						counterItem?.activate(false);
					}
					else
					{
						counterItem?.deactivate(false);
					}

					if (filter.getPreset().getCurrentPresetId() === waitConfirmationPresetId)
					{
						waitConfirmationCounterItem?.activate(false);
					}
					else
					{
						waitConfirmationCounterItem?.deactivate(false);
					}
				},
			},
		});
		counter.show();
		<?php endif;?>
		(new BX.Main.Pagination.Lazyloadtotalcount()).register();
	});
</script><?php
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
</script><?php
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
