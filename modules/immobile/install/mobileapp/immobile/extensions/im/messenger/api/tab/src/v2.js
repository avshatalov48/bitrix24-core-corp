/**
 * @module im/messenger/api/tab/v2
 */
jn.define('im/messenger/api/tab/v2', (require, exports, module) => {
	const { EventType, NavigationTabByComponent } = require('im/messenger/const');

	async function sendChangeTabEvent(tabComponentCode, options)
	{
		const tabId = NavigationTabByComponent[tabComponentCode];

		if (!tabId)
		{
			throw new Error(`invalid componentCode: ${tabComponentCode}`);
		}

		BX.postComponentEvent(
			EventType.navigation.changeTab,
			[tabId, options],
			'im.messenger',
		);
	}

	module.exports = { sendChangeTabEvent };
});
