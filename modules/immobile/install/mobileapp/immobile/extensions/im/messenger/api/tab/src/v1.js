/**
 * @module im/messenger/api/tab/v1
 */
jn.define('im/messenger/api/tab/v1', (require, exports, module) => {
	const { EntityReady } = require('entity-ready');
	const { EventType, ComponentCode } = require('im/messenger/const');

	async function sendChangeTabEvent(tabComponentCode, options)
	{
		await EntityReady.wait(ComponentCode.imNavigation);

		BX.postComponentEvent(
			EventType.navigation.changeTab,
			[tabComponentCode, options],
			ComponentCode.imNavigation,
		);
	}

	module.exports = { sendChangeTabEvent };
});
