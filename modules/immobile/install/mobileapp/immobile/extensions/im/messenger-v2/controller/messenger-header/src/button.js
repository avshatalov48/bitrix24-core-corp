/**
 * @module im/messenger-v2/controller/messenger-header/button
 *
 * @description Warning! Button callbacks should only contain API calls or event emissions,
 * do not write complex logic in them and do not store state.
 */
jn.define('im/messenger-v2/controller/messenger-header/button', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');

	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { showNotificationList } = require('im/messenger/api/notifications-opener');
	const {
		Button,
		PopupCreateButton,
		PopupButton,
	} = require('im/messenger-v2/lib/widget/header-button');

	const HeaderButtonId = Object.freeze({
		search: 'search',
		notification: 'notification',
		readAll: 'read-all',
		more: 'more',
		developerConsole: 'developer-console',
		developerMenu: 'developer-menu',
		developerReload: 'developer-reload',
	});

	const ButtonType = Object.freeze({
		search: 'search',
		notification: 'notification',
		more: 'more',
	});

	const ButtonBadgeCode = Object.freeze({
		notifications: 'notifications',
	});

	const searchButton = Button.create({
		id: HeaderButtonId.search,
		type: ButtonType.search,
		callback: async () => {
			serviceLocator.get('recent-manager').getActiveRecent().openSearch();
		},
	});

	const notificationButton = Button.create({
		id: HeaderButtonId.notification,
		testId: 'notification_badge',
		type: ButtonType.notification,
		badgeCode: ButtonBadgeCode.notifications,
		callback: async () => showNotificationList(),
	});

	const readAllPopupButton = PopupButton.create({
		id: HeaderButtonId.readAll,
		title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_BUTTON_READ_ALL'),
		iconName: Icon.CHATS_WITH_CHECK.getIconName(),
		callback: async () => {
			void serviceLocator.get('read-service').readAllMessages();
		},
	});

	const developerConsolePopupButton = PopupButton.create({
		id: HeaderButtonId.developerConsole,
		title: 'Developer console',
		iconName: Icon.EDIT.getIconName(),
		shouldShow: async () => Feature.isDevModeEnabled,
		callback: async () => {
			const { Console } = await requireLazy('im:messenger/lib/dev/tools');
			Console.open();
		},
	});

	const developerMenuPopupButton = PopupButton.create({
		id: HeaderButtonId.developerMenu,
		title: 'Developer menu',
		iconName: Icon.MORE.getIconName(),
		shouldShow: async () => Feature.isDevelopmentEnvironment,
		callback: async () => {
			void window.messengerDebug.showDeveloperMenu();
		},
	});

	const developerReloadPopupButton = PopupButton.create({
		id: HeaderButtonId.developerReload,
		title: 'reload();',
		iconName: Icon.REFRESH.getIconName(),
		shouldShow: async () => Feature.isDevelopmentEnvironment,
		callback: async () => {
			window.reload();
		},
	});

	const moreButton = PopupCreateButton.create({
		id: HeaderButtonId.more,
		type: ButtonType.more,
		buttons: [
			readAllPopupButton,
			developerConsolePopupButton,
			developerMenuPopupButton,
			developerReloadPopupButton,
		],
	});

	module.exports = {
		searchButton,
		notificationButton,
		moreButton,
		readAllPopupButton,
		developerConsolePopupButton,
		developerMenuPopupButton,
		developerReloadPopupButton,
	};
});
