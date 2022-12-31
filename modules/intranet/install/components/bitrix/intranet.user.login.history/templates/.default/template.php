<?php

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.dialogs.messagebox', 'ui.notification', 'main.core', 'ui.hint']);

$APPLICATION->SetTitle(Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').' no-background no-all-paddings pagetitle-toolbar-field-view ');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['GRID_COLUMNS'],
		'ROWS' => $arResult['ROWS'],
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'AJAX_MODE' => 'Y',
		'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
		'TOTAL_ROWS_COUNT' => $arResult['NAV_OBJECT']->getRecordCount(),
		'NAV_PARAM_NAME' => $arResult['NAV_OBJECT']->getId(),
		'CURRENT_PAGE' => $arResult['NAV_OBJECT']->getCurrentPage(),
		'PAGE_COUNT' => $arResult['NAV_OBJECT']->getPageCount(),
		'PAGE_SIZE' => 20,
		'SHOW_MORE_BUTTON' => $arResult['NAV_OBJECT']->getPageCount() !== $arResult['NAV_OBJECT']->getCurrentPage(),
		'ENABLE_NEXT_PAGE' => true,
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => true,
		'SHOW_PAGESIZE' => true,
		'SHOW_ACTION_PANEL' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'ALLOW_SORT' => true,
	],
	$this->getComponent(),
	['HIDE_ICONS' => 'Y']
);
?>

<script>
	BX.ready(function(){
		BX.UI.Hint.init(BX('.bx-user-login-history-device-type-icon'));
		<?php if (!$arResult['isConfiguredPortal'] && !$arResult['isCloud']): ?>
			top.BX.Helper.show('redirect=detail&code=16615982');
		<?php endif; ?>
	});

	function showLogoutBox(event)
	{
		BX.UI.Dialogs.MessageBox.show({
			message: '<?= $arParams['USER_ID'] === (int)\Bitrix\Main\Engine\CurrentUser::get()->getId()
				? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_WARNING_MESSAGE_THIS')
				: Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_WARNING_MESSAGE_FOR_USER') ?>',
			title: '<?= Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_WARNING_TITLE') ?>',
			overlay: true,
			buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL,
			minWidth: 400,
			popupOptions:{
				contentBackground: 'transparent',
				closeByEsc: true,
				padding: 0,
				background: '',
				bindOnResize: false,
			},
			onYes: function(messageBox) {
				BX.ajax.runComponentAction('bitrix:intranet.user.profile.password', 'logout', {
					mode: 'ajax',
					data: {
						userId: <?= $arParams['USER_ID'] ?? \Bitrix\Main\Engine\CurrentUser::get()->getId() ?>,
					},
				}).then(() => {
					messageBox.close();
					BX.UI.Notification.Center.notify({
						content: '<?= $arParams['USER_ID'] === (int)\Bitrix\Main\Engine\CurrentUser::get()->getId()
							? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_NOTIFICATION_LOGOUT_ALL_WITHOUT_THIS')
							: Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_NOTIFICATION_LOGOUT_ALL_USER') ?>',
						autoHideDelay: 1800,
					});
				}).catch(() => {
					messageBox.close();
					BX.UI.Notification.Center.notify({
						content: '<?= Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_BUTTON_NOTIFICATION_LOGOUT_ERROR') ?>',
						autoHideDelay: 3600,
					});
				});
			},
		});
	}

	function openSecuritySlider()
	{
		BX.SidePanel.Instance.open('<?= SITE_DIR
		. 'company/personal/user/'
		. $arParams['USER_ID']
		. '/common_security/?page=auth' ?>', {width: 1100});
	}

</script>




