/**
 * @module im/messenger/model/sidebar/src/default-element
 */

jn.define('im/messenger/model/sidebar/src/default-element', (require, exports, module) => {
	const sidebarDefaultElement = Object.freeze({
		dialogId: '0',
		isMute: false,
		isHistoryLimitExceeded: false,
	});

	module.exports = {
		sidebarDefaultElement,
	};
});
