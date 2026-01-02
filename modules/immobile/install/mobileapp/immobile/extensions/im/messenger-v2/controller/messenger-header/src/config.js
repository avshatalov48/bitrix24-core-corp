/**
 * @module im/messenger-v2/controller/messenger-header/config
 */
jn.define('im/messenger-v2/controller/messenger-header/config', (require, exports, module) => {
	const { NavigationTabId } = require('im/messenger/const');

	const {
		searchButton,
		notificationButton,
		moreButton,
	} = require('im/messenger-v2/controller/messenger-header/button');

	/** @type {HeaderButtonsConfig} */
	const chatsConfig = {
		rightButtons: [
			searchButton,
			notificationButton,
			moreButton,
		],
	};

	/** @type {HeaderButtonsConfig} */
	const copilotConfig = {
		rightButtons: [
			notificationButton,
			moreButton,
		],
	};

	/** @type {HeaderButtonsConfig} */
	const channelConfig = {
		rightButtons: [
			notificationButton,
			moreButton,
		],
	};

	/** @type {HeaderButtonsConfig} */
	const collabConfig = {
		rightButtons: [
			notificationButton,
			moreButton,
		],
	};

	/** @type {HeaderButtonsConfig} */
	const openLinesConfig = {
		rightButtons: [
			notificationButton,
			moreButton,
		],
	};

	/** @type {HeaderButtonsConfig} */
	const taskConfig = {
		rightButtons: [
			notificationButton,
			moreButton,
		],
	};

	const headerControllerConfig = {
		[NavigationTabId.chats]: chatsConfig,
		[NavigationTabId.copilot]: copilotConfig,
		[NavigationTabId.channel]: channelConfig,
		[NavigationTabId.collab]: collabConfig,
		[NavigationTabId.openlines]: openLinesConfig,
		[NavigationTabId.task]: taskConfig,
	};

	module.exports = { headerControllerConfig };
});
