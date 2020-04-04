<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Main\UI\Extension::load('ui.buttons');
Main\UI\Extension::load('ui.buttons.icons');
Main\UI\Extension::load('ui.alerts');

Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/mail.client.message.list/templates/.default/user-interface-manager.js');

$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'pagetitle-toolbar-field-view pagetitle-mail-view')));
$filterOptions = [
	'FILTER_ID' => $arResult['FILTER_ID'],
	'GRID_ID' => $arResult['GRID_ID'],
	'ENABLE_LABEL' => true,
	'FILTER' => $arResult['FILTER'],
	'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
	'RESET_TO_DEFAULT_MODE' => true,
	'VALUE_REQUIRED' => true,
];
$unseenCount = 0;
$mailboxMenu = array();
foreach ($arResult['MAILBOXES'] as $mailboxId => $item)
{
	$unseenCount += $item['__unseen'];

	$mailboxMenu[] = array(
		'text' => sprintf(
			'<span class="main-buttons-item-text">%s</span> %s',
			htmlspecialcharsbx($item['NAME']),
			sprintf('<span class="main-buttons-item-counter %s">%u</span>',
				$item['__unseen'] > 0 ? 'js-unseen-mailbox' : 'main-ui-hide',
				$item['__unseen']
			)
		),
		'dataset' => ['mailboxId' => $mailboxId, 'unseen' => $item['__unseen'], 'sliderIgnoreAutobinding' => 'true'],
		'className' => $item['ID'] == $arResult['MAILBOX']['ID'] ? 'menu-popup-item-take' : 'dummy',
		'href' => \CHTTP::urlAddParams(
			\CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_MAIL_MSG_LIST'],
				array('id' => $item['ID'])
			),
			array_filter(array(
				'IFRAME' => isset($_REQUEST['IFRAME']) ? $_REQUEST['IFRAME'] : null,
				'IFRAME_TYPE' => isset($_REQUEST['IFRAME_TYPE']) ? $_REQUEST['IFRAME_TYPE'] : null,
			))
		),
		'items' => $mailboxId == $arResult['MAILBOX']['ID'] ? $arResult['DIRS_MENU'] : false,
	);
}

$addMailboxMenuItem = array(
	'text' => Loc::getMessage('MAIL_CLIENT_MAILBOX_ADD'),
	'className' => 'dummy',
	'href' => \CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_MAIL_CONFIG'],
		array('act' => '')
	),
);

$userMailboxesLimit = $arResult['MAX_ALLOWED_CONNECTED_MAILBOXES'];
if ($userMailboxesLimit >= 0 && $arResult['USER_OWNED_MAILBOXES_COUNT'] >= $userMailboxesLimit)
{
	if (\CModule::includeModule('bitrix24'))
	{
		\CBitrix24::initLicenseInfoPopupJS();

		$licenseConnectedMailboxesInfo = Loc::getMessage('MAIL_MAILBOX_LICENSE_CONNECTED_MAILBOXES_LIMIT_BODY', array('#LIMIT#' => $userMailboxesLimit));
		$addMailboxMenuItem = array(
			'text' => '<div onclick="' . "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].showLicensePopup('connectedMailboxes')\">" .
				'<span class="mail-connect-lock-text">' . Loc::getMessage('MAIL_CLIENT_MAILBOX_ADD') . '</span>' .
				'<span class="mail-connect-lock-icon"></span>' .
			'</div>',
			'className' => 'dummy',
		);
	}
}

$mailboxMenu[] = array(
	'delimiter' => true,
);
$mailboxMenu[] = $addMailboxMenuItem;

$configPath = \CHTTP::urlAddParams(
	\CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_MAIL_CONFIG'],
		array('act' => 'edit')
	),
	array('id' => $arResult['MAILBOX']['ID'])
);
$createPath = \CHTTP::urlAddParams(
	$arParams['PATH_TO_MAIL_MSG_NEW'],
	array('id' => $arResult['MAILBOX']['ID'])
);

$settingsMenu = [
	[
		'text' => Loc::getMessage('MAIL_MESSAGE_LIST_SETTINGS_LINK'),
		'className' => '',
		'href' => htmlspecialcharsbx($configPath),
		'disabled' => $USER->getId() != $arResult['MAILBOX']['USER_ID'] && !$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'),
	],
	[
		'text' => Loc::getMessage('MAIL_MESSAGE_LIST_SIGNATURE_LINK'),
		'href' => htmlspecialcharsbx($arParams['PATH_TO_MAIL_SIGNATURES']),
	],
	[
		'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BLACKLIST_LINK'),
		'className' => '',
		'href' => htmlspecialcharsbx($arParams['PATH_TO_MAIL_BLACKLIST']),
	],
];
$this->setViewTarget('mail-msg-counter');

?>
<? $isVisibleCounters = ($arResult['UNSEEN'] > 0); ?>

<div class="mail-msg-counter-title">
	<span class="mail-msg-counter-name"><?=Loc::getMessage('MAIL_MESSAGE_LIST_COUNTERS_TITLE') ?>:</span>

	<span class="mail-msg-counter-container <?= $arResult['UNSEEN'] == 0 ? 'main-ui-hide' : ''; ?>"
		data-role="unreadCounter"
		onclick="BX.Mail.Client.Message.List['<?=CUtil::JSEscape($component->getComponentId())?>'].onUnreadCounterClick()">
		<span class="mail-msg-counter-inner" id="mail_msg_unseen_counter">
			<span class="mail-msg-counter-number" data-role="unread-counter-number"><?=intval($arResult['UNSEEN']) ?></span>
			<span class="mail-msg-counter-text"><?=Loc::getMessage('MAIL_MESSAGE_LIST_COUNTERS_UNSEEN') ?></span>
		</span>
	</span>

	<span class="mail-msg-counter-empty <?=($isVisibleCounters ? 'main-ui-hide' : '') ?>" data-role="emptyCountersTitle">
		<?=Loc::getMessage('MAIL_MESSAGE_LIST_COUNTERS_EMPTY') ?>
	</span>
</div>

<?

$this->endViewTarget();

if (SITE_TEMPLATE_ID == 'bitrix24')
{
	$this->setViewTarget('inside_pagetitle'); ?>

	<div class="pagetitle-container mail-pagetitle-flexible-space">
		<? $APPLICATION->includeComponent(
			'bitrix:main.ui.filter', '',
			$filterOptions
		); ?>
		<a class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-dropdown"
			data-role="mailbox-current-title"
			data-mailbox-id="<?=intval($arResult['MAILBOX']['ID']) ?>">
			<span style="display: inline-block; width: 100%; overflow: hidden; text-overflow: ellipsis; ">
				<?=($arResult['MAILBOX']['NAME']) ?>
				<i class="ui-btn-counter <?=($unseenCount > 0 ? '' : 'main-ui-hide') ?>"
					data-role="unseen-total"><?=intval($unseenCount) ?></i>
			</span>
		</a>
	</div>

	<button class="ui-btn" type="button" style="display: none; "></button>
	<? if (\Bitrix\Mail\Helper\LicenseManager::isSyncAvailable()): ?>
	<button class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-icon-business" type="button" id="mail-msg-sync-button"
		style="min-width: 39px; min-width: var(--ui-btn-height); " title="<?=Loc::getMessage('MAIL_MESSAGE_SYNC_BTN_HINT') ?>"
		onclick="BXMailMailbox.sync(this, BX('mail-msg-sync-stepper'), '<?= CUtil::jsEscape($arResult['GRID_ID']) ?>'); "></button>
	<? endif ?>
	<div class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-icon-setting"
		style="min-width: 39px; min-width: var(--ui-btn-height); "
		data-role="mail-list-settings-menu-popup-toggle"></div>
	<a class="ui-btn ui-btn-primary" href="<?=htmlspecialcharsbx($createPath) ?>"
		style="overflow: hidden; text-overflow: ellipsis; ">
		<?=Loc::getMessage('MAIL_MESSAGE_NEW_BTN') ?>
	</a>

	<? $this->endViewTarget();

	$this->setViewTarget('below_pagetitle'); ?>

	<div class="mail-msg-counter" id="mail-msg-counter-title">
		<?=$APPLICATION->getViewContent('mail-msg-counter') ?>
	</div>

	<? if (!empty($arResult['MAILBOX']['LINK'])): ?>
		<div style="float: right; margin-right: 20px; ">
			<a class="ui-btn ui-btn-themes ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps"
				href="<?=htmlspecialcharsbx($arResult['MAILBOX']['LINK']) ?>" target="_blank"><?=Loc::getMessage('MAIL_MESSAGE_LIST_LINK') ?></a>
		</div>
	<? endif ?>

	<? $this->endViewTarget();
}
else
{
	?>

	<div style="display: flex; margin: 0 20px; ">
		<? $APPLICATION->includeComponent(
			'bitrix:main.ui.filter', '',
			$filterOptions
		); ?>
	</div>
	<div style="display: flex; margin: 0 20px; ">
		<a class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-dropdown"
			data-role="mailbox-current-title"
			data-mailbox-id="<?=intval($arResult['MAILBOX']['ID']) ?>">
			<span style="display: inline-block; width: 100%; overflow: hidden; text-overflow: ellipsis; ">
				<?=($arResult['MAILBOX']['NAME']) ?>
				<i class="ui-btn-counter <?=($unseenCount > 0 ? '' : 'main-ui-hide') ?>"
					data-role="unseen-total"><?=intval($unseenCount) ?></i>
			</span>
		</a>
		<button class="ui-btn" type="button" style="display: none; "></button>
		<? if (\Bitrix\Mail\Helper\LicenseManager::isSyncAvailable()): ?>
		<button class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-icon-business" type="button" id="mail-msg-sync-button"
			style="min-width: 39px; min-width: var(--ui-btn-height); " title="<?=Loc::getMessage('MAIL_MESSAGE_SYNC_BTN_HINT') ?>"
			onclick="BXMailMailbox.sync(this, BX('mail-msg-sync-stepper'), '<?= CUtil::jsEscape($arResult['GRID_ID']) ?>'); "></button>
		<? endif ?>
		<div class="ui-btn ui-btn-themes ui-btn-light-border ui-btn-icon-setting"
			style="min-width: 39px; min-width: var(--ui-btn-height); "
			data-role="mail-list-settings-menu-popup-toggle"></div>
		<a class="ui-btn ui-btn-primary" href="<?=htmlspecialcharsbx($createPath) ?>"
			style="margin-right: 20px; overflow: hidden; text-overflow: ellipsis; ">
			<?=Loc::getMessage('MAIL_MESSAGE_NEW_BTN') ?>
		</a>
	</div>

	<?
}

$this->setViewTarget('mail-msg-counter-script');

?>

<script type="text/javascript">

BX('mail-msg-counter-title').innerHTML = '<?=\CUtil::jsEscape($APPLICATION->getViewContent('mail-msg-counter')) ?>';

</script>

<?

$this->endViewTarget();

addEventHandler('main', 'onAfterAjaxResponse', function ()
{
	global $APPLICATION;
	return $APPLICATION->getViewContent('mail-msg-counter-script');
});


if (Main\Loader::includeModule('pull'))
{
	global $USER;
	\CPullWatch::add($USER->getId(), 'mail_mailbox_' . $arResult['MAILBOX']['ID']);
}

$showStepper = 0 == $arResult['MAILBOX']['SYNC_LOCK'];
if ($arResult['MAILBOX']['SYNC_LOCK'] > 0)
{
	$showStepper = time() - $arResult['MAILBOX']['SYNC_LOCK'] > 20;
}

\CJsCore::init(array('update_stepper'));

?>

<? if (!\Bitrix\Mail\Helper\LicenseManager::isSyncAvailable()): ?>
	<div style="background: #eef2f4; padding-bottom: 1px; margin-bottom: -1px; ">
		<div class="ui-alert ui-alert-warning ui-alert-icon-warning">
			<span class="ui-alert-message"><?=Loc::getMessage('MAIL_CLIENT_CANCELATION_WARNING_2') ?></span>
		</div>
	</div>
<? endif ?>

<div class="mail-msg-list-stepper-wrapper">

	<div id="mail-msg-sync-stepper" class="main-stepper main-stepper-show"
		<? if (!$showStepper): ?> style=" display: none; "<? endif ?>>
		<div class="main-stepper-info"><?=Loc::getMessage('MAIL_CLIENT_MAILBOX_SYNC_BAR') ?></div>
		<div class="main-stepper-inner">
			<div class="main-stepper-bar">
				<div class="main-stepper-bar-line" style="width: 0%;"></div>
			</div>
			<div class="main-stepper-steps"></div>
		</div>
	</div>

	<?=Main\Update\Stepper::getHtml(
		array(
			'mail' => array(
				'Bitrix\Mail\Helper\MessageIndexStepper',
				'Bitrix\Mail\Helper\ContactsStepper',
				'Bitrix\Mail\Helper\MessageClosureStepper',
			),
		),
		Loc::getMessage('MAIL_CLIENT_MAILBOX_INDEX_BAR')
	) ?>

</div>

<?

$snippet = new Main\Grid\Panel\Snippet();

$actionPanelActionButtons = [
	[
		'TYPE' => \Bitrix\Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['read']['id'],
		'ICON' => $arResult['gridActionsData']['read']['icon'],
		'TEXT' => '<span data-role="read-action">' . $arResult['gridActionsData']['read']['text'] . '</span>',
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onReadClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => \Bitrix\Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['notRead']['id'],
		'ICON' => $arResult['gridActionsData']['notRead']['icon'],
		'TEXT' => '<span data-role="not-read-action">' . $arResult['gridActionsData']['notRead']['text'] . '</span>',
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onReadClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['delete']['id'],
		'ICON' => $arResult['gridActionsData']['delete']['icon'],
		'TEXT' => $arResult['gridActionsData']['delete']['text'],
		'ONCHANGE' => [
			[
				'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
				'CONFIRM' => true,
				'CONFIRM_APPLY_BUTTON' => 'CONFIRM_APPLY_BUTTON',
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDeleteClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['spam']['id'],
		'ICON' => $arResult['gridActionsData']['spam']['icon'],
		'TEXT' => '<span data-role="spam-action">' . $arResult['gridActionsData']['spam']['text'] . '</span>',
		'ONCHANGE' => [
			[
				'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onSpamClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ICON' => $arResult['gridActionsData']['notSpam']['icon'],
		'ID' => $arResult['gridActionsData']['notSpam']['id'],
		'TEXT' => '<span data-role="not-spam-action">' . $arResult['gridActionsData']['notSpam']['text'] . '</span>',
		'ONCHANGE' => [
			[
				'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onSpamClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::DROPDOWN,
		'ID' => $arResult['gridActionsData']['move']['id'],
		'ICON' => $arResult['gridActionsData']['move']['icon'],
		'SUBMENU_OPTIONS' => $arResult['gridActionsData']['move']['submenuOptions'],
		'TEXT' => $arResult['gridActionsData']['move']['text'],
		'ITEMS' => $arResult['foldersItems'],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['task']['id'],
		'ICON' => $arResult['gridActionsData']['task']['icon'],
		'TEXT' => $arResult['gridActionsData']['task']['text'],
		'DISABLED' => true,
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
					],
				],
			],
		],
	]
];
if ($arResult['userHasCrmActivityPermission'])
{
	$actionPanelActionButtons = array_merge($actionPanelActionButtons, [
		[
			'TYPE' => Main\Grid\Panel\Types::BUTTON,
			'ID' => $arResult['gridActionsData']['addToCrm']['id'],
			'ICON' => $arResult['gridActionsData']['addToCrm']['icon'],
			'TEXT' => '<span data-role="crm-action">' . $arResult['gridActionsData']['addToCrm']['text'] . '</span>',
			'DISABLED' => true,
			'ONCHANGE' => [
				[
					'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						[
							'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
						],
					],
				],
			],
		],
		[
			'TYPE' => Main\Grid\Panel\Types::BUTTON,
			'ICON' => $arResult['gridActionsData']['excludeFromCrm']['icon'],
			'ID' => $arResult['gridActionsData']['excludeFromCrm']['id'],
			'TEXT' => '<span data-role="not-crm-action">' . $arResult['gridActionsData']['excludeFromCrm']['text'] . '</span>',
			'DISABLED' => true,
			'ONCHANGE' => [
				[
					'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [
						[
							'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
						],
					],
				],
			],
		],
	]);
}
$actionPanelActionButtons = array_merge($actionPanelActionButtons, [
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ICON' => $arResult['gridActionsData']['liveFeed']['icon'],
		'ID' => $arResult['gridActionsData']['liveFeed']['id'],
		'TEXT' => $arResult['gridActionsData']['liveFeed']['text'],
		'DISABLED' => true,
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ID' => $arResult['gridActionsData']['discuss']['id'],
		'ICON' => $arResult['gridActionsData']['discuss']['icon'],
		'TEXT' => $arResult['gridActionsData']['discuss']['text'],
		'DISABLED' => true,
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
					],
				],
			],
		],
	],
	[
		'TYPE' => Main\Grid\Panel\Types::BUTTON,
		'ICON' => $arResult['gridActionsData']['event']['icon'],
		'ID' => $arResult['gridActionsData']['event']['id'],
		'TEXT' => $arResult['gridActionsData']['event']['text'],
		'DISABLED' => true,
		'ONCHANGE' => [
			[
				'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($component->getComponentId()) . "'].onDisabledGroupActionClick()",
					],
				],
			],
		],
	],
]);

?>

<div class="mail-msg-list-grid">

<?

$APPLICATION->includeComponent(
	'bitrix:main.ui.grid', '',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'MESSAGES' => $arResult['MESSAGES'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		//'ALLOW_PIN_HEADER' => true,
		'TOP_ACTION_PANEL_CLASS' => 'mail-msg-list-action-panel',
		'TOP_ACTION_PANEL_RENDER_TO' => '.main-grid-header',
		'SHOW_ACTION_PANEL' => false,
		'TOP_ACTION_PANEL_PINNED_MODE' => true,
		'HEADERS' => array(
			array('id' => 'ID', 'name' => 'ID', 'default' => false, 'editable' => false),
			array('id' => 'FROM', 'name' => Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_FROM'), 'class' => 'mail-msg-list-from-cell-head', 'default' => true, 'editable' => false, 'showname' => false),
			array('id' => 'SUBJECT', 'name' => Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_SUBJECT'), 'class' => 'mail-msg-list-subject-cell-head', 'default' => true, 'editable' => false, 'showname' => false),
			array('id' => 'DATE', 'name' => Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_DATE'), 'class' => 'mail-msg-list-date-cell-head', 'default' => true, 'editable' => false, 'showname' => false),
			array('id' => 'BIND', 'name' => Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND'), 'class' => 'mail-msg-list-bind-cell-head', 'default' => true, 'editable' => false, 'showname' => false),
		),

		'ROWS' => $arResult['ROWS'],

		'SHOW_GRID_SETTINGS_MENU' => false,
		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_ROWS_SORT' => false,
		'SHOW_NAVIGATION_PANEL' => false,

		'SHOW_MORE_BUTTON' => true,
		'ENABLE_NEXT_PAGE' => !empty($arResult['ENABLE_NEXT_PAGE']),
		'NAV_PARAM_NAME' => $arResult['NAV_OBJECT']->getId(),
		'CURRENT_PAGE' => $arResult['NAV_OBJECT']->getCurrentPage(),
		'ACTION_PANEL' => array(
			'GROUPS' => array(
				array('ITEMS' => $actionPanelActionButtons),
			),
		),

		'SHOW_CHECK_ALL_CHECKBOXES' => true,
	)
);

?>

</div>

<script type="text/javascript">
	BX.ready(function()
	{
		new BX.Mail.Client.Message.List({
			id: '<?= CUtil::JSEscape($component->getComponentId())?>',
			gridId: '<?= CUtil::JSEscape($arResult['GRID_ID'])?>',
			mailboxId: <?= intval($arResult['MAILBOX']['ID']) ?>,
			taskViewUrlTemplate: '<?= CUtil::JSEscape($arResult['taskViewUrlTemplate']) ?>',
			taskViewUrlIdForReplacement: '<?= CUtil::JSEscape($arResult['taskViewUrlIdForReplacement']) ?>',
			mailboxMenu: <?= Main\Web\Json::encode($mailboxMenu) ?>,
			settingsMenu: <?= Main\Web\Json::encode($settingsMenu) ?>,
			moveBtnMailIdPrefix: '<?= CUtil::JSEscape($arResult['gridActionsData']['move']['id']) ?>',
			canDelete: <?= CUtil::PhpToJSObject((bool)$arResult['trashDir']); ?>,
			canMarkSpam: <?= CUtil::PhpToJSObject((bool)$arResult['spamDir']); ?>,
			userHasCrmActivityPermission: <?= \CUtil::PhpToJSObject($arResult['userHasCrmActivityPermission'])?>,
			outcomeDir: '<?= CUtil::JSEscape($arResult['outcomeDir']) ?>',
			spamDir: '<?= CUtil::JSEscape($arResult['spamDir']) ?>',
			trashDir: '<?= CUtil::JSEscape($arResult['trashDir']) ?>',
			connectedMailboxesLicenseInfo: '<?= CUtil::JSEscape($licenseConnectedMailboxesInfo) ?>',
			ENTITY_TYPE_NO_BIND: '<?= CUtil::JSEscape(\Bitrix\Mail\Internals\MessageAccessTable::ENTITY_TYPE_NO_BIND) ?>',
			ENTITY_TYPE_CRM_ACTIVITY: '<?= CUtil::JSEscape(\Bitrix\Mail\Internals\MessageAccessTable::ENTITY_TYPE_CRM_ACTIVITY) ?>',
			ENTITY_TYPE_TASKS_TASK: '<?= CUtil::JSEscape(\Bitrix\Mail\Internals\MessageAccessTable::ENTITY_TYPE_TASKS_TASK) ?>',
			ERROR_CODE_CAN_NOT_MARK_SPAM: 'MAIL_CLIENT_SPAM_FOLDER_NOT_SELECTED_ERROR',
			ERROR_CODE_CAN_NOT_DELETE: 'MAIL_CLIENT_TRASH_FOLDER_NOT_SELECTED_ERROR'
		});
		BX.message({
			MAIL_MESSAGE_LIST_COLUMN_BIND_TASKS_TASK: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_TASKS_TASK')) ?>',
			MAIL_MESSAGE_LIST_COLUMN_BIND_CRM_ACTIVITY: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_CRM_ACTIVITY')) ?>',
			MAIL_CLIENT_AJAX_ERROR: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_CLIENT_AJAX_ERROR')) ?>',
			MAIL_MESSAGE_LIST_BTN_SEEN: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_BTN_SEEN')) ?>',
			MAIL_MESSAGE_LIST_BTN_UNSEEN: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_BTN_UNSEEN')) ?>',
			MAIL_MESSAGE_LIST_BTN_DELETE: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_BTN_DELETE')) ?>',
			MAIL_MESSAGE_LIST_BTN_NOT_SPAM: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_BTN_NOT_SPAM')) ?>',
			MAIL_MESSAGE_LIST_BTN_SPAM: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_BTN_SPAM')) ?>',
			MAIL_MESSAGE_LIST_CONFIRM_DELETE: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_CONFIRM_DELETE')) ?>',
			MAIL_MESSAGE_LIST_CONFIRM_DELETE_BTN: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_CONFIRM_DELETE_BTN')) ?>',
			MAIL_MESSAGE_LIST_CONFIRM_TITLE: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_CONFIRM_TITLE')) ?>',
			MAIL_MESSAGE_LIST_NOTIFY_ADDED_TO_CRM: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_NOTIFY_ADDED_TO_CRM')) ?>',
			MAIL_MESSAGE_LIST_NOTIFY_ADD_TO_CRM_ERROR: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_NOTIFY_ADD_TO_CRM_ERROR')) ?>',
			MAIL_MESSAGE_LIST_NOTIFY_EXCLUDED_FROM_CRM: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_NOTIFY_EXCLUDED_FROM_CRM')) ?>',
			MAIL_MESSAGE_LIST_NOTIFY_SUCCESS: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_NOTIFY_SUCCESS')) ?>',
			MAIL_MESSAGE_LIST_CONFIRM_CANCEL_BTN: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_LIST_CONFIRM_CANCEL_BTN')) ?>',
			MAIL_MAILBOX_LICENSE_CONNECTED_MAILBOXES_LIMIT_TITLE: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MAILBOX_LICENSE_CONNECTED_MAILBOXES_LIMIT_TITLE')) ?>',
			MAIL_MESSAGE_SYNC_BTN_HINT: '<?=\CUtil::jsEscape(Loc::getMessage('MAIL_MESSAGE_SYNC_BTN_HINT')) ?>'
		});

		var mailboxData = <?=Main\Web\Json::encode(array(
			'ID'       => $arResult['MAILBOX']['ID'],
			'EMAIL'    => $arResult['MAILBOX']['EMAIL'],
			'NAME'     => $arResult['MAILBOX']['NAME'],
			'USERNAME' => $arResult['MAILBOX']['USERNAME'],
			'SERVER'   => $arResult['MAILBOX']['SERVER'],
			'PORT'     => $arResult['MAILBOX']['PORT'],
			'USE_TLS'  => $arResult['MAILBOX']['USE_TLS'],
			'LOGIN'    => $arResult['MAILBOX']['LOGIN'],
			'LINK'     => $arResult['MAILBOX']['LINK'],
			'OPTIONS'  => array(
				'flags' => $arResult['MAILBOX']['OPTIONS']['flags'],
				'imap'  => $arResult['MAILBOX']['OPTIONS']['imap'],
			),
		)) ?>;

		// this is to preserve dirs order
		mailboxData.OPTIONS.imap.dirs = <?=json_encode(array_combine(
			Main\Text\Encoding::convertEncoding(array_keys($arResult['MAILBOX']['OPTIONS']['imap']['dirs']), SITE_CHARSET, 'UTF-8'),
			Main\Text\Encoding::convertEncoding(array_values($arResult['MAILBOX']['OPTIONS']['imap']['dirs']), SITE_CHARSET, 'UTF-8')
		)) ?>;

		BXMailMailbox.init(mailboxData);

		<? if (\Bitrix\Mail\Helper\LicenseManager::isSyncAvailable()): ?>
		BXMailMailbox.sync(BX('mail-msg-sync-button'), BX('mail-msg-sync-stepper'), '<?=\CUtil::jsEscape($arResult['GRID_ID']) ?>');
		<? endif ?>

		BX.PULL.extendWatch('mail_mailbox_<?=intval($arResult['MAILBOX']['ID']) ?>');
		BX.addCustomEvent(
			'onPullEvent-mail',
			function (command, params)
			{
				if ('mailbox_sync_status' == command)
				{
					if (<?=intval($arResult['MAILBOX']['ID']) ?> == params.id)
					{
						if (params.new > 0)
						{
							var gridInstance = BX.Main.gridManager.getInstanceById('<?=\CUtil::jsEscape($arResult['GRID_ID']) ?>');

							if (gridInstance.getRows().getCountSelected() == 0)
							{
								gridInstance.reload();
							}
						}

						BXMailMailbox.updateStepper(BX('mail-msg-sync-stepper'), params.complete, params.status);
					}
				}
			}
		);

		BX.addCustomEvent(
			'SidePanel.Slider:onMessage',
			function (event)
			{
				var grid = BX.Main.gridManager.getInstanceById('<?=\CUtil::jsEscape($arResult['GRID_ID']) ?>');

				var urlParams = {};
				if (window !== window.top)
				{
					urlParams.IFRAME = 'Y';
				}

				if (event.getEventId() == 'mail-mailbox-config-success')
				{
					event.data.handled = true;
					if (event.data.id != <?=intval($arResult['MAILBOX']['ID']) ?> || event.data.changed && event.data.changed.imap_dirs)
					{
						grid && grid.tableFade();
						window.location.href = BX.util.add_url_param(
							'<?=\CUtil::jsEscape($arParams['PATH_TO_MAIL_MSG_LIST']) ?>'.replace('#id#', event.data.id),
							urlParams
						);
					}
				}
				else if (event.getEventId() == 'mail-mailbox-config-delete')
				{
					grid && grid.tableFade();
					window.location.href = BX.util.add_url_param(
						'<?=\CUtil::jsEscape($arParams['PATH_TO_MAIL_HOME']) ?>',
						urlParams
					);
				}
				else if (event.getEventId() === 'mail-message-reload-grid')
				{
					grid && grid.reload();
				}
				else if (event.getEventId() == 'mail-message-create-task')
				{
					BX.Mail.Client.Message.List['<?=\CUtil::jsEscape($component->getComponentId()) ?>'].onCreateTaskEvent(event);
				}
			}
		);

		if (window === window.top)
		{
			BX.data(
				BX.findChildByClassName(
					BX('bx_left_menu_menu_external_mail') || BX('menu_external_mail'),
					'menu-item-link',
					true
				),
				'slider-ignore-autobinding',
				'true'
			);
		}
	});

</script>
